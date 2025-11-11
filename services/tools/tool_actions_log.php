<?php 
include("config.php");
echo "JLR action log present pending :)";
exit;
$actions_log_connection = mysqli_connect( $actions_host, $actions_user, $actions_pass, $actions_db);
if( mysqli_connect_error() ){
	echo "JLR<br>";
    echo "Connection Error!";
    echo "<BR>" . mysqli_connect_error();
    exit;
}
echo "next start";

if( $_GET["action"] == "search" ){
	$cond = " where 1 ";	

	if( $_GET["table_name"] != "" ){
	    $table = $_GET["table_name"] ;
	}
	if( $_GET["domain"] != "" ){
	    $domain = $_GET["domain"] ;
	    $cond .= " and domain = '" . $_GET['domain'] . "' ";
	}
	if( $_GET["server"] != "" ){
	        $server = $_GET["server"] ;
	        $cond .= " and server = '" . $_GET['server'] . "' ";
	}
	if( $_GET["method_name"] != "" ){
	    $method_type = $_GET["method_name"] ;
	    $cond .= " and method = '" . $_GET['method_name'] . "' ";
	}
	if( $_GET["vaction"] != "" ){
	    $action = $_GET["vaction"] ;
	    $cond .= " and action = '" . $_GET['vaction'] . "' ";
	}
	$is_blocked="";
	if( $_GET["is_blocked"] != "" ){
	    $is_blocked = $_GET["is_blocked"] ;
	    $cond .= " and blocked = '" . $_GET['is_blocked'] . "' ";
	}
	if( $_GET["keyword"] != "" && $_GET["exclude"] == ""){
    	
    	$cond .= " and 
    		(	    	         
		url like '%" . trim(mysqli_escape_string($actions_log_connection, $_GET['keyword'])) . "%' or 
		data like '%" . trim(mysqli_escape_string($actions_log_connection, $_GET['keyword'])) . "%' or  
		scriptname like '%" . trim(mysqli_escape_string($actions_log_connection, $_GET['keyword'])) . "%' or     
		ip like '%" . trim(mysqli_escape_string($actions_log_connection, $_GET['keyword']) ). "%'  
		)";
		
	}		
	if($_GET["exclude"] != "" && $_GET["keyword"] != ""){		
		$cond .= " and ip not like '%" . trim(mysqli_escape_string($actions_log_connection, $_GET['keyword']) ). "%' ";
	}
    	
}
	$table = $table?$table: "actions_log_". date("Y_m_d");
	$domain = $domain?$domain:$_GET["domain"];
	$server = $server?$server:$_GET["server"];
    $method_type = $method_type ?$method_type:$_GET["method_name"] ;
	$ip = $ip?$ip:$_GET["ip"];
	
	       
        $query = "select count(*) as cnt from ". $table . $cond  ;     
        echo 'query:: '.$query;
        $res = mysqli_query( $actions_log_connection , $query );
        if( mysqli_error( $actions_log_connection ) ){
            echo "Error : " . mysqli_error( $actions_log_connection );
            exit;
        }

        $row = mysqli_fetch_assoc( $res ); 
        $totalrecords = $row["cnt"] ;
        
        $viewperpage = 100 ;
        $pages = ceil( $totalrecords/$viewperpage ) ;
        $display_page_number = $_GET["p"];
        if( $display_page_number == ""){
        	$display_page_number = 1 ;
        }
        $start =(($display_page_number-1)*$viewperpage) ;
        $limit = "limit " . $start . ", "  . $viewperpage;

	  
        $query = "select * from ". $table. $cond." order by id desc ". $limit ;
 		//echo $query;
        $result = mysqli_query( $actions_log_connection , $query );
        if(mysqli_error($actions_log_connection)){
		echo mysqli_error( $actions_log_connection );
		echo "<div>" .$query . "</div>";
		exit;
	}
        $total = mysqli_num_rows( $res );
        while( $row = mysqli_fetch_assoc( $result ) ){
            $row['data'] = json_decode($row['data'], true);
            if( isset($row['data']['password']) && $row['data']['password'] != "" ){
            	$row['data']['password'] = "";
            }
            if( isset($row['data']['txt_pwd']) && $row['data']['txt_pwd'] != "" ){
            	$row['data']['txt_pwd'] = "";
            }
            if($row['data']){
            	$row['data'] = json_encode($row['data']);
            }else{
            	$row['data'] = "";
            }
            
            $records[] = $row ; 

        }                       
	$tables_list =array();
	
	
	$sql = "select  DISTINCT  server  from ". $table. "  ";
    $res_distinct = mysqli_query( $actions_log_connection , $sql );
    if(mysqli_error($actions_log_connection)){
		echo mysqli_error( $actions_log_connection );
		echo "<div>" .$sql . "</div>";
		exit;
	}
	
	while( $row_distinct = mysqli_fetch_assoc( $res_distinct ) ){ 
	       $server_list[] = $row_distinct['server'] ;  	                    	
	} 
	
	$query = "show tables like 'actions_log_%' " ;
	$res = mysqli_query( $actions_log_connection , $query );
	if(mysqli_error($actions_log_connection)){
		echo mysqli_error( $actions_log_connection );
		echo "<div>" .$query . "</div>";
		exit;
	}
	while( $row = mysqli_fetch_assoc( $res ) ){
	    foreach( $row as $i=>$j ){
	              $tables_list[] = $j ;         
	    }     
	}  

	// echo "<pre>";
	// print_r($records);
	// exit;

?>
<!DOCTYPE html>
<html>
<head> 
<title>Actions logs</title>
<meta name="robots" content="noindex, nofollow">
<style>
body {font-size:11px; font-family:Arial; color:#333333;}   		
.container{	width:900px;height:auto;}								 					
.data-table{ font-size:11px; background: lightgray; border-collapse: separate; border-spacing:1px; }
/*form table { border-collapse: separate; border-spacing:1px;    font-size:12px; width:80%  ; }*/
tr{ background-color:white; }
.table_head{ background:whitesmoke;}
/*.table_head th{ padding:5px; }*/
a {text-decoration:none}
</style>
<script type="text/javascript">
	function data(){
		alert("No data found");
		return false;	
	}
</script>
</head>

<body>
<center>
<div class="container" style="width:100%!important;">        
	<h1> Actions logs  </h1>      
	<div>
		<div>
			<form>
			         <div>
				</div><br><table cellpadding="5" cellspacing="1" width="90%" align='center'>
					<tr>   
						<td><b>Search :</b> 
							<select name="table_name">
								<option value="">Select Table</option>
								<?php foreach( $tables_list as $i=>$name ){ 
									echo "<option value='".$name."' ".( $table==$name?"selected":"$table" ).">".$name."</option>";
								}
								?>
							</select>
							<select name="server">
								<option value="">Select Server</option>
								<?php foreach( $server_list as $i=>$name ){ 
									echo "<option value='".$name."' ".( $server==$name?"selected":"$server" ).">".$name."</option>";
								}
								?>
							</select>
							<!-- <select name="domain">
								<option value="">Select Domain</option>
								<?php 	$sql = "select  DISTINCT  domain  from ". $table. "  ";
								        $res = mysqli_query( $actions_log_connection , $sql );
								        if(mysqli_error($actions_log_connection)){
										echo mysqli_error( $actions_log_connection );
										echo "<div>" .$sql . "</div>";
										exit;
									}
									while( $row = mysqli_fetch_assoc( $res ) ){	                    							
										echo "<option value='".$row['domain']."' ".( $domain==$row['domain']?"selected":"$domain" ).">".$row['domain']."</option>";
									}
								?>
							</select>	 -->						
							<select name="method_name">
								<option value="">Select Method</option>
								<?php 
									$sql = "select  DISTINCT  method  from ". $table. " where method != '' ";
								        $res = mysqli_query( $actions_log_connection , $sql );
								        if(mysqli_error($actions_log_connection)){
										echo mysqli_error( $actions_log_connection );
										echo "<div>" .$sql . "</div>";
										exit;
									}
									while( $row = mysqli_fetch_assoc( $res ) ){	                    							
										echo "<option value='".$row['method']."' ".( $method_type==$row['method']?"selected":"$method_type" ).">".$row['method']."</option>";
									}
								?>
							</select>
							<select name="vaction">
								<option value="">Select Action</option>
								<?php 
										$sql = "select  DISTINCT  action  from ". $table. "  where action != '' order by action";
								        $res = mysqli_query( $actions_log_connection , $sql );
								        if(mysqli_error($actions_log_connection)){
										echo mysqli_error( $actions_log_connection );
										echo "<div>" .$sql . "</div>";
										exit;
									}
									while( $row = mysqli_fetch_assoc( $res ) ){	                    							
										echo "<option value='".$row['action']."' ".( $action==$row['action']?"selected":"$action" ).">".$row['action']."</option>";
									}
								?>
							</select>
							<select name="is_blocked">
								<option value="">Blocked Request</option>
								<option value="0" <?=($is_blocked==='0'?'selected':'')?>>No</option>
								<option value="1" <?=($is_blocked==='1'?'selected':'')?>>Yes</option>
							</select>							
					   	<input type="text" name="keyword" id="searchkey"  value="<?=$_GET["keyword"]?>" placeholder="Keyword S">
					   	<!-- <input name="exclude_ip" type="text" id="exclude_ip" style="display:inline;" value="<?=$_GET["exclude_ip"]?>"  placeholder="Exclude IP"> --> 
					   	<input name="exclude" type="checkbox" id="exclude" style="display:inline;" value="exclude_ip" <?=($_GET["exclude"]=="exclude_ip")?"checked":""?> ><label>Exclude IP</label>				   	
						<input type="submit" name="action" id="searchkey" value="search">
						<a style="text-decoration:none;" href="index.php"><b>Clear</b></a>
						</td>
					</tr>
				</table>

				<table class='data-table' cellpadding="5" cellspacing="1" style="background-color:#cdcdcd" width="90%" align='center'>
					<tr>   					  
						<td style="backgound-color:white">Displaying : <?= $start+1 ?> to <?=$pages==$display_page_number?$totalrecords :( $start+$viewperpage) ?> of <?= $totalrecords?></td> 
						<td style="backgound-color:white">
							<?php if($display_page_number>1){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=1&action=search'>First&nbsp;|&nbsp;</a><?php } ?>
							<?php if( $display_page_number <= $pages && $display_page_number > 1 ){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=<?= $display_page_number-1 ?>&action=search'> Prev &nbsp;|&nbsp;</a><?php } ?>

							<?php if( $display_page_number < $pages ){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=<?= $display_page_number+1  ?>&action=search' > Next&nbsp;|&nbsp;</a><?php }?>
							<?php if( $display_page_number < $pages ){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=<?=$pages ?>&action=search'> Last</a><?php } ?> </td>						
					</tr>
				</table> 
			      	<input type="hidden" value="search" name="action" >
			</form>
		</div>
	</div>
  <div style="clear:both"></div>
	<div>
		<?php if(is_array($records ) ){  ?>
		<table class='data-table' cellpadding="5" cellspacing="1" width="90%" align='center'>
			<tr class="table_head">
				<th>Server</th>
				<tH>Domain</th>
				<th>Date</th>
				<th>Time</th>
				<th>Ip</th>
        			<th>Method</th> 
				<th>Url</th>
        			<th>Data</th>
        			<th>Scriptname</th>
        			<th>Action</th>
        			<th>Blocked</th>
			</tr>
			<?php foreach( $records as $key=>$data ){				  
            		?>
			<tr>   
				<td><?=htmlspecialchars($data["server"])?></td>
		                <td><?=$data["domain"]?></td> 
		                <td style="white-space: nowrap;"><?=$data["date"]?></td>
		                <td><?=$data["time"]?></td>
		                <td><?=$data["ip"]?></td>
		                <td><?=htmlspecialchars($data["method"])?></td>
		                <td><div style='width:150px; height:100px; overflow:auto;' ><?=htmlspecialchars($data["url"])?></div></td>		                
		                <td><div style='width:250px; height:100px; overflow:auto;' ><?=htmlspecialchars($data["data"])?></div></td>
		                <td><?=htmlspecialchars($data["scriptname"])?></td>
		                <td><?=htmlspecialchars($data["action"])?></td>
		                <td><?=$data["blocked"]?"Yes":"No"?></td>
			</tr>
			<?php } ?>
			<table cellpadding="5" cellspacing="1" style="background-color:#cdcdcd" width="90%" align='center'>
				<tr>   					  
					<td style="backgound-color:white">Displaying : <?= $start+1 ?> to <?=$pages==$display_page_number?$totalrecords :( $start+$viewperpage) ?> of <?= $totalrecords?></td> 
					<td style="backgound-color:white">
						<?php if($display_page_number>1){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=1&action=search'>First&nbsp;|&nbsp;</a><?php } ?>
							<?php if( $display_page_number <= $pages && $display_page_number > 1 ){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&action=<?=$_GET['action']?>&method_name=<?=$_GET['method_name']?>&keyword=<?=$_GET["keyword"]?>&p=<?= $display_page_number-1 ?>&action=search'> Prev &nbsp;|&nbsp;</a><?php } ?>

							<?php if( $display_page_number < $pages ){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=<?= $display_page_number+1  ?>&action=search' > Next&nbsp;|&nbsp;</a><?php }?>
							<?php if( $display_page_number < $pages ){ ?><a href='?table_name=<?=$_GET['table_name']?>&server=<?=$_GET['server']?>&domain=<?=$_GET['domain']?>&method_name=<?=$_GET['method_name']?>&action=<?=$_GET['action']?>&keyword=<?=$_GET["keyword"]?>&p=<?=$pages ?>&action=search'> Last</a><?php } ?> </td>						
				</tr>
			</table> 
		</table>
		<?php } else{ 		
			echo '<script type="text/javascript">',
		     'data();',
		     '</script>'
		;  
		 }?>
	</div>	
</div>
</center>
</body>
</html>