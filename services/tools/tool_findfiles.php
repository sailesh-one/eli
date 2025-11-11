<?php
ini_set("memory_limit", "6000M");
$server_host=$_SERVER['HTTP_HOST'];
$config_ini_env = "";
$host_path='';
$ext_folder='';
if($server_host=='dms.jlr.local'){
    $config_ini_env = "dev";
}else if($server_host=='jlr-udms.cartrade.com'){
    $config_ini_env = "stage";
}
?>
<!DOCTYPE html>
<html>
<head>
<title>JLR Findfiles</title>
<meta charset="UTF-8">
<meta name="robots" content="noindex, nofollow">

<style>
    body,table,tr,td {font-family: Arial, sans-serif; font-size: 12px;}
    .trsel:hover{ background: lightgray; }
    .trsel{ border-bottom: 1px solid lightgray; }
    .chk{ width:20px; height:20px; cursor: pointer; }
    .wrap{ min-height: 900px; overflow: hidden;}
    .pull-left{width: 20%; float: left; min-height: 700px; }
    .pull-right{width: 80%; float: right;}
</style>
<script>
    var ch_k = 0;
    function selcheck(id){
        if(document.getElementById('che_'+id).checked) {
            document.getElementById('tr_'+id).style.backgroundColor = "gray";
            document.getElementById('che_'+id).checked = true;
            ch_k++;
        }else{
            document.getElementById('tr_'+id).style.backgroundColor = "";
            document.getElementById('che_'+id).checked = false;
            ch_k--;
        }   
        if(ch_k > 9) {
            /*alert("Enough folders selected ");
            document.getElementById('tr_'+id).style.backgroundColor = "";
            document.getElementById('che_'+id).checked = false;
            ch_k--;
            return false;*/ 
        }
    }
    function check_all(ele) {
        /*if(ele.checked && !confirm("are you sure to select all folders? ")) {
            document.getElementById("all_check").checked = false;
            return false;}  */
        var checkboxes = document.getElementsByTagName('input');
        if (ele.checked) {
            for (var i = 0; i < checkboxes.length; i++) {
                if (checkboxes[i].type == 'checkbox' && checkboxes[i].disabled != true) {
                    checkboxes[i].checked = true;
                }
            }
        } else {
            for (var i = 0; i < checkboxes.length; i++) {
                console.log(i)
                if (checkboxes[i].type == 'checkbox' && checkboxes[i].disabled != true) {
                    checkboxes[i].checked = false;
                }
            }
        }
    }
</script>
</head>
<body>
<div class="wrap">
<?php

if ($config_ini_env == "dev") {
    $host = "dms.jlr.local";
    $host_path="vhosts";
    $hosts = array(
            "dms.jlr.local" => "dms.jlr.local"
            
    );
}
if ($config_ini_env == "stage") {
    $host = "jlr-udms.cartrade.com";
    $host_path="vhosts-jlr";
    $ext_folder="htdocs";
    $hosts = array(
            "jlr-udms.cartrade.com" => "jlr-udms.cartrade.com"
    );
}
if ($config_ini_env == "uat_env") {
    $host = "uat.jlr-udms.cartrade.com";
    $hosts = array(
        "uat.jlr-udms.cartrade.com" => "uat.jlr-udms.cartrade.com"
    );
}

if ($config_ini_env == "prod_env") {
    $host = "jlr-udms.cartrade.com";
    $hosts = array(
        "jlr-udms.cartrade.com" => "jlr-udms.cartrade.com"
    );
}


if (!empty($_REQUEST['host'])) {
    $host = $_POST?$hosts[urldecode($_REQUEST['host'])]:$hosts[$_REQUEST['host']];
}

if ($_POST) {
    $searchitem = $_POST['searchitem'];
    if (!trim($searchitem)) {
        exit("Enter Search Term");
    }
    $flr = '';
    if ($_POST['dir']) {
        foreach ($_POST['dir'] as $key => $folder) {
            if (trim($folder) == '.') {
                $flr = '';
                continue;
            }
            if($server_host=='dms.jlr.local'){
            
                $flr .= '/data/vhosts/'.$host.'/'.$folder."/ ";
            
            }else if($server_host=='jlr-udms.cartrade.com'){
            
                     $flr .= '/data/vhosts-jlr/'.$host.'/httpdocs/'.$folder."/ ";
                    
            
            }
        }
    } else {
        echo "No directory Given...Searching default Root Directory <br>";
        $flr = '';
    }
    $flr = trim($flr);
    $oo = [];
// echo "grep -sFin '$searchitem' -r --exclude=\*.{jpg,zip,rar,bmp,jpeg,gif,png} $flr";exit;
    if ($flr != '') {
        exec("grep -sFin '$searchitem' -r --exclude=\*.{jpg,zip,rar,bmp,jpeg,gif,png} $flr", $oo);
    }
    $ppp = [];
    if (!empty(trim($_REQUEST['rootdir'])) || $flr == '') {
        exec("grep -sFin '$searchitem' --exclude=\*.{jpg,gz,tar,7z,sql,xml,zip,rar,bmp,jpeg,gif,png} /data/vhosts/$host/httpdocs/*", $ppp);
        $oo = array_merge($ppp, $oo);
    }


    $final = array_map('htmlspecialchars', $oo);
    echo "<b>Total results count ".count(array_filter($oo))."</b>";

    foreach ($final as $key => $value) {
        if ($final[$key] == "--") {
            continue;
        }

        $value = str_replace('/data/vhosts/'.$host.'/httpdocs/', '', $value);
        $_three = explode(":", $value);
    // $_fin1 = pathinfo($_three[0])['extension'];
    // $_fin2 = pathinfo($_three[0])['basename'];
        $_fpth = $_three[0];
        $_line = $_three[1];
        unset($_three[0]);
        unset($_three[1]);
        $_fin3 = implode(":", $_three);
    // $fd = explode('.php:',$value);
    // $fd2 = explode(':',$fd[1]);
        ?>
    <table width="700px" style="border-bottom: 1px solid gray;border-top: 1px solid gray;font-size: 14px;font-family: Arial, sans-serif;margin:15px 2px 7px 15px ">
        <tr><td bgcolor="lightgray"><?=$_fpth?></td></tr>
        <tr><td>Line Number : <?=$_line?></td></tr>
        <tr><td bgcolor=""><?=str_replace($searchitem, "<span style='background:#66FF9C'>".$searchitem."</span>", $_fin3)?></td></tr>
    </table>
        <?php
    }
}


//exec("find /data/vhosts/$host/ -maxdepth 1 -mindepth 1 -type d | sort", $o);
//$o= str_replace('/data/vhosts/'.$host.'/', "", $o);
// $o = array_flip($o);
exec("find /data/$host_path/$host/httpdocs -maxdepth 1 -mindepth 1 -type d | sort", $o);
    $o= str_replace('/data/'.$host_path.'/'.$host.'/httpdocs', "", $o);
if (!$_POST) {
    $ign = array(".git", "vendor", "fonts", "css");
    ?>
<form action="" method="POST" target="ifr1">
<div class="pull-left">
<div style="height:100%;overflow:auto;position:fixed;padding-bottom:15px;width:225px;">
    <table style="float:left;border-collapse:collapse;cursor:pointer;font-family: Arial, sans-serif;font-size: 13px;width:200px;">
        <tr  class="trsel">
                <th>Check All Folders</th>
                <th><input class="chk" type="checkbox" id="all_check" onclick="check_all(this)"></th>
        </tr>
        <tr onclick="" id="tr_1" class="trsel"><td>Root folder</td><th>
        <input class="chk" onclick="selcheck(1)" type="checkbox" id="che_1" value="." name="rootdir" checked="true">
        </th></tr>
        <?php
        $s = 1;
        asort($o);
        if ($_GET['show'] == "test") {
            echo "<pre>";
            print_r($o);
            echo "</pre>";
        }
        foreach ($o as $key => $value) {
            $sear = str_replace('/data/'.$host_path.'/'.$host.'/'.$ext_folder, '', $value);
            if (in_array($sear, $ign)) {
                    continue;
            }
                $s++;
            ?>
        <tr onclick="" id="tr_<?=$s?>" class="trsel">
            <td><?=$value?></td>
            <th><input class="chk" type="checkbox" id="che_<?=$s?>" value="<?=$value?>" name="dir[]" onchange= "selcheck(<?=$s?>)" id=""></th>
        </tr>
        <?php } ?>
        <tr>
            <td>&nbsp;</td>
        </tr>
    </table>
</div>
</div>
<div class="pull-right">
    <div style="float:left; width: 100%;">
    <?php if (!$_POST) { ?>
<table cellpadding="4" cellspacing="0" border="0" width="98%" align="center">
    <tr>
    <td align="left" width="40%">
    <input type="text" name="searchitem" style="width: 201px;font-size: 15px;">
    <input type="submit" value="Search" style="padding:3px 5px 3x 5px;">
    </td>
        <td align="right" width="20%">
            <select name="host" id="" onchange="location.href='?host='+this.value" >
                <?php foreach ($hosts as $hoid => $hoval) { ?>
                    <option <?=$_REQUEST['host']==$hoid?'selected':''?> value="<?=$hoid?>"><?=$hoid?></option>                    
                <?php } ?>
            </select>
        </td>
        <td align="left" width="40%"><?=$host?></td>
    </tr>
</table>
    <?php } ?>
    </div>
</form>
<iframe style="width:90%;height:700px;margin-top:5%;border:1px solid gray;" src="" name="ifr1" frameborder="0"></iframe>
    <?php
}
?>
</div>
</div>
</body>
</html>