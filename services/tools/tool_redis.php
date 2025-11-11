<h1> Tool Redis - Under Develpment</h1>
<?php 
exit;
include("config.php");
$redis_host = $india_credentials['redis_host'];
$redis_port = $india_credentials['redis_port'];



foreach ($redis_db_details[$config_ini_env] as $db_id => $db_name) {
	echo "<a style='font-size:16px' href='?dbno=".$db_id."'>".$db_name."</a> |  ";
}

echo "<br><br>".$redis_host.":".$redis_port."<br/>";
$redis_connection = false;

$redis = new Redis();
$redis_connection = $redis->connect($redis_host,$redis_port);
	
if(!$redis_connection){
	header("HTTP/1.1 503 Server Unavailable");
	echo '<html><head><title>bmw-bps.in</title></head><body>Redis server is unavailable</body></html>';
	exit;  
}
function v_type( $vt ){

	if( $vt == 1 ){
		return "string";
	}else if( $vt == 2 ){
		return "set";
	}else if( $vt == 3 ){
		return "list";
	}else if ( $vt == 4 ){
		return "zset";
	}else if( $vt == 5 ){
		return "hash";
	}else{
		return $vt;
	}

}

if( $_GET['showgroup'] ){
	$width = 350;
}else{
	$width = 200;
}

$ifrno = $_GET['ifrno']?$_GET['ifrno']+1:1;
$dbno = $_GET['dbno']?$_GET['dbno']:$redis_db;
if( $dbno > 0 ){
	$redis->select( $dbno );
}
?>
<!DOCTYPE html>
<html>
<head><title>My Simple Redis Viewer</title></head>
<body>
<style>* {font-size:12px; font-family:Arial; color:#333333;}
body,form,p{ margin:0px; padding:0px; }

a {color:#333355;}
</style>
<?php
if(isset($_POST['gosearch'])){  
    $keyword = $_POST['customsearch'];
    $url="tool_redis2.php?dbno=0&ifrno=1&showkey=".$keyword;
    header("Location:".$url);
    exit;
} 
?>
<?php if( $_GET['showgroup']=='' && $_GET['showkey']==''){?>
<br/>
  <form method="post"> 
      <div style="margin-bottom:20px;margin-top:20px;">
          Search Key:
          <input type="text" name="customsearch" id="customsearch" style="width:100px;">
          <input type="submit" name="gosearch" value="Go"> 
      </div>
  </form>  
<?php }?> 
<table cellpadding="0" cellspacing="0" border="0" bgcolor="red" width="100%">
<tr valign="top" bgcolor="white"><td width="<?=!$_GET['showkey']&&!$_GET['action']?$width:"" ?>" style="border-right:1px solid #cdcdcd;">
<div id="left_td" style="width:<?=!$_GET['showkey']&&!$_GET['action']?$width."px":"100%" ?>; margin:5px; margin-top:0px; margin-bottom:0px; height:460px; overflow:auto;">
<div id="loa" style="line-height:50px; border:1px solid #cdcdcd; margin:20px; padding:20px; background-color:#e0e0e0; color:red; font-weight:bold;" >Loading...</div>
<?php
ob_flush();

flush();

if( $_GET['action'] == "confirmdelete" ){
	//echo "<pre>";
	//print_r($redis->info() );
	$redis->del( urldecode($_GET['key']) );
	echo "<div style='margin:20px; padding:20px; font-size:16px; border: 1px solid #999999; background-color:#f0f0ff;'>Deleted <BR><BR><strong>".$_GET['key']."</storng></div>";
	
}else if( $_GET['action'] == "deletekey" ){

	echo "<div style='margin:20px; padding:20px; font-size:16px; border: 1px solid #999999; background-color:#f0f0ff;'>Are you sure about deleting <BR><BR><strong>".$_GET['key']."</storng></div>";
	echo "<div><a href=\"?action=confirmdelete&dbno=".$_GET['dbno']."&key=".urlencode($_GET['key'])."\" >Confirm</a></div>";

}elseif( $_GET['showkey'] ){
	
	$vtype = v_type( $redis->type($_GET['showkey']) );
	echo "<div style='font-size:18px;line-height:35px;' >" . ucwords($vtype) . "</div>";
	echo "<div style=' font-size:14px; margin-bottom:10px; font-weight:bold; border-bottom:2px solid #cdcdcd; ' >" . $_GET['showkey'] . "</div>";
	echo "<div align='right' ><a href='?action=deletekey&dbno=".$_GET['dbno']."&key=".urlencode( $_GET['showkey'] ) . "' >DeleteKey</a></div>";
	echo "<div>Value:</div>";
	echo "<div id='sp_value_view' style='width:200px;height:200px;overflow:auto;' >";
	
	$ttl = $redis->ttl( $_GET['showkey'] );
	echo "<div>time: " . $ttl . "</div>";

	if( $vtype == "string" ){
		echo "<pre>" . $redis->get( $_GET['showkey'] ) . "</pre>";
	}else if( $vtype == "zset" ){
		echo "<pre>";
		print_r( $redis->get( $_GET['showkey'] ) );
		print_r( $redis->zrangebyscore( $_GET['showkey'], -0, 9999999999 )  );
		echo '</pre>';
	}else if( $vtype == "hash" ){
	
		if( preg_match( "/(URL)/", $_GET['showkey'] ) ){
        		$fields = array("url","rewrite_path", "robots", "link_canonical", "link_alternate", "page_action", "redirect_url", "layout", "meta_title", "meta_description", "meta_keywords", "module_name", "sub_module", "sub_module2", "page_type", "make", "model", "variant", "city", "short_description", "full_description", "upper_page_content", "middle_page_content", "footer_page_content" );
		}else{
			$fields = array();
		}

		$total_fields = $redis->hlen( $_GET['showkey'] );
		$p = $_GET['p']?$_GET['p']:1;
		$pages = floor($total_fields/100)+1;
		$start = ($p-1)*100;

		echo "<div>Keys in Hash: " . $total_fields . "</div>";
		if( $total_fields > 200 ){
			echo "<div>Displaying: " . $start . " to " . ($start+100) . "</div>";
			$it = $_GET['nextiterator']?$_GET['nextiterator']:null;
			$v = array();
			$search = $_GET['search']?"*".$_GET['search']."*":"";
			if( $search ){
				$cnt = 0;
				do
				{
					$cnt++;
				 	$vv = $redis->hscan( $_GET['showkey'], $it, $search , 1000 );
				 	if( is_array($vv) ){
						$v = array_merge( $v, $vv );
					}else{
						break;
					}
					//echo "<div>" . $it . " : " . sizeof($v ) . "</div>";
				}while( $cnt < 100 || sizeof($v)<100 );
			}else{
			    //$v = $redis->hscan( $_GET['showkey'], $it, "", 100 );			    
			    //$it = 0;
                $cnt = 0;
			    do {
    			    //$it++;
                    $cnt++;
    			    $v = $redis->hscan( $_GET['showkey'], $it, "" , 1000 );
                    if($it == 0){break;}
    			    //echo "<div>" . $it . " : " . sizeof($v ) . "</div>";
			    }while( $cnt < 100 || sizeof($v)<100 );
			}
			//print_r($v);
			echo "<div>Last Iterator: ". $it . "</div>";

			echo "<div align='right' ><a href='?dbno=".$_GET['dbno']."&ifrno=".$_GET['ifrno']."&showkey=".$_GET['showkey']."&nextiterator=".$it."' >Next</a></div>";	
			echo "<div align='right' ><input type='text' name='search' id='search_field' value='' ><input type='button' value='Search' onclick='searchhashkey()' >  <a href='?dbno=".$_GET['dbno']."&ifrno=".$_GET['ifrno']."&showkey=".$_GET['showkey']."&nextiterator=".$it."' >Next</a></div>";
		}else{
			$v = $redis->hgetall( $_GET['showkey'] );
		}
		
		if( 1==1 ){
		
		echo '<table width="100%" cellpadding="5" cellspacing="1" bgcolor="#cdcdcd" >';
		ksort( $v );
		foreach ((array) $fields as $ii=>$i ){
			$j = $v[ $i ];
			unset($v[$i]);
			if( $j[0] == "{" || $j[0] == "[" ){
				$j = print_r( json_decode( $j ), 1 );
			}else{
				$j = htmlspecialchars($j);
			}
			echo "<tr width='200' valign='top' bgcolor='white'><td >" . $i . "</td>";
			echo "<td><div class='spd' style='width:600px;height:100px; overflow:auto;' ><pre>".$j."</pre></div></td>";
			echo "</tr>"; 
		}
		foreach ((array) $v as $i=>$j ){
			if( $j[0] == "{" || $j[0] == "[" ){
				$j = print_r( json_decode( $j ), 1 );
			}else{
				$j = htmlspecialchars($j);
			}
			echo "<tr width='200' valign='top' bgcolor='white'><td >" . $i . "</td>";
			echo "<td><div class='spd' style='width:600px;height:100px; overflow:auto;' ><pre>".$j."</pre></div></td>";
			echo "</tr>";
		}
		
		}

		echo "</table>";
		
		?>
		<script language="javascript">
			function searchhashkey(){
				se = document.getElementById( "search_field").value ;
				document.location = "/tool_redis2.php?dbno=<?=$_GET['dbno'] ?>&ifrno=<?=$_GET['ifrno'] ?>&showkey=<?=$_GET['showkey'] ?>&search=" + se ;
			}
		</script>
		<?php
		
	}else if( $vtype == "set" ){
		print_r( $redis->smembers( $_GET['showkey']) );
	}else if( $vtype == "list" ){
		print_r( $redis->lrange( $_GET['showkey'], 0, 100 ) );

	}else{
		echo $vtype . ": this type variable view has not coded yet . ... satish !";
	}
	
	
	
	echo "</div>";
	

}else{

	if( $_GET['showgroup'] ){
	
		if( $_GET['showgroup'] ){
		    if( $_GET['search'] ){
			    $s = $_GET['showgroup'] .":*" . $_GET['search'] . "*";
		    }else{
			    $s = $_GET['showgroup'] .":*";
		    }
		    //$list = $redis->keys( $s );
		    $lt = null;
		    //echo $s;
		    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
		    while( $keys_ = $redis->scan($lt, $s) ){			
			foreach ((array) $keys_ as $k ){
			    $list[] = $k;
			}
		    }
		}else{
			//$list = $redis->keys("*");
			$lt = null;
			while( $keys_ = $redis->scan($lt, $s, 999999) ){
				foreach ((array) $keys_ as $k ){
						$list[] = $k;
				}
			}
		}
		
		$total_keys = $redis->dbsize();
		
		if( !$_GET['showgroup'] ){
			for( $i=0;$i<=5;$i++){
				?><a style="border:1px solid #cdcdcd; padding:5px; margin;2px; background-color:#f0f0f0;" href="?dbno=<?=$i ?>" ><?=$i ?></a>&nbsp;<?php
			}
		}
		
		echo "<div style='line-height:30px; border-bottom:2px solid #cdcdcd; font-size:14px; ' >";
		if( $_GET['showgroup'] ){
			echo "Group: <strong>".($s?$s:"Redis")."</strong><Br>Keys: <strong>" . number_format( sizeof( $list ) ) . "</strong>";
		}else{
			echo "Total Keys in <strong>Redis</strong>: <strong>" . number_format( $total_keys ) . "</strong>";
		} 
		echo "</div>";
		
		$keys = array();
		$keys2 = array();
		
		for($i=0;$i<sizeof($list)&& $i<500000;$i++){
			//echo "<BR>" . $list[ $i ];
			$k = $list[ $i ];
			if( $_GET['showgroup'] ){
				$k = str_replace( $_GET['showgroup'].":", "", $k );
			}
			$l = explode( ":", $k );
			if( sizeof( $l ) >= 2 ){
				if( !$keys[ $l[0] ] ){
					$keys[ $l[0] ] = array();
				}
				if( $_GET['ifrno'] ){
					
					//$keys[ implode( ":", array_slice( $l, 0, sizeof($l)-1 ) ) ][] = 1;
					//echo sizeof( $l );
					if( sizeof( $l ) >= 5 ){
						//$keys[ $l[0] ][ $l[1] ][ $l[2] ][ $l[3] ][]  = ($l[4]?":".$l[4]:"") . ($l[5]?":".$l[5]:"") . ($l[6]?":".$l[6]:"");
						$keys[ implode( ":", array_slice( $l, 0, sizeof($l)-4 ) ) ][] = 1;
					}else if( sizeof( $l ) >= 4 ){
	 					//$keys[ $l[0] ][ $l[1] ][ $l[2] ][ $l[3] ][] = ($l[4]?":".$l[4]:"") . ($l[5]?":".$l[5]:"") . ($l[6]?":".$l[6]:"");
						$keys[ implode( ":", array_slice( $l, 0, sizeof($l)-3 ) ) ][] = 1;
					}else if( sizeof( $l ) >= 3 ){
						//$keys[ $l[0] ][ $l[1] ][ $l[2] ][] = ($l[3]?":".$l[3]:"") . ($l[4]?":".$l[4]:"") . ($l[5]?":".$l[5]:"");
						$keys[ implode( ":", array_slice( $l, 0, sizeof($l)-2 ) ) ][] = 1;
					}else{
						//$keys[ $l[0] ][] = $l[1] . ($l[2]?":".$l[2]:"") . ($l[3]?":".$l[3]:"") . ($l[4]?":".$l[4]:"");
						$keys[ implode( ":", array_slice( $l, 0, sizeof($l)-1 ) ) ][] = 1;						
					}
				}else{
					$keys[ $l[0] ][] = 1;
				}
			}else{
				$keys2[] = $k;
			}
		}
		
		/*
		echo "<pre>";
		print_r( $keys );
		echo "</pre>";
		*/
		
		function print_keys($kk, $p){
			global $ifrno;
			global $dbno;
			ksort($kk);
			foreach ((array) $kk as $i=>$j ){
				if( is_array( $j ) ){
					if( $j[0] ){
						$gr = ($_GET['showgroup']?$_GET['showgroup'].":":"").($p!=""?$p . ":":"") . $i ;
						echo "<tr bgcolor=white ><td><a target='ifr".$ifrno."' href='?dbno=".$dbno."&ifrno=".$ifrno."&showgroup=". $gr . "' >" . $gr . "</a></td><td>" . sizeof( $j ) . "</td></tr>";
					}else{
						//echo "<div> print_keys " . $i . " : " . $p . " :  =" . ($p!=""?$p.":":"") . $i . "=</div>";
						print_keys( $j , ($p!=""?$p . ":":"") . $i );
					}
				}else{
					echo "<tr><td>==" . $i . "</td><td>" . $j . "</td></tr>";
				}  
			}
		}
	
		if( sizeof($keys) ){
			echo '<table cellpadding="5" cellspacing="1" bgcolor="#cdcdcd" width="100%">';
			echo "<tr bgcolor=white><td><strong>Group</strong></td><td><strong>Count of keys</strong></td></tr>";
			print_keys( $keys, "" );
			echo "</table>";
		}
		
		
		asort( $keys2 );
		$perpage = 500;
		$tot = sizeof($keys2);
		$pages = $tot/$perpage;
		$p = $_GET['p']?$_GET['p']:1;
		$start = (($p-1)*$perpage);
		$end = $start + $perpage;
		if( $end > $tot ){
		   $end = $tot;
		}
	
		//echo "<p>&nbsp;</p>";
		echo "<form action=\"\" method=\"get\" >";
		echo "<div>Search: <input type=\"text\" name=\"search\" style=\"width:100px;\" value=\"\" ><input type=\"submit\" name=\"btn\" value=\"Go\" onclick='_searchnow()' ></div>";
		echo "<input type=\"hidden\" name=\"showgroup\" value=\"".$_GET['showgroup']."\" >";
		echo "<input type=\"hidden\" name=\"ifrno\" value=\"".$_GET['ifrno']."\" >";
		echo "<input type=\"hidden\" name=\"dbno\" value=\"".$dbno."\" >";
		echo "</form>";
		echo "<div>Displaying: " . $start . " - " . $end . " of " . $tot . " Keys</div>";
	
		if( $tot > $perpage ){
			echo "<div>";
			if( $p > 1 ){
				echo "<a href=\"?dbno=".$dbno."&ifrno=".$_GET['ifrno']."&showgroup=".$_GET['showgroup']."&search=".$_GET['search']."&p=".($p-1)."\" >Previous</a>";
			}if( $p < $pages ){
				echo "&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"?dbno=".$dbno."&ifrno=".$_GET['ifrno']."&showgroup=".$_GET['showgroup']."&search=".$_GET['search']."&p=".($p+1)."\" >Next</a>";
			}
			echo "</div>";
		}
		echo '<table cellpadding="5" cellspacing="1" bgcolor="#cdcdcd" >';
		echo "<tr bgcolor='white'><td nowrap>SNo</td><td nowrap>Key</td><td nowrap>Key Type</td></tr>";
		$cnt = $start;
		foreach ((array) $keys2 as $i=>$j ){
			if( $i >= $start && $i <= $end ){
				$a_key = ($_GET['showgroup']?$_GET['showgroup'].":":"").$j;
				echo "<tr bgcolor='white'><td nowrap>".($cnt)."</td><td nowrap><a target='ifr".$ifrno."' href='?dbno=".$dbno."&ifrno=".$ifrno."&showkey=".$a_key."' >" . $j . "</a></td><td nowrap>" . v_type( $redis->type($a_key) ) . "</td></tr>";
				$cnt++;
			} 
		}
		echo "</table>";

	}else{

		$total_keys = $redis->dbsize();

		if( !$_GET['showgroup'] ){
			for( $i=0;$i<=5;$i++){
				?><a style="border:1px solid #cdcdcd; padding:5px; margin;2px; background-color:#f0f0f0;" href="?dbno=<?=$i ?>" ><?=$i ?></a>&nbsp;<?php
			}
		}
		
		echo "<div style='line-height:30px; border-bottom:2px solid #cdcdcd; font-size:14px; ' >";
		echo "Total Keys in <strong>Redis</strong>: <strong>" . number_format( $total_keys ) . "</strong>";
		echo "</div>";
		
		$keys = array();
		$keys2 = array();
		
                
		$lt = null;
		while( $keys_ = $redis->scan($lt, "", 5000) ){
			foreach ((array) $keys_ as $ii=>$k ){
	 	
			//	echo "<BR>" . $list[ $i ];
				$k = $k;
				$l = explode( ":", $k );
				if( sizeof( $l ) >= 2 ){
					if( !$keys[ $l[0] ] ){
						$keys[ $l[0] ] = 0;
					}
					$keys[ $l[0] ] += 1;
				}else{
					$keys2[] = $k;
				}
			}
			if( sizeof( $keys2 ) > 100000 ){
				echo "<div>Too many keys without prefix!</div>";
				break;
			}
		} 
                
                
               // $keys = $redis->hgetall('cfgr:k:'.$dbno.':grp');
		//$keys2 = $redis->hgetall('cfgr:k:'.$dbno.':sng');
	
		echo '<table cellpadding="5" cellspacing="1" bgcolor="#cdcdcd" width="100%">';
		echo "<tr bgcolor=white><td><strong>Group</strong></td><td><strong>Count of keys</strong></td></tr>";
		foreach ((array) $keys as $group=>$cnt ){
			echo "<tr bgcolor=white ><td><a target='ifr".$ifrno."' href='?dbno=".$dbno."&ifrno=".$ifrno."&showgroup=". $group . "' >" . $group . "</a></td><td align=right >" . $cnt . "</td></tr>";
		}
		echo "</table>";

		asort( $keys2 );
		$perpage = 500;
		$tot = sizeof($keys2);
		$pages = $tot/$perpage;
		$p = $_GET['p']?$_GET['p']:1;
		$start = (($p-1)*$perpage);
		$end = $start + $perpage;
		if( $end > $tot ){
		   $end = $tot;
		}
	
		//echo "<p>&nbsp;</p>";
		echo "<form action=\"\" method=\"get\" >";
		echo "<div>Search: <input type=\"text\" name=\"search\" style=\"width:100px;\" value=\"\" ><input type=\"submit\" name=\"btn\" value=\"Go\" onclick='_searchnow()' ></div>";
		echo "<input type=\"hidden\" name=\"showgroup\" value=\"".$_GET['showgroup']."\" >";
		echo "<input type=\"hidden\" name=\"ifrno\" value=\"".$_GET['ifrno']."\" >";
		echo "<input type=\"hidden\" name=\"dbno\" value=\"".$dbno."\" >";
		echo "</form>";
		echo "<div>Displaying: " . $start . " - " . $end . " of " . $tot . " Keys</div>";
	
		if( $tot > $perpage ){
			echo "<div>";
			if( $p > 1 ){
				echo "<a href=\"?dbno=".$dbno."&ifrno=".$_GET['ifrno']."&showgroup=".$_GET['showgroup']."&search=".$_GET['search']."&p=".($p-1)."\" >Previous</a>";
			}if( $p < $pages ){
				echo "&nbsp;&nbsp;|&nbsp;&nbsp;<a href=\"?dbno=".$dbno."&ifrno=".$_GET['ifrno']."&showgroup=".$_GET['showgroup']."&search=".$_GET['search']."&p=".($p+1)."\" >Next</a>";
			}
			echo "</div>";
		}
		echo '<table cellpadding="5" cellspacing="1" bgcolor="#cdcdcd" >';
		echo "<tr bgcolor='white'><td nowrap>SNo</td><td nowrap>Key</td><td nowrap>Key Type</td></tr>";
		$cnt = $start;
		foreach ((array) $keys2 as $i=>$j ){
			if( $i >= $start && $i <= $end ){
				$a_key = ($_GET['showgroup']?$_GET['showgroup'].":":"").$j;
				echo "<tr bgcolor='white'><td nowrap>".($cnt)."</td><td nowrap><a target='ifr".$ifrno."' href='?dbno=".$dbno."&ifrno=".$ifrno."&showkey=".$a_key."' >" . $j . "</a></td><td nowrap>" . v_type( $redis->type($a_key) ) . "</td></tr>";
				$cnt++;
			} 
		}
		echo "</table>";
	}
	
}
?></div>
</td>

<?php
if(!isset($_GET['dbno'])){
echo "<td>";	
echo "<pre>";
print_r($redis_db_details);
echo "</pre>";
echo "Application + Session Redis are common";
echo "</td>";
}
?>	

<?php if( !$_GET['showkey'] ){ ?><td><iframe style="width:99%; height:100px; border:0px; solid #cdcdcd;" border='0' frameborder="0" marginwidth="0" marginheight='0' src="" name="ifr<?=$ifrno ?>" id="ifr<?=$ifrno ?>" ></iframe></td><?php } ?>
</tr>
</table> 
<script language="javascript">
	document.getElementById("loa").style.display = 'none';
	
	var pageWidth = 0; var pageHeight = 0; 
	var pageVisibleWidth = 0; var pageVisibleHeight = 0; 
	var pageScrollHeight = 0; var pageScrollWidth = 0; 
	var isitIE = "y"; 
	function FindSize(){
		if( window.innerHeight && window.scrollMaxY ){ isitIE ='n'; pageWidth = window.innerWidth + window.scrollMaxX;  pageHeight = window.innerHeight + window.scrollMaxY; }
		else if( document.body.scrollHeight > document.body.offsetHeight ){ isitIE = "n"; pageWidth = document.body.scrollWidth; pageHeight = document.body.scrollHeight; }
		else{ isitIE = 'y'; pageWidth = document.body.offsetWidth + document.body.offsetLeft; pageHeight = document.body.offsetHeight + document.body.offsetTop; }
		return isitIE ;
	}
	function FindVisibleSize()
	{
		if( typeof( window.innerWidth ) == 'number' ) { pageVisibleWidth = window.innerWidth; pageVisibleHeight = window.innerHeight; }
		else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) { pageVisibleWidth = document.documentElement.clientWidth; pageVisibleHeight = document.documentElement.clientHeight; }
		else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) { pageVisibleWidth = document.body.clientWidth; pageVisibleHeight = document.body.clientHeight; }
	}
	function FindScrollXY()
	{
	  if( typeof( window.pageYOffset ) == 'number' ) { 	pageScrollHeight = window.pageYOffset; pageScrollWidth = window.pageXOffset; }
	  else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) { pageScrollHeight = document.body.scrollTop; pageScrollWidth = document.body.scrollLeft;  }
	  else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) { pageScrollHeight = document.documentElement.scrollTop; pageScrollWidth = document.documentElement.scrollLeft; }
	}

	vv = "ifr<?=$ifrno ?>";
	FindVisibleSize();
	try{document.getElementById( vv ).style.height = (pageVisibleHeight - 2)+"px";}catch(e){}
	try{document.getElementById( "left_td" ).style.height = (pageVisibleHeight - 10)+"px";}catch(e){}
	try{document.getElementById( "sp_value_view").style.height = (pageVisibleHeight - 120)+"px";}catch(e){}
	try{document.getElementById( "sp_value_view").style.width = (pageVisibleWidth - 35)+"px";}catch(e){}
	try{
		s = document.getElementsByTagName( "div" );
		for( i=0;i<s.length;i++ ){
			if( s[i].className == "spd" ){
				s[i].style.width=(pageVisibleWidth-210)+"px";
			}
		} 
	}catch(e){alert(e);}

</script>
</body>
</html>