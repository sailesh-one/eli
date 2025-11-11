<?php 
include('config.php');
?>
<html>
<head>
<title>Dealers Branches List</title>    
</head>
<body>

<style type='text/css'>
        *{font-family:arial; font-size:12px;}
        .minitable{border:1px #ddd solid; border-collapse: collapse;}
        .minitable thead{background:#eee; padding:5px;}
        .minitable thead th, .minitable td{padding: 6px; border-bottom:1px #ddd solid; border-right:1px #ddd solid;}
        a{color:#333;}
</style>
<h1 style="font-size:24px">Dealer Branches List</h1>
<table class='minitable'>
<tr>
        <thead>
                <th>Branch ID</th>
                <th>Branch Name</th>                
                <th>Main Branch</th>
                <th>Dealer Id</th>
                <th>Dealer Name</th>                
                <th>City</th> 
                <th>Display Name</th>    
                <th>Display City</th>                                                                
                <th>Active</th>
        </thead>
</tr>           
<?php
$query="select a.*, b.name from dealer_branches as a left join users as b on (a.dealer_group_id = b.id) where b.name!='' order by dealer_group_id asc";
$data=mysqli_query($connection,$query);
if (!$data) {
    die("Query Error: " . mysqli_error($connection));
}
$i=1;
$dealers_info = array();
while($row=mysqli_fetch_array($data))
{
        $dealers_info[$row['id']] = array('company' => $row['company'], 'city' => $row['city'] );
        ?>
                <tr>
                    <td align='center'><?php echo hsc($row['id']); ?></td>
                    <td><?php echo hsc($row['name'])?></td>
                    <td><?php echo hsc($row['is_main_branch'])?"Yes":"No"; ?></td>
                    <td><?php echo hsc($row['id']); ?></td>                
                    <td ><?php echo hsc($row['registered_name']); ?></td>
                    <td ><?php echo hsc($row['city']); ?></td>
                    <td ><?php echo hsc($row['name']); ?></td>
                    <td ><?php echo hsc($row['dealer_display_city']); ?></td>
                    <td ><?php echo hsc($row['active']==1?'Yes':'No') ?></td>
                                      
                </tr>
        <?php
        $i++;
        
}
?>
</table>
</body>
</html>