<?php 
include_once("config.php");
// include($_SERVER['DOCUMENT_ROOT'].'/config_master_password.php');
if ( function_exists( 'mail' ) ){
    echo 'mail() is available';
}else{
    echo 'mail() has been disabled';
} 
echo "<br>IP : ".$_SERVER['REMOTE_ADDR']."<br>";

echo phpinfo(); 
?>