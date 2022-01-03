<?php
$sPID = $_POST['pid'];
$sPID = filter_var ( $sPID, FILTER_SANITIZE_NUMBER_INT);
exec("kill -9 $sPID",$sEXEC);

echo $sEXEC;
?>
