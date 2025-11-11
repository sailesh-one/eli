<h1> Branches Executives - Under Develpment</h1>
<?php 
exit;
include('config.php');
?>
<html>
<head>
<title>Branches Executives Mapping</title>    
</head>
<body>

<style type='text/css'>
        *{font-family:arial; font-size:12px;}
        .minitable{border:1px #ddd solid; border-collapse: collapse;}
        .minitable thead{background:#eee; padding:5px;}
        .minitable thead th, .minitable td{padding: 6px; border-bottom:1px #ddd solid; border-right:1px #ddd solid;}
        a{color:#333;}
</style>

<h1 style="font-size:24px">Dealer Branches to Executives Mapping</h1>
<table class='minitable'>
<tr>
        <thead>
                <th>Dealer Id</th>
                <th>Dealer Name</th>                                
                <th>Branch ID</th>
                <th>Branch Name</th>                
                <th>Executive Id</th>
                <th>Executive Name</th>                                
                <th>Executive Plan Id</th>                                                
                <th>Executive Plan</th>                                                                
                <th>Mapping Active</th>
        </thead>
</tr>           
<?php
$db_host = $config['db_host'];
$db_user = $config['db_user'];
$db_pass = $config['db_pass'];
$db_name = $config['db_name'];
$connection = mysqli_connect( $db_host, $db_user, $db_pass, $db_name);
$query="select a.*, b.company, c.id as branch_id, c.branch_name, d.plan_name, e.name, e.plan_id from users_branch_executive_mapping as a 
    left join users as b on (a.dealer_id = b.id) 
    left join users_branches as c on (a.mapped_branches = c.id)    
    left join users as e on (a.executive_id = e.id )          
    left join users_plans as d on (e.plan_id = d.plan_id)      
    where b.company!='' and ( b.active='y' or e.active='y' ) order by dealer_id, branch_id, executive_id asc";
$data=mysqli_query($connection,$query);
is_error($query);
$i=1;
$dealers_info = array();
while($row=mysqli_fetch_array($data))
{
        ?>
                <tr>
                    <td align='center'><?php echo hsc($row['dealer_id']); ?></td>
                    <td><?php echo hsc($row['company'])?></td>
                    <td><?php echo hsc($row['mapped_branches']); ?></td>                
                    <td><?php echo hsc($row['branch_name']); ?></td>                
                    <td ><?php echo hsc($row['executive_id']); ?></td>
                    <td ><?php echo hsc($row['name']); ?></td>
                    <td ><?php echo hsc($row['plan_id']); ?></td>
                    <td ><?php echo hsc($row['plan_name']); ?></td>
                    <td ><?php echo hsc($row['active']==1?'Yes':'No') ?></td>
                                      
                </tr>
        <?php
        $i++;
        
}
?>
</table>
</body>
</html>