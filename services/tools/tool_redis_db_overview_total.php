<h1> Redis DB Overview - Under Develpment</h1>
<?php
exit;
include("config.php");

date_default_timezone_set( "Asia/Kolkata");

$db_details = array('db0' =>"Sessions" , 
					'db1' => "India Dev" , 
					'db2' => "India UAT" , 
					'db3' => "India Production" , 
			  );

$connections_array = array(
						"BMW_India" => array(
							"db1" => array(
									"host"=> $india_credentials['redis_host'], 
									"port"=>6379
									),
							),
					 );

	if(isset($_GET['rdb']) && $_GET['rdb']!=''){
		$selrdb = $connections_array[$_GET['rdb']];
	}else{
		$selrdb = $connections_array['BMW_India'];
	}

	$i=0;
	foreach ( $selrdb as $dbcat => $db) {
		$connection_ip =  $db['host'];
		$port = $db['port'];
		$redis = new Redis();
		if($redis->connect($connection_ip, $port)){
			$ser_info = $redis->info();
			//echo $i;
			//echo '<br />';
			$redis_info['Redis Host'][$connection_ip][$port] = '<strong style="color: #FF0000;">'.$connection_ip.'</strong>';
			$redis_info['Redis Port'][$connection_ip][$port] = '<strong style="color: #FF0000;">'.$port.'</strong>';
			$redis_info['Redis version'][$connection_ip][$port] = $ser_info['redis_version'];
			$redis_info['port'][$connection_ip][$port] = '<strong style="color: #FF0000;">'.$ser_info['tcp_port'].'</strong>';
			$redis_info['Number of client connections'][$connection_ip][$port] = $ser_info['connected_clients'];
			$redis_info['Memory consumed by Redis'][$connection_ip][$port] = $ser_info['used_memory_human'];
			$redis_info['Peak memory consumed by Redis'][$connection_ip][$port] = $ser_info['used_memory_peak_human'];
			$redis_info['Num of changes since the last dump'][$connection_ip][$port] = $ser_info['rdb_changes_since_last_save'];
			$redis_info['Last successful RDB save'][$connection_ip][$port] = date("Y-m-d H:i:s",( $ser_info['rdb_last_save_time']));
			$redis_info['Flag indicating a RDB save is on-going'][$connection_ip][$port] = $ser_info['rdb_bgsave_in_progress'];
			$redis_info['Status of the last RDB save operation'][$connection_ip][$port] = $ser_info['rdb_last_bgsave_status'];
			$redis_info['Duration of the last RDB save operation in seconds'][$connection_ip][$port] = $ser_info['rdb_last_bgsave_time_sec'];
			$redis_info['Duration of the on-going RDB save operation if any'][$connection_ip][$port] = $ser_info['rdb_current_bgsave_time_sec'];
			$redis_info['Num of commands processed per second'][$connection_ip][$port] = $ser_info['instantaneous_ops_per_sec'];
			$redis_info['Instantaneous input'][$connection_ip][$port] = $ser_info['instantaneous_input_kbps'];
			$redis_info['Instantaneous output'][$connection_ip][$port] = $ser_info['instantaneous_output_kbps'];
			$redis_info['Role'][$connection_ip][$port] = $ser_info['role'];
			$redis_info['Connected slaves'][$connection_ip][$port] = $ser_info['connected_slaves'];
			if($ser_info['role'] == 'slave'){ 
				$redis_info['Master host'][$connection_ip][$port] = $ser_info['master_host'];
				$redis_info['Master port'][$connection_ip][$port] = $ser_info['master_port'];
				$redis_info['Master link status'][$connection_ip][$port] = $ser_info['master_link_status'];
				$redis_info['Master last io seconds ago'][$connection_ip][$port] = $ser_info['master_last_io_seconds_ago'];
				$redis_info['Master sync in progress'][$connection_ip][$port] = $ser_info['master_sync_in_progress'];
				$redis_info['Number of seconds since the link is down'][$connection_ip][$port] = $ser_info['master_link_down_since_seconds'];
			}else{
				$redis_info['Master host'][$connection_ip][$port] = '';
				$redis_info['Master port'][$connection_ip][$port] = '';
				$redis_info['Master link status'][$connection_ip][$port] = '';
				$redis_info['Master last io seconds ago'][$connection_ip][$port] = '';
				$redis_info['Master sync in progress'][$connection_ip][$port] = '';
				$redis_info['Number of seconds since the link is down'][$connection_ip][$port] = '';
			}
			$databases = $redis->info('keyspace');

			$db_cnt = array();
			foreach($databases as $db => $data){
				$dbdata = array();
				$dbdata = explode(',',$data);
				$db_cnt[] = str_replace("db","",$db);
				$redis_info[$db][$connection_ip][$port] = $dbdata[0];
			}
			$i++;
		} else {
			echo '<h5 style="text-align:center;">Redis Not Connected</h5>';
		}
	}
?>
<!DOCTYPE html>
<html>
<head>
<title>BMW India</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" type="image/png" href="https://images.cartrade.com/images4/favicon.png">
<style type="text/css">
	body {font-size:11px; font-family:Arial; color:#333333;}
</style>
</head>
<body>	
	
	<table width="100%" border="0" align="center" cellpadding="5" cellspacing="0">		
		<tr>
			<td align="center" colspan="8">
				<h1>BMW India Common Redis DB Overview</h1>
			</td>
		</tr>
		<tr>
			<td align="center" colspan="8" bgcolor="#f5f5f0">
				<ul style=" display:table-row;">
				<?php foreach ($connections_array as $dbcat => $db) { ?>
				<li style=" float: left;  display: table-cell;  list-style: none; padding: 5px;"><a href="?rdb=<?php echo $dbcat ?>"><strong><?php echo $dbcat ?></strong></a></li>
				<?php }  ?>
				</ul>
			</td>
		</tr>
		<tr>
			<td align="center" colspan="8">&nbsp;</td>
		</tr>
		<tr>
			<td align="center" colspan="8">
				<table width="100%" border="1" align="center" cellpadding="5" cellspacing="0">
		<?php
			foreach ($redis_info as $key => $value) {
				//echo $key ;
				//echo '<br >';
				$db_detail = "";
				if(isset($db_details[$key])){
					$db_detail = " <b>[".$db_details[$key]."]</b>";
				}
				echo '<tr>
				<th align="left">';
				echo $key ;
				echo '</th>';
				foreach ($value as $ky => $val) {					
					foreach ($val as $k => $vl) {
						echo '<td>';
						echo $vl.$db_detail;
						echo '</td>';
					}
				}
				echo '</tr>';				
			}
		?>
			</table>
		</td>
	</tr>
	</table>
</body>
</html>