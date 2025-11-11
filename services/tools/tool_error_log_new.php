<?php
include('config.php');


function getfile_e($st){
	exec('ls /var/log/apache2/'.$st.'* -frt 	| tail -1',$output);
	return trim($output[0]);
}

function getfile_a($st){	
	exec('ls /var/log/apache2/'.$st.'* -frt 	| tail -1',$output);
	return trim($output[0]);
}

$selectedDomain = isset($_GET['domain']) ? $_GET['domain'] : '';
$logType = isset($_GET['log_type']) ? $_GET['log_type'] : ''; // Can be 'access', 'error', or 'tools'

echo "<pre>";
if($_SERVER['HTTP_HOST'] == "dms.jlr.local"){
	$vhosts_error = array(		
	"dms.jlr.local"=>"/data/vhosts/archive/logs/dms.jlr.local/error.log"
	);

	$vhosts_access = array(
	"dms.jlr.local"=>"/data/vhosts/archive/logs/dms.jlr.local/access.log"
	);
}else{
	if($env_server == "dev"){
		$vhosts_error = array(		
		"jlr-udms.cartrade.com"=>"/var/log/apache2/jlr-udms.cartrade.com.error.log"
		);
	
		$vhosts_access = array(
		"jlr-udms.cartrade.com"=>"/var/log/apache2/jlr-udms.cartrade.com.access.log"
		);
	}
	
	if( $env_server == "uat"){
		$vhosts_error = array(		
		"jlr-udms.cartrade.com"=>"/var/log/apache2/jlr-udms.cartrade.com.error.log"
		);
	
		$vhosts_access = array(
		"jlr-udms.cartrade.com"=>"/var/log/apache2/jlr-udms.cartrade.com.access.log"
		);
	}
	
	if( $env_server == "prod"){
		$vhosts_error = array(		
		"jlr-udms.cartrade.com"=>"/var/log/apache2/jlr-udms.cartrade.com.error.log"
		);
	
		$vhosts_access = array(
		"jlr-udms.cartrade.com"=>"/var/log/apache2/jlr-udms.cartrade.com.access.log"
		);
	}
}
 
if($_REQUEST['file'] == 'error_log'){
	$vhosts = $vhosts_error;	
	$rre = "/^\[([a-z]{3}) ([a-z]{3}) ([0-9]{2}) ([0-9]{2}\:[0-9]{2}\:[0-9]{2})\.[0-9]{6} ([0-9]{4})\] \[(.*?)\] \[(.*?)\] (\[(.*?)\])?(.*)/ism";
	$col = 4;
}else{
	$vhosts = $vhosts_access;
	$rre = "~(?P<ipaddress>\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3})(.*?)\\[(.*)] ((\\\"(GET|POST) )(?P<url>.+)) (?P<statuscode>\\d{3}) (?P<bytessent>\\d+) ([\"](?P<refferer>(\\-)|(.+))[\"]) ([\"](?P<useragent>.+)[\"])~mis"; 
	$col = 9;
}
$sty = 'line-height:12px;';
if(!$_REQUEST['file']){
	$sty = "padding: 1px;line-height:30px;";
}
$vm = array("Jan"=>"01","Feb"=>"02", "Mar"=>"03", "Apr"=>"04", "May"=>"05", "Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09", "Oct"=>"10", "Nov"=>"11", "Dec"=>"12");
?>
<!DOCTYPE html>
<html>
<head>
<title><?=$_SERVER['HTTP_HOST']?></title>
<meta name="robots" content="noindex, nofollow">
<style type="text/css">
	body {font-size:12px; font-family:Arial; color:#333333; background-color:#f5f4f4;}
	.divclass{width:400px; overflow:hidden;white-space:nowrap;}
</style>
<style>
	.ss{
		position:absolute;
		overflow:visible;
		padding:5px;
		margin-top:-10px;
		background-color:white;
		white-space:normal !important;
		border:1px solid #cdcdcd;
	}
</style>
<script language="javascript">
	function showit( event, obj ){
		try{
			obj.className = 'ss';
		}catch(e){  }
	}
	function hideit(event,obj){
		obj.className = 'divclass';
	}
	function doitt(){
		vs = document.getElementsByTagName( "div" );
		for( i in vs ){
			if( vs[i].className == 'divclass' ){
				vs[i].style.cursor='pointer';
				vs[i].onmouseover = function (event){ showit(event,this); };
				vs[i].onmouseout = function (event){ hideit(event,this); };
			}
		}
	}
	setTimeout("doitt()",500);
</script>
</head>
<body>

	<div style="text-align:center; margin-top:20px; width:100%;">
		<img src="https://jlr-udms.cartrade.com/assets/images/udms-logo.png" />
		<center><h3>JLR Access and Error Log's</h3></center>
	</div>
	<?php if( !$_GET['vhost'] ){ ?>

		<table cellpadding='5' cellspacing='1' bgcolor='#cdcdcd' style="<?=$sty?>" align="center" width="600px">
		<?php foreach ((array) $vhosts as $i=>$j ){ ?>
		<tr bgcolor=white>
			<td><?=$i ?></td><td><a  href="?vhost=<?=$i ?>&file=access_log" >Access Log</a></td><td><a href="?vhost=<?=$i ?>&file=error_log" >Error Log</a></td>
		</tr>
		<?php } ?>
		</table>
	<?php 
	}else{ 
		$vhost_file = $vhosts[ $_GET['vhost'] ];
		
		echo "<div>" . $vhost_file . "</div>";
		//echo "<div>" . filesize( $vhost_file ). " .... </div>";
		//echo file_get_contents($vhost_file);		
		//exit;
		//var_dump(is_file($vhost_file));
		//echo '<br>';

		$fp = fopen($vhost_file, "r");
        if ($fp !== false) {
            $file_size = filesize($vhost_file);
            if ($file_size > 30000000) {
                fseek($fp, $file_size - 30000000);
                $data = fread($fp, $file_size);
            } else {
                if ($file_size > 0) {
                    $data = fread($fp, $file_size);
                }
            }
            fclose($fp);
        } else {
            echo "<div style='color:red;'>Unable to open log file: $vhost_file</div>";
            $data = '';
        }
		
		//print_r( $data ).'....';

		$lines = explode( "\n", $data );
		//print_r($lines);
		unset( $data );


		// $fp = fopen($vhost_file, 'r');
		// $pos = -2; // Skip final new line character (Set to -1 if not present)
		// $lines = array();
		// $currentLine = '';
		// while (-1 !== fseek($fp, $pos, SEEK_END)) {
		//     $char = fgetc($fp);
		//     if (PHP_EOL == $char) {
		//             $lines[] = $currentLine;
		//             $currentLine = '';
		//     } else {
		//             $currentLine = $char . $currentLine;
		//     }
		//     $pos--;
		// }
		// $lines[] = $currentLine; // Grab 

		echo "<table cellpadding='5' cellspacing='1' bgcolor='#cdcdcd' align=\"center\">";
		if($_REQUEST['file'] == 'access_log') {
			echo "<tr bgcolor='#f8f8f8' ><td>Ip address</td><td>User</td><td>Datetime</td><td>Type</td><td>Url</td><td>Status Code</td><td>Bytes sent</td><td>referrer</td><td>useragent</td></tr>";	
		} else {
			//echo "<tr bgcolor='#f8f8f8' ><td>Date</td><td>Error</td><td>IP</td><td>Details</td></tr>";
			echo '<tr class="table_head">					
					<th>Datetime</th>
					<th>Type</th>
					<th>Pid</th> 
					<th>Client</th>
					<th>Message</th>
					</tr>';	
		}
		
		$cnt = 0;
		for( $i=sizeof($lines)-1; $i>0; $i-- ){
			$j = $lines[ $i ];			
			if( !preg_match( "/(file does not exist)/i", $j ) ){
				preg_match( $rre, $j, $m );
				// print_r($m);
				// echo '<br>';
				// echo '<br>';
				if( $m ){	
					if($_REQUEST['file'] == 'access_log') {

						echo "<tr bgcolor='white'><td>" . $m['ipaddress'] . "</td><td>" . str_replace('-','',$m[2]) . "</td><td>" . $m[3]  . "</td><td>".$m[6]."</td><td><div class='divclass'>" . $m['url'] . "</div></td><td>" . $m['statuscode'] . "</td><td>" . $m['bytessent'] . "</td><td><div class='divclass'>" . $m['refferer'] . "</div></td><td><div class='divclass'>" . $m['useragent'] . "</div></td></tr>";
					}else{
						$ddate = $m[5] . "-" . $vm[$m[2]] . "-" . $m[3] . " " . $m[4];
						$dddate = $m[5] . "_" . $vm[$m[2]] . "_" . $m[3];
						$type = $m[6];
						$pid = $m[7];					
						$client = $m[9];
						$error = $m[10];
						echo "<tr bgcolor='white'><td>" . $ddate . "</td><td>" . $type . "</td><td>" . $pid . "</td><td>" . $client . "</td><td>" . $error . "</td></tr>";
						//echo "<tr bgcolor='white'><td nowrap >" . $m[1] . "</td><td>" . $m[2] . "</td><td nowrap >" . str_replace( "client ", "", $m[3] ) . "</td><td>" . $m[4] . "</td></tr>";
					}
				}else{					
					//echo "<tr bgcolor='white'><td nowrap colspan=".$col.">" . $lines[ $i ] . "</td>";					
				}

				$cnt++;
				if( $cnt > 1500 ){
					break;
				}
			}
		}
		echo "</table>";
 	} 
?>
</body>
</html>