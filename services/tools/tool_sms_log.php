<h1> SMS Otp Log - Under Develpment</h1>
<?php
exit;
include($_SERVER['DOCUMENT_ROOT']."/tools_india/eli_config.php");

  	$query = "select count(*) as cnt from  sms_bps_log order by id desc "  ;     
	$res = mysqli_query( $connection , $query );
	if( mysqli_error( $connection ) ){
	    echo "Error : " . mysqli_error( $connection );
	    exit;
	}

	$row = mysqli_fetch_assoc( $res ); 
	$totalrecords = $row["cnt"] ; 

	$viewperpage = 50 ;
	$pages = ceil( $totalrecords/$viewperpage ) ;
	$display_page_number = $_GET["p"];
	if( $display_page_number == ""){
		$display_page_number = 1 ;
	}
	$start =(($display_page_number-1)*$viewperpage) ;
	$limit = "limit " . $start . ", "  . $viewperpage;

	$query = "select * from sms_bps_log order by id desc ". $limit ;	
	$result = mysqli_query( $connection , $query );
	if(mysqli_error($connection)){
			echo mysqli_error( $connection );
			echo "<div>" .$query . "</div>";
			exit;
	}
	$total = mysqli_num_rows( $res );
	while( $row = mysqli_fetch_assoc( $result ) ){
			$records[] = $row ; 
	}    
?>
<!DOCTYPE html>
<html>
<head> 
<title>BMW India SMS Log</title>
<meta name="robots" content="noindex, nofollow">
<style>
body {font-size:11px; font-family:Arial; color:#333333;}   		
.container{	width:900px;height:auto;}								 					
.data-table{ font-size:11px; background: lightgray; border-collapse: separate; border-spacing:1px; }
tr{ background-color:white; }
td{word-break: break-all}
.table_head{ background:whitesmoke;}
a {text-decoration:none}
.pull-right{width: 80%; float: left;}
</style>
</head>
<?php
$host = "india";
$hosts = array(
        "India SMS Log"=>"india",
        );

if(!empty($_REQUEST['host'])){
    $host = $_POST?$hosts[$_REQUEST['host']]:$hosts[$_REQUEST['host']];
}

?>
<body>
<center>
<div class="container" style="width:100%!important;">        
	<h1> BMW India SMS Log  </h1>
	<div class="pull-right">
 <div style="width: 100%; margin-top:-40px">
<?php if(!$_POST){ ?>
<table cellpadding="4" cellspacing="0" border="0" width="98%" align="center">
    <tr>   
        <td align="right" width="20%">
            <select name="host" id="" onchange="location.href='?host='+this.value" >
                <?php foreach ($hosts as $hoid => $hoval) { ?>
                    <option <?=$_REQUEST['host']==$hoval?'selected':''?> value="<?=$hoval?>"><?=$hoid?></option>                    
                <?php } ?>
            </select>
        </td>
        <td align="left" width="40%"><?=$host?></td>
    </tr>
</table>
<?php } ?>
    </div>
</div>        
	<div>
		<div>
			<form>
			         <div>
				<?php			
	 			//include($_SERVER['DOCUMENT_ROOT']."display_menu.php");?>
				</div><br>
				<table class='data-table' cellpadding="5" cellspacing="1" style="background-color:#cdcdcd" width="90%" align='center'>
					<tr>   					  
						<td style="backgound-color:white">Displaying : <?= $start+1 ?> to <?=$pages==$display_page_number?$totalrecords :( $start+$viewperpage) ?> of <?= $totalrecords?></td> 
						<td style="backgound-color:white;">
							<?php if($display_page_number>1){ ?><a href='?p=1'>First&nbsp;|&nbsp;</a><?php } ?>
							<?php if( $display_page_number <= $pages && $display_page_number > 1 ){ ?><a href='?p=<?= $display_page_number-1 ?>'> Prev &nbsp;|&nbsp;</a><?php } ?>
							<?php if( $display_page_number < $pages ){ ?><a href='?p=<?= $display_page_number+1  ?>' > Next&nbsp;|&nbsp;</a><?php }?>
							<?php if( $display_page_number < $pages ){ ?><a href='?p=<?=$pages ?>'> Last</a><?php } ?> </td>						
					</tr>
				</table> 			      	
			</form>
		</div>
	</div>
  <div style="clear:both"></div>
	<div>
		<?php if(is_array($records ) ){  ?>
		<table class='data-table' cellpadding="5" cellspacing="1" width="90%" align='center'>
			<tr class="table_head">
				<th>Date Time</th>
				<th>Dealer Id</th>
				<th>SMS Recepient</th>
				<th>SMS Content</th>
				<th>Status</th>
				<th>Response</th>
				<th>Sender ID</th>
				<th>Template ID</th>				
				<th>From Page</th>
				<th>From Action</th>
				<th>IP</th>        		
			</tr>
			<?php 			
			foreach( $records as $key=>$data ){ ?>
			<tr>   
				<td style="width:120px;"><?=date('d-M-Y H:i:s',strtotime($data["datetime"]))?></td> 
				<td><?=$data["dealer_id"]?></td>				
				<td><?=$data["mobile"]?></td>				
				<td><?=$data["sms"]?></td>
				<td><?=$data["status"]?></td>				
				<td><?=$data["returncode"]?></td>
				<td><?=$data["senderid"]?></td>
				<td><?=$data["templateid"]?></td>								
				<td><?=$data["frompage"]?></td>
				<td><?=$data["fromaction"]?></td>				
				<td><?=$data["ip"]?></td>				              
			</tr>
			<?php } ?>
			<table cellpadding="5" cellspacing="1" style="background-color:#cdcdcd" width="90%" align='center'>
				<tr>   					  
					<td style="backgound-color:white">Displaying : <?= $start+1 ?> to <?=$pages==$display_page_number?$totalrecords :( $start+$viewperpage) ?> of <?= $totalrecords?></td> 
					<td style="backgound-color:white">
						<?php if($display_page_number>1){ ?><a href='?p=1'>First&nbsp;|&nbsp;</a><?php } ?>
							<?php if( $display_page_number <= $pages && $display_page_number > 1 ){ ?><a href='?p=<?= $display_page_number-1 ?>'> Prev &nbsp;|&nbsp;</a><?php } ?>
							<?php if( $display_page_number < $pages ){ ?><a href='?p=<?= $display_page_number+1  ?>' > Next&nbsp;|&nbsp;</a><?php }?>
							<?php if( $display_page_number < $pages ){ ?><a href='?p=<?=$pages ?>'> Last</a><?php } ?> </td>						
				</tr>
			</table> 
		</table>
		<?php } ?>
	</div>	
</div>
</center>
</body>
</html>