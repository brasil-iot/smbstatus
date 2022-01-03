<?php

/***************************/
$PATH = 'script/';

$sUsersFile = $PATH . 'users.txt';
if( (!file_exists($sUsersFile)) OR (time()-filemtime($sUsersFile) > 24 * 3600)) {
	exec("getent passwd | tail -n +28 | sort -n >$sUsersFile",$sEXEC);
}
$aTMP = file($sUsersFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$aUser = array();
/*
 * [0]=> string(13) "root" login
 * [1]=> string(1) "*" password
 * [2]=> string(5) "10500" uid
 * [3]=> string(5) "10513" gid
 * [4]=> string(0) "" full
 * [5]=> string(9) "/root" home
 * [6]=> string(17) "/usr/sbin/nologin" shell
 */
foreach($aTMP as $sTMP) {
	$aLine = explode(':',$sTMP);
	//var_dump($aLine);
	$aUser[$aLine[0]] = array(
			'uid' => $aLine[2],
			'full' => $aLine[4],
			'gid' => $aLine[3],
	);
}

/***************************/
$aTMP = NULL;

exec("sudo smbstatus -b | egrep -v '^(PID|Samba|$|-)' | awk '{ print $1\";\"$2 }'", $aTMP);
/*
 * PID     UID (login)  GID          IP                                        PROTOCOL                               ALGO
 * 17594   11520        domain users 172.17.0.66 (ipv4:172.17.0.66:49770)      SMB3_11           -                    partial(AES-128-CMAC)
 */
$aLogin = array();
foreach($aTMP as $sTMP) {
	$aLine = explode(';', $sTMP);
	$pid = $aLine[0];
	$uid = $aLine[1];
	$aLogin[$pid] = $uid;
}

/***************************/
$aTMP = NULL;

exec("sudo smbstatus -S | egrep -v '^(Service|-|$|IPC)'",$aTMP);
$aConnected = array();
/*
 * SHARE NAME  PID     IP            DATE
 * SHARE$      3619    172.17.0.19   seg dez 20 08:20:38 2021 -03     -            -           
 */
foreach($aTMP as $sTMP) {
	$aLine = preg_split('/[ ]+/',$sTMP,4);
	#var_dump($aLine);
	$pid = $aLine[1];
	$ip = preg_replace("#.*\((.*)\).*#s", "$1", $aLine[2]);
	if (($timestamp = strtotime($aLine[3])) === false) {
		$since = '';
	} else {
		$since = date('Y/m/d H:i:s', $timestamp);
	}
	$aConnected[$pid] = array(
			'ip' => $ip,
			'since' => $since,
	);
}

/***************************/
$aTMP = NULL;

exec("sudo smbstatus -L | egrep -v '^(Pid|Loc|$|-)' | tr -s ' ' | cut -d' ' -f1,2,9,10-",$aTMP);
/*
 * PID  UID (share) FILE          DATE
 * 7461 10500       file/exec.exe Tue Dec 14 11:37:16 2021
 */
$aOpen = array();
foreach($aTMP as $sTMP) {
	$aLine = preg_split('/[ ]+/',$sTMP,4);
	$pid = $aLine[0];
	$uid = $aLine[1];
	$file = $aLine[2];
	if (($timestamp = strtotime($aLine[3])) === false) {
		$since = '';
	} else {
		$since = date('Y/m/d H:i:s', $timestamp);
	}
	if($file == '.' ) {
		continue;
	}
	$aOpen[$uid][$pid][] = array(
			'file' => $file,
			'since' => $since,
	);
}

/***************************/
$aList = array();

foreach($aOpen as $uid => $aDataUID) {
	#echo $uid;
	foreach($aDataUID as $pid => $aDataPID) {
		$uLOGGED = $aLogin[$pid];
		$aTMP = array();
		foreach($aDataPID as $key => $aDataFILE) {
			$aList[] = array(
				'user' => $uLOGGED,
				'machine' => $aConnected[$pid]['ip'],
				'logged' => $aConnected[$pid]['since'],
				'name' => ((!empty($aUser[$uLOGGED]['full'])) ? $aUser[$uLOGGED]['full'] : $aUser[$uLOGGED]['name']) . ' (' . $uLOGGED . ')',
				'pid' => $pid,
				'uid' => $uid,
				'file' => $aDataFILE['file'],
				'since' => $aDataFILE['since'],
			);
		};
		#echo json_encode($aList);
		#exit;
	}
}

$aList = array( 'data' => $aList );
echo json_encode($aList);
