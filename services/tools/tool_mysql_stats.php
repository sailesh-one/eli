<h1> Mysql Stats - Under Develpment</h1>
<?php
exit;
include("config.php");

foreach ($india_config_ini_values as $env => $values) {
	if($env != "burp_env"){	
		if(!$mysql_connetions[$values['db']]){
			$mysql_connetions[$values['db']] = array(
				'db'=> $values['db'],
				'host'=> $values['host'],
				'port'=> $values['port'],
				'user'=> $values['user'],
				'pass'=> $values['pass'],									
			);
		}
	}	
}		 


$urlparam = '';					 
if(isset($_GET) && $_GET['db'] != ''){
	$g_db = $_GET['db'];
	$host = $mysql_connetions[$g_db]['host'].':'.$mysql_connetions[$g_db]['port'];
	$user = $mysql_connetions[$g_db]['user'];
	$pass = $mysql_connetions[$g_db]['pass'];
	$db = $mysql_connetions[$g_db]['db']; 
	$port = $mysql_connetions[$g_db]['port']; 
	//exit;
	$urlparam = '&db='.$g_db;
} else {
	$g_db = 'bmw_india_prod';
	$host = $mysql_connetions[$g_db]['host'].':'.$mysql_connetions[$g_db]['port'];
	$user = $mysql_connetions[$g_db]['user'];
	$pass = $mysql_connetions[$g_db]['pass'];
	$db = $mysql_connetions[$g_db]['db']; 
	$port = $mysql_connetions[$g_db]['port']; 
}

mysqli_report(MYSQLI_REPORT_OFF);

$connection = mysqli_connect( $host, $user, $pass,$db,$port );
if( mysqli_connect_error() ){
	echo mysqli_connect_error();
	exit;
}
mysqli_select_db($connection,$db);
?>
<!DOCTYPE html>
<html>
<head>
	<title>MySQL Stats</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex,nofollow" />
	<style>
        body {
        background: #ffffff;
        font: normal normal 12px Arial,Helvetica,sans-serif !important;
        color: #404040;
        line-height: normal;
        }
		ul{ margin: 0px; padding: 0px;}
		li{ float: left; list-style: none; padding: 5px; }
		a{text-decoration: none;}
		a:hover{color: #ff0000;}
	</style>
</head>
<body>
<center><h1>Mysql Processlist</h1></center>
<div>Processlist: <?=date("d-m-Y . H:i:s") ?></div>
<br /> 
<?php
$res = mysqli_query( $connection, "SHOW STATUS where variable_name in ( 'Connections', 'Max_used_connections', 'Threads_cached', 'Threads_connected', 'Threads_created', 'Threads_running') " );
while( $row = mysqli_fetch_array( $res ) ){
	echo "<div>" . $row[0] . " : " . $row[1] . "</div>";
}
?>
<br />
<table width="100%"  cellpadding="5" cellspacing="1">
	<tr bgcolor="#cdcdcd">
	    <td align="center">
		<ul>
		    <?php foreach($mysql_connetions as $key => $val){ ?>
		    <li style="<?php echo ($g_db == $key)?'background-color: #ffffff;':'' ?>"><a href="?db=<?php echo $key; ?>"><?php echo $key; ?></a></li>
		    <?php } ?>			
		</ul>
	    </td>
	</tr>
	<tr><td align="center"><h3 style="margin: 0px; padding: 0px;"><?php echo $host ?></h3></td></tr>
	<tr>
		<td align="left">
			<?php if($_REQUEST['refresh'] == 'off' || !$_REQUEST['refresh']){ ?><a href="?refresh=on<?php echo ($_GET['db'])?'&db='.$_GET['db']:''?>"><b>Auto Refresh ON</b></a><?php } ?>
			<?php if($_REQUEST['refresh'] == 'on'){ ?><a href="?refresh=off<?php echo ($_GET['db'])?'&db='.$_GET['db']:''?>"><b>Auto Refresh OFF</b></a><?php	} ?>
		</td>
	</tr>
</table>

<br />

<?php

if( $_REQUEST['action'] == "kill" ){
    mysqli_query( $connection, "kill " . $_REQUEST['id'] . ";" ) or die(mysqli_error($connection));
}

$res = mysqli_query( $connection, "show full processlist" );
//$res = mysqli_query( $connection, "SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST ORDER BY `PROCESSLIST`.`ID` ASC" );
while( $row = mysqli_fetch_assoc( $res ) ){	
	$sq_log[$row['Id']] = $row ;
}
mysqli_data_seek( $res, 0 );
while( $row = mysqli_fetch_assoc( $res ) ){
	$sq_log[$row['Id']] = $row ;
}

?>
<table width="100%" bgcolor="#cdcdcd" cellpadding="5" cellspacing="1">
	<tr bgcolor="#f8f8f8">
        <td>Kill</td>
        <td>ID</td>
        <td>User</td>
        <td>Host</td>
        <td>db</td>
        <td>Command</td>
        <td>Time</td>
        <td>State</td>
        <td>Info</td>
	</tr>
	<?php foreach ($sq_log as $key => $row) {?>
		<tr bgcolor="#ffffff">
        <td>
			<?php if(1==1 || $admin_user[$_SERVER['PHP_AUTH_USER']]){ ?>
			<a href="?action=kill&id=<?=$row['Id'].$urlparam ?>" >Kill</a>
			<?php } ?>
		</td>
        <td><?=$row['Id'] ?></td>
        <td><?=$row['User'] ?></td>
        <td><?=$row['Host'] ?></td>
        <td><?=$row['db'] ?></td>
        <td><?=$row['Command'] ?></td>
        <td><?=$row['Time'] ?></td>
        <td><?=$row['State'] ?></td>        
  <?php if($_REQUEST['kill'] == "blocked" && $row['Time'] > 10 && $row['Command'] == "Query"){
        mysqli_query( $connection, "kill " . $row1['Id'] . ";" ); ?>
        <td>Killed</td>
  <?php }else { ?>
        <td><?=$row['Info'] ?></td>
  <?php } ?>
        </tr>
	<?php } ?>
</table>
<!-- <script language='javascript' >setTimeout("document.location='?rand=<?=time() ?><?php echo ($_GET['db'])?'&db='.$_GET['db']:''?>'",3000);</script> -->
<?php if($_REQUEST['refresh'] == 'on') { ?>
<script language='javascript' >setTimeout("document.location='?refresh=<?=$_REQUEST['refresh']?>&rand=<?=time() ?><?php echo ($_GET['db'])?'&db='.$_GET['db']:''?>'",3000);</script>
<?php } ?>
</body>
</html>