<?php 
include('config.php');

$cond = " where 1  ";  
$_GET['mobile_action'] = isset($_GET['mobile_action'])?$_GET['mobile_action']:"";
$_GET['keyword'] = isset($_GET['keyword'])?$_GET['keyword']:"";
$_GET['status'] = isset($_GET['status'])?$_GET['status']:"";
$_GET['apiid'] = isset($_GET['apiid'])?$_GET['apiid']:"";
$_GET['ip'] = isset($_GET['ip'])?$_GET['ip']:"";
$_GET['page_source'] = isset($_GET['page_source'])?$_GET['page_source']:"";
if( $_GET['mobile_action'] ){
    $cond .= " and action = '" . ($_GET['mobile_action']=="rewrite"?"":$_GET['mobile_action']) . "' ";
}
if( $_GET['keyword'] ){
    $cond .= " and url like '%" . ($_GET['keyword']=="rewrite"?"":$_GET['keyword']) . "%' ";
}
if( $_GET['status'] ){
    $cond .= " and `status` = '" . $_GET['status'] . "' ";
}
if( $_GET['apiid'] ){
    $cond .= " and `device_id` = '" . $_GET['apiid'] . "' ";
}
if( $_GET['ip'] ){
    $cond .= " and `IP` = '" . $_GET['ip'] . "' ";
}
if( $_GET['page_source'] ){
    $cond .= " and `page_source` = '" . $_GET['page_source'] . "' ";
}
//$cond .= " and `from_server` = '1' ";
 $_GET['mobileapilog'] = isset($_GET['mobileapilog'])?$_GET['mobileapilog']:"";
if($_GET['mobileapilog']!='')
{
    $tablenamelist=$_GET['mobileapilog'];
}else{
    $tablenamelist='api_req_resp_logs_'.date('Y').'_'.date('m').'_'.date('d');
}
//echo $tablenamelist;


$test_query = "SELECT table_schema,table_name FROM information_schema.TABLES WHERE `TABLE_SCHEMA` ='$mysql_db' AND table_name LIKE  'api_req_resp_logs_%' ORDER BY table_name DESC";


$test_res = mysqli_query( $connection, $test_query ); 
//is_error($test_query); 
while( $row =mysqli_fetch_assoc( $test_res ) ){ 
     //print_r($row);
    $tableslist[]=$row['table_name'];
}   
// print_r($tableslist);
// exit;
$_GET['page'] = isset($_GET['page'])?$_GET['page']:"";
if($_GET['page']){ $start = $_GET['page']; }else{ $start =1; }
$_GET['id'] = isset($_GET['id'])?$_GET['id']:"";
if($_GET['id']){
    $query_comment = isset($query_comment)?$query_comment:"";
    $log_q = 'select * from `'.$tablenamelist.'` where log_id='.$_GET['id']." $query_comment ";
    $res_log = mysqli_query( $connection, $log_q); 
     //is_error($log_q); 
}else{
//$cond .= "and from_server=1";
$query_comment = isset($query_comment)?$query_comment:"";
$log_q = 'select count(*) as cnt from `'.$tablenamelist.'` '.$cond." $query_comment "; 
$res_total = mysqli_query( $connection, $log_q);  
//is_error($log_q);
$sql_error = mysqli_error( $connection );
if($res_total){
$count = mysqli_fetch_array($res_total);
$count = $count['cnt'];
$last = ceil($count/150);
$range = ($start-1) *150;
$q =  'select * from `'.$tablenamelist.'` '.$cond.' order by log_id desc limit '.$range. ' , 150 '.$query_comment.' ';
$res = mysqli_query( $connection, $q);
//is_error($q);  
}
} 
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Request Log</title>
<style type="text/css">
body{ font-size:14px; font-family: 'robotoregular' !important;background:#ffffff !important;} 
body, html, ul, li, p, h1, h2, h3, h4, h5, h6 {margin: 0px;padding: 0px;font-weight: normal;color: #262626;}
.tab_cls, .tab_cls td, .tab_cls th{border:1px solid black;height:22px;padding-left:5px;padding-right:4px;}
.sub_tab, .sub_tab td, .sub_tab th{border:1px solid black;width:100%;height:22px;padding-left:5px;padding-right:4px;}
.field{ width:35%!important;}
.tab_cls , .sub_tab , .pagination{border-collapse:collapse!important;}
table {margin:0px;padding:0px;}
.desc{text-align:left;margin-left:200px;line-height:10px;}
.texArLeg{
    width: 1100px; 
    height: 300px;
}
</style>
</head>
<body><center>
<div class="container" style="width:100%!important;">

    <h1>API Request & Response Logs</h1>
  <div style="margin:5px;">
<form method="get" >
<table cellpadding="5" cellspacing="1" bgcolor="#cdcdcd" width="100%" align='left'>
    <tr bgcolor="white">
        <td align="left">
        <strong>Status:</strong> <select name="status" id="status">
            <option value="" >All</option>
            <option value="200" <?=$_GET['status']=="200"?"selected":"" ?>  >200</option>
            <option value="302" <?=$_GET['status']=="205"?"selected":"" ?> >305</option>
            <option value="304" <?=$_GET['status']=="210"?"selected":"" ?> >400</option>
            <option value="301" <?=$_GET['status']=="101"?"selected":"" ?> >403</option>
            <option value="103" <?=$_GET['status']=="103"?"selected":"" ?> >103</option>
            <option value="305" <?=$_GET['status']=="305"?"selected":"" ?> >500</option>
        </select>&nbsp;
        <strong>Action:</strong> 
    <input name="mobile_action" id="mobile_action" value="<?=$_GET['mobile_action'] ?>" >
        &nbsp;
        <strong>IP: </strong> <input name="ip" id="ip" value="<?=$_GET['ip'] ?>" >
        &nbsp;
        <strong>Date</strong> 
         <select name="mobileapilog">
             <?php 
         for($i=0;$i<count($tableslist);$i++){
            $_REQUEST['mobileapilog'] = isset($_REQUEST['mobileapilog'])?$_REQUEST['mobileapilog']:"";
        if(!preg_match("/mobile_api_log_rec|mypage/",$tableslist[$i])){
            ?>
            <option value="<?php echo $tableslist[$i] ?>" <?php echo ($_REQUEST['mobileapilog'] == $tableslist[$i])? 'selected="selected"': ''; ?>><?php echo $tableslist[$i] ?></option>
                <?php 
            
        } 
         }
         ?>
         </select>
        &nbsp;
        <strong>DeviceId:</strong> <input name="apiid" id="apiid" value="<?=$_GET['apiid'] ?>" >
        </td>
        
        <td align="right">
        <input type="submit" name="submit" value="go" >
        </td>
    </tr>
</table>
</form>
</div>
<div style="clear:both;"></div>
    <?php if($_GET['id']){ 
             $row = mysqli_fetch_assoc( $res_log ); 
             extract($row);
             $data = explode('&' , $postdata);?>
             <table class='sub_tab'>
                <tr>
                    <td class='field'>ID</td>
                    <td><?= $log_id; ?></td>
                </tr>
                <tr>
                    <td class='field'>DeviceId</td>
                    <td><?= $device_id; ?></td>
                </tr>
                <tr>
                    <td class='field'>Action</td>
                    <td><?= $action; ?></td>
                </tr>
                <tr>
                    <td class='field'>Date</td>
                    <td><?= $c_date; ?></td>
                </tr>
               
                <tr >
                    <td class='field'>IP</td>
                    <td><?=$ip; ?></td>
                </tr>
              
                <tr>
                    <td class='field'>URL</td>
                    <td><?=$url; ?></td>
                </tr>
                <tr>
                    <td class='field'>Status</td>
                    <td><?=$status; ?></td>
                </tr>
                <tr>
                    <td class='field'>Request</td>
                    <td><textarea class="texArLeg"><?php  echo  json_encode(json_decode($original_request), JSON_PRETTY_PRINT);?></textarea></td>
                </tr>
                <tr>
                    <td class='field'>Response</td>
                     <td><textarea class="texArLeg"><?php  echo  json_encode(json_decode($original_response), JSON_PRETTY_PRINT);?></textarea></td>
                </tr>
                <tr>
                    <td class='field'>Post Data</td>
                    <td>
                        <table class='sub_tab'>
                            <tr>
                            <th class='field'>Key</th>
                            <th>Value</th>
                            </tr>
                            <?php 
                            $original_request = json_decode($original_request,true);
                            // print_r($original_request);
                            foreach ($original_request as $key => $value) {
                            ?>
                            <tr>
                                <td class='field'><?=$key?></td>
                                <td><?=$value?></td>
                            </tr>
                            <?php } ?>
                        </table>                    
                    </td>
                </tr>
             </table>
    <?php }else{ ?>
    <div > 
    <table class='pagination' width="100%">
     <tr>
       <td width="10%" style='padding-left:10px;'><a href='tool_api_req_resp_log.php?page=1&mobileapilog=<?=$tablenamelist?>' >Start</a></td>
       <td width="10%">
           
         <?php $prev = $start-1;
         if($prev > 0 ){ ?>
        <a href='tool_api_req_resp_log.php?page=<?=$prev?>&mobileapilog=<?=$tablenamelist?>'>Previous</a>
        <?php } ?>
        </td>
         <td width="60%" style="text-align:center!important;" >Total number of records&nbsp;<?php echo $count; ?> </td>
        <td width="10%">
         <?php 
            $cond3 = ($start)*20; 
               $end = $start+1;   
          if($count > $cond3 ){ ?>
         <a href='tool_api_req_resp_log.php?page=<?=$end; ?>&mobileapilog=<?=$tablenamelist?>'>Next</a>
         <?php } ?> 
     
       </td>
       <td width="10%"><a href='tool_api_req_resp_log.php?page=<?=$last; ?>&mobileapilog=<?=$tablenamelist?>' >Last</a><td>
     </tr>
    </table>
    </div>
    <table class="tab_cls">
       <tr>
        <th>Log Id</th>
        <!-- <th>Action Type</th> -->
        <th>Action </th>
        <th>Post Data</th>
        <th>Date</th>
        <th>IP</th>
        <th>Page Source</th>
        <!-- <th>Status</th> -->
        <th>Method</th>
        <!-- <th>Description</th> -->
        <th>DeviceId</th>
        <!-- <th>App code</th> -->
        <th>Source</th>
        <th>Status</th>
       </tr>
       <?php 
       if($res){
       while($row = mysqli_fetch_assoc( $res )){ 
       extract($row);
      
       ?>
         <tr>
            <td align='right'><?=$log_id ?></td>
            <!-- <td><?=$action_type ?></td> -->
            <td><?=$action ?></td>
            <td><a href="tool_api_req_resp_log.php?id=<?=$log_id ?>&mobileapilog=<?php echo $tablenamelist;?>">Show Data</a></td>
            <td nowrap><?=$c_date ?></td>
            <td><?=$ip ?></td>
            <td><?=$row['url'] ?></td>
           <!--  <td align='right'><?=$status ?></td> -->
            <td><?=$method ?></td>
            <!-- <td><?=$description ?></td> -->
            <td><?=$device_id?></td>
            <td><?=$device?></td>
            <td><?=$status?></td>
         </tr>
       <?php } ?>
    </table>  
    <?php }} ?> 
</div></center>
</body>
</html>