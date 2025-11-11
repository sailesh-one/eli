<?php 
include('config.php');
$host = $_POST['host']?$_POST['host']:"JLR";
$roles_info=[];
$qry="select id,role_name from config_roles order by id asc";
$record=mysqli_query($connection,$qry);
while($row=mysqli_fetch_assoc($record))
{
    $roles_info[$row['id']]=$row['role_name'];
}
?>
<html>
<head>
<title>Dealers Login Credentials</title>    
</head>
<body>
    
<style type='text/css'>
        *{font-family:arial; font-size:12px;}
        .minitable{border:1px #ddd solid; border-collapse: collapse;}
        .minitable thead{background:#eee; padding:5px;}
        .minitable thead th, .minitable td{padding: 6px; border-bottom:1px #ddd solid; border-right:1px #ddd solid;}
        a{color:#333;}
        .non_bps_dealer{ display: none; }

        select { width: 25%; height: auto; margin: auto; font-size: 15px;}     
        
</style>
<?php
$hosts = array("JLR" => "JLR");
?>

<form action="" method="POST">
<table cellpadding="4" cellspacing="0" border="0" width="98%" align="center">
    <tr>
        <td align="right" width="20%">
            <select name="host" id="">
                <?php foreach ($hosts as $hoid => $hoval) { ?>
                    <option <?=$_REQUEST['host']==$hoid?'selected':''?> value="<?=$hoid?>"><?=$hoval?></option>
                <?php } ?>
            </select>
        </td>
        <td align="left" width="40%">
            <input type="submit" value="Submit" style="padding:3px 5px 3x 5px;">
        </td>
    </tr>
</table>

</form>
<h1>Dealer Groups</h1>
<table class='minitable'>
<tr>
        <thead>
                <th>ID</th>
                <th>Dealer Name</th>
                <th>Short Name</th>
                <th>Active</th> 
                <th>Created By</th>
                <th>Created By Name</th>
                <th>Created By Role</th>
                <th>Created Date</th>            
                <th>Updated Date</th>                      
        </thead>
</tr>           
<?php
$query="select d.id,d.name,d.short_name,d.active,d.created,d.updated,a.id as adminId,a.name as adminName,a.role_id  from dealer_groups as d left join users_admin as a ON(a.id=d.created_by)  order by d.id asc";
$record=mysqli_query($connection,$query);
$i=1;
$dealers_info = array();
while($row=mysqli_fetch_assoc($record))
{
    $dealers_info[$row['id']] = array('company' => $row['name']); 
?>
<tr>
    <td align='center'><?php echo hsc($row['id']); ?></td>
    <td><?php echo hsc($row['name'])?></td>
    <td><?php echo hsc($row['short_name']); ?></td>
    <td ><?php echo hsc($row['active']); ?></td>
    <td ><?php echo hsc($row['adminId']); ?></td>
    <td ><?php echo hsc($row['adminName']); ?></td>
    <td><?php echo hsc($roles_info[$row['role_id']])?></td>
    <td ><?php echo hsc($row['created']); ?></td>   
    <td ><?php echo hsc($row['updated']); ?></td>                       
</tr>
<?php
    $i++;    
}
?>
</table>

<h1>Executives</h1>
<table class='minitable'>
<tr>
        <thead>
                <th>ID</th>
                <th>Dealer ID</th>                
                <th>Dealer Name</th>   
                <th>Executive Name</th>    
                <th>Mobile</th>                 
                <th>Email</th>  
                <th>Role ID</th>  
                <th>Role</th>                
                <th>Active</th>      
                <th>Assign Branches</th>                  
        </thead>
</tr>           
<?php
$query="SELECT 
  id,
  name,
  email,
  mobile,
  dealership_id,
  role_id,
  created_at,
  updated_at,
  active,
  branch_id
FROM users 
ORDER BY id asc";

$data=mysqli_query($connection,$query);
$i=1;
while($row=mysqli_fetch_assoc($data))
{

        ?>
                <tr>
                    <td align='center'><?php echo hsc($row['id']); ?></td>
                    <td><?php echo hsc($row['dealership_id']); ?></td>                    
                    <td><?php echo hsc($dealers_info[$row['dealership_id']]['company'])?></td>
                    <td><?php echo hsc($row['name'])?></td>
                    <td><?php echo hsc($row['email'])?></td>
                    <td><?php echo hsc($row['mobile'])?></td>
                    <td><?php echo hsc($row['role_id'])?></td>   
                    <td><?php echo hsc($roles_info[$row['role_id']])?></td>
                    <td ><?php echo hsc($row['active']); ?></td>
                    <td>Yes</td>
                </tr>
        <?php
        $i++;
        
}
?>
</table>

<h1>Admin Users</h1>
<table class='minitable'>
<tr>
        <thead>
                <th>ID</th>                        
                <th>Name</th>      
                <th>Mobile</th>   
                <th>Email</th>                            
                <th>Role</th>                     
                <th>Active</th>    
        </thead>
</tr>           
<?php


$query="select * from users_admin order by id asc";
$data=mysqli_query($connection,$query) or die(mysql_error());

$i=1;
while($row=mysqli_fetch_array($data))
{
        ?>
                <tr>
                    <td align='center'><?php echo hsc($row['id']); ?></td>
                    <td><?php echo hsc($row['name'])?></td>
                    <td><?php echo hsc($row['mobile'])?></td>
                    <td><?php echo hsc($row['email'])?></td>  
                    <td><?php echo hsc($roles_info[$row['role_id']]); ?></td>         
                    <td><?php echo hsc($row['active']=='y'?'Yes':'No'); ?></td>    
                </tr>
        <?php
        $i++;
        
}
?>
</table>
</body>
</html>