<h1> Server Monitor - Under Develpment</h1>
<?php 
exit;
include_once("config.php");
include_once( $_SERVER['DOCUMENT_ROOT']."tools_india/httpclient.class.ver3.php");
$servers_ = array(
	"BMW-INDIA-SERVERS" => array(
        "10.90.5.158"  => array( 
            "info"=>"INDIA Dev",   
            "host"=>"10.90.5.158", 
            "port"=>8080, 
            "url"=>"http://10.90.5.158:8080/server-status",  
            "domains"=>array("dev.bmw-bps.in","dev.bmwusedcars.in","dev.miniusedcars.in") 
        ),

        // "10.90.7.121"  => array( 
        //     "info"=>"INDIA UAT",   
        //     "host"=>"10.90.7.121", 
        //     "port"=>8080, 
        //     "url"=>"http://10.90.7.121:8080/server-status",  
        //     "domains"=>array("uat.bmw-bps.in","uat.bmwusedcars.in","uat.miniusedcars.in") 
        // ),

        // "10.90.8.105"  => array( 
        //     "info"=>"INDIA Web1",   
        //     "host"=>"10.90.8.105", 
        //     "port"=>8080, 
        //     "url"=>"http://10.90.8.105:8080/server-status",  
        //     "domains"=>array("www.bmw-bps.in","www.bmwusedcars.in","www.miniusedcars.in") 
        // ),

        // "10.90.8.198"  => array( 
        //     "info"=>"INDIA Web2",   
        //     "host"=>"10.90.8.198", 
        //     "port"=>8080, 
        //     "url"=>"http://10.90.8.198:8080/server-status",  
        //     "domains"=>array("www.bmw-bps.in","www.bmwusedcars.in","www.miniusedcars.in") 
        // ),


	),
);

if( !$_GET['ip'] ){
    $_GET['ip'] = "10.90.5.158";
}
if( !$_GET['server'] ){
    $_GET['server'] = "BMW-INDIA-SERVERS";
}

$v = $servers_[$_GET['server']][ $_GET['ip'] ];
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>BMW MY Server Status</title>
<meta name="robots" content="noindex, nofollow">

<style type="text/css">
<!--
/*body{ font-family: Arial, Helvetica, sans-serif;font-size: 12px;color: #000000;}
body {background-color: #F7F7F7;margin-left: 0px;margin-top: 0px;margin-right: 0px;margin-bottom: 15px;}
div{margin-left:50px;;}
h1,h2,h3{margin:0px;padding:5px;}
.wapper{padding:5px;width:910px;}*/
body {font-size:12px; font-family:Arial; color:#333333;}
h3{ margin: 0px;padding: 0px; font-size: 16px; }
h1{margin: 0px;padding: 0px;}
-->
</style>
</head>

<body>
<div style="text-align:center; margin-top:10px; width:100%;">
    <center><h3>Apache Server Status</h3></center>
</div>
<hr />
<div class="wapper">

<table border="0" cellpadding="5" cellspacing="0" width="100%" align="left">  
    <tr>
        <td align="left" valign="top" width="50%">
            <table cellpadding="5" cellspacing="0" border="1" align="left" width="100% ">
            <?php
            $tabl = 1;
            $di = ceil(count($servers_)/2);
            $firstcol = '';
            $secondcol = '';
            foreach( $servers_ as $server => $serverips ){
                echo '<tr>';
                echo '<th align="left">'.$server.'</th><td align="left">'; 
                foreach ($serverips as $ip => $info) {
                    echo '<a href="?ip='.$ip.'&server='.$server.'">'.$ip.'</a>&nbsp;&nbsp;&nbsp;';
                }
                echo '</td></tr>';
            }                
            ?>
            </table>
        </td>
        <td align="left"  valign="top" width="50%">
            <strong>Domains List of <?php echo $v['host']; ?>:</strong><br> 
            <table cellpadding="5" cellspacing="1" border="0" align="left" width="100%">
                <tr>
                    <?php
                    $tabl = 1;
                    $di = ceil(count($v['domains'])/2);
                    foreach( $v['domains'] as $domain ){
                        echo '<td align="left">'.$domain.'</td>';
                        if($tabl % 3 === 0){
                            echo '</tr><tr>';
                            $tabl = 1;
                        } else {
                            $tabl++;
                        }            
                    }
                    ?>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br><br>
<?php
//print_r( $v );
if( !$v ){
	echo "<div>Server Details not found</div>";
}else{
	$con = new HttpClient($v['host'],$v['port']);
        $con->port = $v['port'];
        $con->debug = false;
        $con->get( $v['url']);
        //echo '<table border="0" cellpadding="0" cellspacing="0" width="100%" align="left">';
        //echo '<tr><td align="left">Server: ' . $v['info'] . '</td></tr>';
        //echo '<tr><td align="left">Calling URL: ' . $v['url'] . '</td></tr>';
        //echo '</table>';
        $response_status = $con->getStatus();		
	$response_data = $con->getContent();
        if($con->debug){
		echo "<pre>";
        echo "I'm in this block";
		print_r($request);
		echo "</pre>";
	}
    $srequestlist = array();
    if($response_status == 200){
        if( $response_data == "" ){
            echo "empty";
            if( $con->debug ){
                echo "<div>Response Empty!</pre></div>";
            }
            $con->error = "API Response Empty!";
            $con->status = "Failed";
            error_log("api_server_monitor.php connection error: ".$con->error);
            exit;
        }else{
            preg_match_all("/<dt>Current Time: (.*?)<\/dt>/ism", $response_data , $ctime);
            preg_match_all("/<dt>Server load: (.*?)<\/dt>/ism", $response_data , $ctload);
            preg_match_all("/<dt>CPU Usage: (.*?) - (.*?) CPU load<\/dt>/ism", $response_data , $cpus);
            if(preg_match_all("/<dt>([0-9].*?)\\srequests\\/sec\\s+-\\s+(.*?)\\s-\\s(.*?)\\skB\\/request<\\/dt>/ism", $response_data , $cpurequests)){
            }else if(preg_match_all("/<dt>([0-9].*?)\\srequests\\/sec\\s+-\\s+(.*?)\\s-\\s(.*?)\\sB\\/request<\\/dt>/ism", $response_data , $cpurequests)){
            }

            $re = '/<tr><td><b>(.*?)<\/b><\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)\N
            <\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)\N
            <\/td><td>(.*?)<\/td><td nowrap=\"\">(.*?)<\/td><td nowrap=\"\">(.*?)<\/td><\/tr>/ism';
            preg_match_all('/<tr><td><b>(.*?)<\/b><\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td>(.*?)<\/td><td nowrap>(.*?)<\/td><td nowrap>(.*?)<\/td><\/tr>/ism', $response_data , $srequestlist);
        }
    }else{
        echo "ERROR ";
        echo $con->status;
        echo $con->details;
        error_log("api_server_monitor.php connection error: ".$con->error);
        exit;	
    }
}
?>


<table border="0" cellpadding="5" cellspacing="0" width="100%" align="left">
	<tr>
    	<td align="left"><h1>Apache Server Status for <?php echo $v['info']; ?></h1></td>
    </tr>
    <tr>
        <td align="left"><a href="<?php echo $_SERVER['REQUEST_URI'];?>&view=showall">Click here</a> to view all processes</td>
    </tr>
    <tr>
    	<td align="left"><strong>Current Time:</strong> <?php echo $ctime[1][0]; ?></td>
    </tr>
    <tr>
    	<td align="left" style="color: #FF0000;"><strong>Server load:</strong> <?php echo $ctload[1][0]; ?></td>
    </tr>
    <tr>
    	<td align="left"><strong>CPU load:</strong> <?php echo $cpus[2][0]; ?></td>
    </tr>
    <tr>
    	<td align="left"><strong>Requests/sec:</strong> <?php echo $cpurequests[1][0]; ?></td>
    </tr>
    <tr>
    	<td align="left"><strong>Requests/KB:</strong> <?php echo $cpurequests[3][0]; ?></td>
    </tr>
    
    <tr>
    	<td align="left">
            <table border="0" cellpadding="5" cellspacing="0" width="100%" style="margin:0px; border:1px #999999 solid;">
                <tr bgcolor="#999999">
                    <th>Srv</th>
                    <th>PID</th>
                    <th>Acc</th>
                    <th>M</th>
                    <th>CPU</th>
                    <th>SS</th>
                    <th>Req</th>
                    <th>Conn</th>
                    <th>Child</th>
                    <th>Slot</th>
                    <th>Client</th>
                    <th>VHost</th>
                    <th>Request</th>
                </tr>
            	<?php for($i=0;$i<count($srequestlist[1]);$i++){ ?>

                    <?php if(isset($_GET['view']) && $_GET['view'] == 'showall'){?>	
                        <?php if( trim($srequestlist[6][$i]) > 30){ ?>
                        <tr style="color: #FF0000 !important;">
                        <?php  } else { ?>
                        <tr>
                        <?php  } ?>
                    <?php  
                        } else { 
                        
                     if(trim($srequestlist[4][$i]) == '_' || trim($srequestlist[4][$i]) == '.'){ continue; } ?>
                    <tr>
                    <?php } ?>
                        <td nowrap><b><?php echo $srequestlist[1][$i] ?></b></td>
                        <td><?php echo $srequestlist[2][$i] ?></td>
                        <td><?php echo $srequestlist[3][$i] ?></td>
                        <td><b><?php echo $srequestlist[4][$i] ?></b></td>
                        <td><?php echo $srequestlist[5][$i] ?></td>
                        <td><?php echo $srequestlist[6][$i] ?></td>
                        <td><?php echo $srequestlist[7][$i] ?></td>
                        <td><?php echo $srequestlist[8][$i] ?></td>
                        <td><?php echo $srequestlist[9][$i] ?></td>
                        <td><?php echo $srequestlist[10][$i] ?></td>
                        <td><?php echo $srequestlist[11][$i] ?></td>
                        <td nowrap><?php echo $srequestlist[12][$i] ?></td>
                        <td nowrap><?php echo $srequestlist[13][$i] ?></td>
                    </tr>                   
                    
                <?php } ?>
            </table>
        </td>
    </tr>
</table>
<br>
<hr />
<br>
<table border="0" cellpadding="5" cellspacing="0" width="900px" align="left">
    <tr>
        <td align="left" width="50%" valign="top">           

            <table border="0" cellpadding="5" cellspacing="0" width="100%" style="margin:0px;">
                <tr>
                    <td align="left"><h2>Scoreboard Key:</h2></td>
                </tr>
                <tr>	
                    <td align="left">
                        "<b><code>_</code></b>" Waiting for Connection,<br> 
                        "<b><code>S</code></b>" Starting up,<br> 
                        "<b><code>R</code></b>" Reading Request,<br>
                        "<b><code>W</code></b>" Sending Reply,<br> 
                        "<b><code>K</code></b>" Keepalive (read),<br> 
                        "<b><code>D</code></b>" DNS Lookup,<br>
                        "<b><code>C</code></b>" Closing connection,<br> 
                        "<b><code>L</code></b>" Logging,<br> 
                        "<b><code>G</code></b>" Gracefully finishing,<br> 
                        "<b><code>I</code></b>" Idle cleanup of worker,<br> 
                        "<b><code>.</code></b>" Open slot with no current process<br>
                    </td>
                </tr>
            </table>
        </td>
        <td align="left" width="50%" valign="top">
            <table border="0" cellpadding="5" cellspacing="0" width="100%" style="margin:0px;">
            <tr>
                <th>Srv</th>
                <td>Child Server number - generation</td>
              </tr>
            <tr>
                <th>PID</th>
                <td>OS process ID</td>
              </tr>
            <tr>
                <th>Acc</th>
                <td>Number of accesses this connection / this child / this slot</td>
              </tr>
            <tr>
                <th>M</th>
                <td>Mode of operation</td>
              </tr>
            <tr>
                <th>CPU</th>
                <td>CPU usage, number of seconds</td>
              </tr>
            <tr>
                <th>SS</th>
                <td>Seconds since beginning of most recent request</td>
              </tr>
            <tr>
                <th>Req</th>
                <td>Milliseconds required to process most recent request</td>
              </tr>
            <tr>
                <th>Conn</th>
                <td>Kilobytes transferred this connection</td>
              </tr>
            <tr>
                <th>Child</th>
                <td>Megabytes transferred this child</td>
              </tr>
            <tr>
                <th>Slot</th>
                <td>Total megabytes transferred this slot</td>
              </tr>
          </table>
        </td>
    </tr>
</table>
</div>
</body>
</html>
