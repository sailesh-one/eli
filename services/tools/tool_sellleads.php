<?php 
include('config.php');
?>
<html>
<head>
<title>Latest PM Leads (Sellleads)</title>    
</head>
<body>
    
<style type='text/css'>
        *{font-family:arial; font-size:12px;}
        .minitable{border:1px #ddd solid; border-collapse: collapse;}
        .minitable thead{background:#eee; padding:5px;}
        .minitable thead th, .minitable td{padding: 6px; border-bottom:1px #ddd solid; border-right:1px #ddd solid;}
        a{color:#333;}
</style>

<table class='minitable'>
<tr>
        <thead>
                <th>PM Lead ID</th>
                <th>Dealer ID</th>
                <th>Dealer Name</th>
                <th>Lead Insert Date</th>
                <th>Lead Updated Date</th>                
                <th>Customer Name</th>                
                <th>Customer Mobile</th> 
                <th>Customer Email</th>                 
                <th>Source Category</th>                                                                
                <th>Source</th>
                <th>Sub Source</th>
                <th>Opted For Finance</th> 
                <th>IP</th>
                <th>Exact Match</th>
                <th>Customer Comments</th>                                                 
        </thead>
</tr>           
<?php

$query="select a.*, b.name,(select source as sourceName from master_sources where id=a.source)as sourceName,(select sub_source from master_sources_sub where id=a.source_sub)as sub_source from sellleads as a left join dealer_groups as b on (a.user = b.id) where b.name!='' order by updated desc, id asc limit 100";
$data=mysqli_query($connection,$query);
//is_error($query);
$i=1;
$dealers_info = array();
while($row=mysqli_fetch_assoc($data))
{
        
        //$dealers_info[$row['id']] = array('company' => $row['company'], 'city' => $row['city'] );
        ?>
                <tr>
                    <td align='center'><?php echo hsc($row['id']); ?></td>
                    <td align='center'><?php echo hsc($row['dealer'])?></td>
                    <td><?php echo hsc($row['name']); ?></td>
                    <td ><?php echo hsc($row['created']); ?></td>                    
                    <td ><?php echo hsc($row['updated']); ?></td>                                        
                    <td><?php echo hsc($row['first_name'])." ".hsc($row['last_name']); ?></td>                
                    <td ><?php echo hsc($row['mobile']); ?></td>
                    <td ><?php echo hsc($row['email']); ?></td>
                    <td ></td>
                    <td ><?php echo hsc($row['sourceName']); ?></td>                    
                    <td ><?php echo hsc($row['sub_source']); ?></td>                                        
                    <td><?php echo hsc($row['finance']=='y'?'Yes':'No')?></td>
                    <td ><?php echo hsc($row['ip']); ?></td>                                    
                    <td ></td>                
                    <td ><?php echo hsc($row['remarks']); ?></td>                                        
                </tr>
        <?php
        $i++;
        
}
?>
</table>


</body>
</html>