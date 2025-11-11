<?php
require($_SERVER['DOCUMENT_ROOT'] . "/../config.php");
//Main Db connection
$mysql_host=$config['db_host']?$config['db_host']:"";
$mysql_user=$config['db_user']?$config['db_user']:"";
$mysql_pass=$config['db_pass']?$config['db_pass']:"";
$mysql_db=$config['db_name']?$config['db_name']:"";
//Action logs connection
$actions_host=$config['db_host']?$config['db_host']:"";
$actions_user=$config['db_user']?$config['db_user']:"";
$actions_pass=$config['db_pass']?$config['db_pass']:"";
$actions_db=$config['db_name']?$config['db_name']:"";
$connection = mysqli_connect( $mysql_host, $mysql_user, $mysql_pass, $mysql_db);
?>