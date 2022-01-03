<?php

exec("service smbd restart",$sEXEC);
#exec("killall -HUP smbd",$sEXEC);
#exec("smbcontrol all reload-config",$sEXEC);

echo $sEXEC;
?>
