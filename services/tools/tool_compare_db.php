<h1> Tool Compare DB - Under Develpment</h1>
<?php
exit;
include($_SERVER['DOCUMENT_ROOT']."/tools_india/eli_config.php");

$cmp_db_host = "India Database Compare";

$cmp_dn_hosts = array(
        "India Database Compare"=>"india",
        );

$dbnames = array(
        "Stage"=>"test",
        "UAT"=>"uat",        
        "Live"=>"live",
        );

if(!empty($_POST['cmp_db_host'])){    
    $cmp_db_host = $cmp_dn_hosts[$_POST['cmp_db_host']];       
}

if($_POST['db1'] == "test" || empty($_POST['db1']) ){
    $db1 = "dev_env";
    $_POST['db1'] = "test";
}else if($_POST['db1'] == "uat"){
    $db1 = "uat_env";
}else if($_POST['db1'] == "live"){
    $db1 = "prod_env";
}

if($_POST['db2'] == "test"){
    $db2 = "dev_env";
}else if($_POST['db2'] == "uat" || empty($_POST['db2']) ){
    $db2 = "uat_env";
    $_POST['db2'] = "uat";
}else if($_POST['db2'] == "live"){
    $db2 = "prod_env";
}

if($_POST['db1'] == $_POST['db2']){
    echo "Please select different Databases to Compare.. ";
    exit;
}

$tables = array();
$tables_test = array();

if(preg_match( '/india/i' ,$cmp_db_host )){
    $db1_host = $india_config_ini_values[$db1]['host'];
    $db1_user = $india_config_ini_values[$db1]['user'];
    $db1_pass = $india_config_ini_values[$db1]['pass'];
    $db1_db   = $india_config_ini_values[$db1]['db'];
    $db1_port = $india_config_ini_values[$db1]['port'];

    $db2_host = $india_config_ini_values[$db2]['host'];
    $db2_user = $india_config_ini_values[$db2]['user'];
    $db2_pass = $india_config_ini_values[$db2]['pass'];
    $db2_db   = $india_config_ini_values[$db2]['db'];
    $db2_port = $india_config_ini_values[$db2]['port'];   
}

$db1_connection = mysqli_connect( $db1_host, $db1_user, $db1_pass, $db1_db); 
if( mysqli_connect_error() != "" ){
        echo "Error connecting database1 ";
        exit;
}

$db2_connection = mysqli_connect( $db2_host, $db2_user, $db2_pass, $db2_db); 
if( mysqli_connect_error() != "" ){
        echo "Error connecting database2 ";
        exit;
}

mysqli_select_db( $db2_connection, $db2_db );
if( mysqli_connect_error() != "" ){
        echo "Error selecting Database ";
        exit;
}


mysqli_select_db( $db1_connection, $db1_db );
if( mysqli_connect_error() != "" ){
        echo "Error selecting Database ";
        exit;
} 


 $db1_query = "SHOW tables";
 $db1_res = mysqli_query(  $db1_connection,  $db1_query ); 

 $db2_query = "SHOW tables";
 $db2_res = mysqli_query(  $db2_connection,  $db2_query );

$total_data =  $db1_data =  $db2_data = array();
while(  $db1_row = @mysqli_fetch_array(  $db1_res ) ){
        $total_data[$db1][ $db1_row['Tables_in_'.$db1_db]] =  array();
         $db1_data[ $db1_row['Tables_in_'.$db1_db]] = "ok";


        
        $qq1 = "desc ". $db1_row['Tables_in_'.$db1_db];
        $qq2 = "SHOW INDEX FROM ". $db1_row['Tables_in_'.$db1_db];
        $test11 = mysqli_query(  $db1_connection, $qq1 );
        $test22 = mysqli_query(  $db1_connection, $qq2 ); 
        while($row11 = @mysqli_fetch_array( $test11 )){
                $result_test_field[ $db1_row['Tables_in_'.$db1_db]][] = $row11['Field'];
                $result_test_type[ $db1_row['Tables_in_'.$db1_db]][] = $row11['Field']."###".$row11['Type'];
                //   $result_test_type[ $db1_row['Tables_in_'.$db1_db]][$row11['Type']] = $row11['Field'];
                $total_data[$db1][ $db1_row['Tables_in_'.$db1_db]][]  = $row11; 
        }
        while($row22 = @mysqli_fetch_array( $test22)){
            $result_test_index[ $db1_row['Tables_in_'.$db1_db]][] = $row22['Column_name']."###".$row22['Key_name']."###".$row22['Non_unique'];
        }
}
while(  $db2_row = @mysqli_fetch_array(  $db2_res ) ){ 
        $total_data[$db2][ $db2_row['Tables_in_'.$db2_db]] =   array();
         $db2_data[ $db2_row['Tables_in_'.$db2_db]] = "ok";
        
        $qq1 = "desc ". $db2_row['Tables_in_'.$db2_db];
        $qq2 = "SHOW INDEX FROM ". $db2_row['Tables_in_'.$db2_db];
        $test11 = mysqli_query(  $db2_connection, $qq1 ); 
        $test22 = mysqli_query(  $db2_connection, $qq2 );
        while($row11 = @mysqli_fetch_array( $test11 )){
                $result_live_field[ $db2_row['Tables_in_'.$db2_db]][] = $row11['Field'];
                $result_live_type[ $db2_row['Tables_in_'.$db2_db]][] = $row11['Field']."###".$row11['Type']; 
                // $result_live_field[ $db2_row['Tables_in_'.$db2_db]][$row11['Type']] = $row11['Field'];
                $total_data[$db2][ $db2_row['Tables_in_'.$db2_db]][] = $row11;         
        }
        while($row22 = @mysqli_fetch_array( $test22)){
            $result_live_index[ $db2_row['Tables_in_'.$db2_db]][] = $row22['Column_name']."###".$row22['Key_name']."###".$row22['Non_unique'];
        }
}

// echo "<pre>";
// print_r($result_test_type);
// //print_r($result_test_field);
// exit;

?>
<style type="text/css">
th, td {
    border: 1px solid black;
}
table {
    border-collapse: collapse;
}
td {
    padding: 5px;
    text-align: left;
}
#mn_tab table{
    border: 0px;
}
#mn_tab td {
    padding: 5px;
    text-align: center;
}
th{
    padding: 5px;
    text-align: center;
}
</style>
<div class="pull-right">
 <div style="width: 100%;">
    <form name="cmp_dbs" action="" method="post">
<?php //if(!$_POST){ ?>
<table id="mn_tab" cellpadding="4" cellspacing="0" width="50%" style="border: 0 !important;" align="center">
    <tr>   
        <td colspan="2" align="center" >
            <select name="cmp_db_host" id="" >
                <?php foreach ($cmp_dn_hosts as $hoid => $hoval) { ?>
                    <option <?=$_POST['cmp_db_host']==$hoid?'selected':''?> value="<?=$hoid?>"><?=$hoid?></option>                    
                <?php } ?>
            </select>
        </td>
    </tr>  
    <tr>
        <td align="right" >
            DB1 : <select name="db1" id="db1" >
                <?php foreach ($dbnames as $dbid => $dbval) { ?>
                    <option <?=$_POST['db1']==$dbval?'selected':''?> value="<?=$dbval?>"><?=$dbid?></option>                    
                <?php } ?>
            </select>
        </td> 
        <td align="right" >
            DB2 : <select name="db2" id="db2" >
                <?php foreach ($dbnames as $dbid => $dbval) { ?>
                    <option <?=$_POST['db2']==$dbval?'selected':''?> value="<?=$dbval?>"><?=$dbid?></option>                    
                <?php } ?>
            </select>
        </td>    
    </tr>
    <tr>   
        <td colspan="2" align="center" >
            <input type="submit" value="Compare">
        </td>
    </tr> 
</table>
</form>
<?php //} ?>
    </div>
</div> <br>
<?php

echo "<table cell>";
echo "<tr><th><h3><span style='color:red;''>". $db1_db."</span> Table Name</h3><br/></th>";
echo "<th><h3>Field Name</h3><br/></th>";
echo "<th><h3>Data Type</h3> <br/></th>";
echo "<th><h3>Status</h3><br/></th>";

echo "</tr>";

foreach($result_test_field as $key=>$value){

        if($result_live_field[$key]){  
                ?>             
                <?php foreach($value as $kk=>$vv){  
                        //$rem = explode("###",$vv);  
                        if(!in_array($vv, $result_live_field[$key])){
                                echo "<tr><td>". $key."<br/></td>";  
                                echo "<td>". $vv."<br/></td>";
                                echo "<td>------<br/></td>";
                                echo "<td>Field Missing in ".$db2." Table</td></tr>";
                        } 
                
                 } 
        } else {      
                $tables[] = $key;
                // echo "<div>". $key." Table does not exists In Live</div>";
        }
} 

foreach($result_test_type as $key=>$value){
        if($result_live_type[$key]){      
                foreach($value as $kk=>$vv){  
                        $rem = explode("###",$vv);  
                        if(!in_array($vv, $result_live_type[$key])){
                        echo "<tr><td>". $key."<br/></td>"; 
                                echo "<td>".$rem[0]."<br/></td>"; 
                                echo "<td>". $rem[1]."<br/></td>";
                                echo "<td>Data Type Not Matched with ".$db2." Table</td></tr>";
                        }               
                         
                } 
        } 
} 

?>        
</table> 
<br><br>
<table>
<tr> <td><h2> Missing Tables in <span style="color:red;"><?= $db2_db ?></span> DB </h2> </td> </tr>
        <?php 
              if(count($tables)>0){
              foreach($tables as $k => $v){  ?>
                <tr><td><?=$v?></td></tr>         
        <?php } } ?>
</table>
<br><br>
<?php

echo "<table cell>";
echo "<tr><th><h3><span style='color:red;''>". $db2_db."</span> Table Name</h3><br/></th>";
echo "<th><h3>Field Name</h3><br/></th>";
echo "<th><h3>Data Type</h3> <br/></th>";
echo "<th><h3>Status</h3><br/></th>";

echo "</tr>";

foreach($result_live_field as $key=>$value){

        if($result_test_field[$key]){  
                ?>             
                <?php foreach($value as $kk=>$vv){  
                        //$rem = explode("###",$vv);  
                        if(!in_array($vv, $result_test_field[$key])){
                                echo "<tr><td>". $key."<br/></td>";  
                                echo "<td>". $vv."<br/></td>";
                                echo "<td>------<br/></td>";
                                echo "<td>Field Missing in ".$db1." Table</td></tr>";
                        }                 
                } 
        } else {      
                $tables_test[] = $key;
                // echo "<div>". $key." Table does not exists In Test</div>";
        }
} 



foreach($result_live_type as $key=>$value){
        if($result_test_type[$key]){      
                foreach($value as $kk=>$vv){  
                        $rem = explode("###",$vv);  
                        if(!in_array($vv, $result_test_type[$key])){
                                echo "<tr><td>". $key."<br/></td>"; 
                                echo "<td>".$rem[0]."<br/></td>"; 
                                echo "<td>". $rem[1]."<br/></td>";
                                echo "<td>Data Type Not Matched with ".$db1." Table</td></tr>";
                        }               
                         
                } 
        } 
} 

?>        
</table> 
<br><br>
<table>
<tr> <td><h2> Missing Tables in <span style="color:red;"><?= $db1_db?></span> DB </h2> </td> </tr>
        <?php 
            if(count($tables_test)>0){
            foreach($tables_test as $k => $v){  ?>
                <tr><td><?=$v?></td></tr>         
        <?php } } ?>
</table>
<br><br>
<?php

echo "<table cell>";
echo "<tr><th><h3><span style='color:red;''>". $db1_db."<br></span> Table Name</h3><br/></th>";
echo "<th><h3>Field Name</h3><br/></th>";
echo "<th><h3>Key Name</h3><br/></th>";
echo "<th><h3>Non Unique</h3> <br/></th>";
echo "<th><h3>Status</h3><br/></th>";
echo "</tr>";

foreach($result_test_index as $key=>$value){
        if($result_live_index[$key]){  
                ?>             
                <?php foreach($value as $kk=>$vv){  
                        $rem = explode("###",$vv);  
                       
                        if(!in_array($vv, $result_live_index[$key])){
                            $count=0;
                            foreach ($result_live_index[$key] as $key1 => $value1) {
                                $rem1 = explode("###",$value1); 
                                if($rem[0] ==  $rem1[0])
                                    $count++;
                            }
                            if($count == 0)
                                $status_msg = "Index not found in ".$db2." table";
                            else
                                $status_msg = "Index Miss-match in ".$db2." table";
                            
                                echo "<tr><td>". $key."<br/></td>";  
                                echo "<td>". $rem[0]."<br/></td>";
                                echo "<td>". $rem[1]."<br/></td>";
                                echo "<td>". $rem[2]."<br/></td>";
                                echo "<td> ".$status_msg." </td></tr>";
                        } 
                
                } 
        } else {      
                $tables[] = $key;
        }
} 


?>        
</table> 
<br><br>

<br><br>
<?php

echo "<table cell>";
echo "<tr><th><h3><span style='color:red;''>". $db2_db."<br></span> Table Name</h3><br/></th>";
echo "<th><h3>Field Name</h3><br/></th>";
echo "<th><h3>Key Name</h3><br/></th>";
echo "<th><h3>Non Unique</h3> <br/></th>";
echo "<th><h3>Status</h3><br/></th>";

echo "</tr>";

foreach($result_live_index as $key=>$value){
        if($result_test_index[$key]){  
                ?>             
                <?php foreach($value as $kk=>$vv){  
                        $rem = explode("###",$vv);  
                        if(!in_array($vv, $result_test_index[$key])){
                            $count=0;
                            foreach ($result_test_index[$key] as $key1 => $value1) {                                
                                $rem1 = explode("###",$value1); 
                                if($rem[0] ==  $rem1[0])
                                    $count++;
                            }
                            if($count == 0)
                                $status_msg = "Index not found in ".$db1." table";
                            else
                                $status_msg = "Index Miss-match in ".$db1." table";

                                echo "<tr><td>". $key."<br/></td>";  
                                echo "<td>". $rem[0]."<br/></td>";
                                echo "<td>". $rem[1]."<br/></td>";
                                echo "<td>". $rem[2]."<br/></td>";
                                echo "<td> ".$status_msg." </td></tr>";
                        } 
                
                 } 
        } else {      
                $tables[] = $key;
        }
} 



?>        
</table> 