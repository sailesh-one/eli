<?php
/*
Security class Created on Apr 04 2017 - fz
*/
function isitin($ndl, $hstck)
{
    foreach ($hstck as $bypass) 
    {
       if (stripos(strtolower(trim($ndl)), strtolower(trim($bypass))) !== false) 
       {
          return true;
       }
    }
    return false;
}

 //#[AllowDynamicProperties]
class SECURITY
{
    /**
     * [__construct description]
     */
	public $filters;
	public $msg;
	public $op;
	public $opmt;
	public $block;
	public $white_list_files;
	public $logfile;
	public $sts_tble;
	public $redis_bmw;
	public $_redip;
	public $_redport;
	public $connection_redis_bmw;
	public $redis_ip_blocked;
	public $actionslog_server;
	public $stats_conn;
	public $trusted_hosts;
	public $_ignore_keys;
	public $mime_file_exe;
	public $preg_block_file_names;
	public $data;
	public $_key;
	public $push_events_redis_connection;
	public $preg_black_file_names;
	public $con_redis_security;
	public $bypassed_fields;

    
	function __construct(){
		global $con_redis_security;
		$this->filters = 
			[
				'onabort(|\ |\t)+\=',
				'onafterprint(|\ |\t)+\=',
				'onbeforeprint(|\ |\t)+\=',
				'onbeforeunload(|\ |\t)+\=',
				'onblur(|\ |\t)+\=',
				'oncanplay(|\ |\t)+\=',
				'oncanplaythrough(|\ |\t)+\=',
				'onchange(|\ |\t)+\=',
				'onclick(|\ |\t)+\=',
				'oncontextmenu(|\ |\t)+\=',
				'oncopy(|\ |\t)+\=',
				'oncuechange(|\ |\t)+\=',
				'oncut(|\ |\t)+\=',
				'ondblclick(|\ |\t)+\=',
				'ondrag(|\ |\t)+\=',
				'ondragend(|\ |\t)+\=',
				'ondragenter(|\ |\t)+\=',
				'ondragleave(|\ |\t)+\=',
				'ondragover(|\ |\t)+\=',
				'ondragstart(|\ |\t)+\=',
				'ondrop(|\ |\t)+\=',
				'ondurationchange(|\ |\t)+\=',
				'onemptied(|\ |\t)+\=',
				'onended(|\ |\t)+\=',
				'onerror(|\ |\t)+\=',
				'onfocus(|\ |\t)+(\=|\:)',
				'onhashchange(|\ |\t)+\=',
				'oninput(|\ |\t)+\=',
				'oninvalid(|\ |\t)+\=',
				'onkeydown(|\ |\t)+\=',
				'onkeypress(|\ |\t)+\=',
				'onkeyup(|\ |\t)+\=',
				'onload(|\ |\t)+\=',
				'onloadeddata(|\ |\t)+\=',
				'onloadedmetadata(|\ |\t)+\=',
				'onloadstart(|\ |\t)+\=',
				'onmessage(|\ |\t)+\=',
				'onmousedown(|\ |\t)+\=',
				'onmousemove(|\ |\t)+\=',
				'onmouseout(|\ |\t)+\=',
				'onmouseover(|\ |\t)+\=',
				'onmouseup(|\ |\t)+\=',
				'onmousewheel(|\ |\t)+\=',
				'onoffline(|\ |\t)+\=',
				'ononline(|\ |\t)+\=',
				'onpagehide(|\ |\t)+\=',
				'onpageshow(|\ |\t)+\=',
				'onpaste(|\ |\t)+\=',
				'onpause(|\ |\t)+\=',
				'onplay(|\ |\t)+\=',
				'onplaying(|\ |\t)+\=',
				'onpopstate(|\ |\t)+\=',
				'onprogress(|\ |\t)+\=',
				'onratechange(|\ |\t)+\=',
				'onreset(|\ |\t)+\=',
				'onresize(|\ |\t)+\=',
				'onscroll(|\ |\t)+\=',
				'onsearch(|\ |\t)+\=',
				'onseeked(|\ |\t)+\=',
				'onseeking(|\ |\t)+\=',
				'onselect(|\ |\t)+\=',
				'onshow(|\ |\t)+\=',
				'onstalled(|\ |\t)+\=',
				'onstorage(|\ |\t)+\=',
				'onsubmit(|\ |\t)+\=',
				'onsuspend(|\ |\t)+\=',
				'ontimeupdate(|\ |\t)+\=',
				'ontoggle(|\ |\t)+\=',
				'onunload(|\ |\t)+\=',
				'onvolumechange(|\ |\t)+\=',
				'onwaiting(|\ |\t)+\=',
				'onwheel(|\ |\t)+\=',
				'ondblclick(|\ |\t)+\=',
				'ondrag(|\ |\t)+\=',
				'oncut(|\ |\t)+\=',
				'onwheel(|\ |\t)+\=',
				'select\(',
                'select \(',
                'RLIKE',
				'update\(',
				'delete\(',
				'information\_schema',
				'group\_concat',
				'drop table',
				'union[\ ]+select',
				'select database',
				'\?\>',
				'exec master\.dbo\.xp\_dirtree',
				'SELECT \(CASE WHEN \(var\_dump\(',
				'DBMS\_PIPE\.RECEIVE\_MESSAGE',
				'while\(',
				'do\{',
				'function\(',
				'order by',
				'Date\(',
				'waitfor delay',
				'select\*from',
				'sleep\(',
				'version\(',
				'const\(',
				'char\(',
				'\)\=\(',
				'select \* ',
				'\;([\ \*\/a-z0-9\!]{1,10})(select|insert|delete|show|alter)', // remove update on may2nd as requests gettings blocked
				'dATABASE([\(\ ]+)',
				'database\(',
				'IFNULL',
				'HEX\(IFNULL\(CAST',
				'\ UNION\ ALL',
				'eval\(',
				'base64\_decode',
				'include\(',
				'\_config',
				'\_functions',
				'pass\_decrypt',
				'\$\_post',
				'\<script',
				'\.cookie',
				'alert\(',
				'javascript',
				'\<META HTTP',
				'\<META',
				'iframe',
				'\<\?php',
				'\?\>',
				'base64',
				'prompt\(',
				'\<svg',
				'onclick\(',
				'\.location',
				'^document\.',
				'location\.href',
				'window\.open',
				'\<style',
				'\<span',
				'\<a href',
				'\<div',
				'\<img',
				'\<layer',
				'\<xss',
				'\<xml',
				'\<form',
				'\<input',
				'passthru',
				'shell\_exec',
				'master.dbo',
				'declare \@',
				'varchar',
				'\<xmltype',
				'extractvalue',
				'load_file',
				'convert\(',
				'\.\.\/\.\.\/',
				'\$\_server',
				'\<body',
				'onload\=',
				'onerror\=',
				'src\=',
				'src(|\ |\t)+\=',
				'dynsrc(|\ |\t)+\=',
				'lowsrc(|\ |\t)+\=',
				'FSCommand(|\ |\t)+\(',
				'confirm\(',
				'(alert|confirm|prompt)(|\ )+\(.*?\)',
				'\+\[\]\)', // new payload from BMW
				'&#97&#108;&#101;&#114;&#116;', //hex code alert
				'window\[',
				'(select|update|delete|drop)(|\ )+\/\*(?:.*?)\*\/',
				'^update(?:.*?)set',
				'^delete(|\ )+from',
				'window(?:.*?)\[',
				'window\/\*(?:.*?)\*\/\[',
				'\/\*(.*?)\*\/',
				// '\/\*',
				'\*\/',
				// '\[\'',
				// '\[\"',
				// '\"\]',
				// '\'\]',
				'top\[',
				'find\/\*(?:.*?)\*\/\(',
				'contenteditable',
				'ondrag',
				'bit_count',
				'ascii',
				'MID(|\ )+\(',
				'user\(\)',
				'\{0\}',
				'\>\>',
				'query\.format',
				'if\(',
				'exp\(',
				'\[0x0a\]',
				'burpcollab',
				'BENCHMARK\(',
				'exp\('
			];
			$this->push_events_redis_connection = false;
			$this->msg              = array();
			$this->op               = null;
			$this->opmt             = null;
			$this->block            = false;
			$this->white_list_files = ['csv','gif','jpeg','jpg','png','pdf','xls','xlsx','doc','docx'];
			$this->mime_file_exe = array(
				'doc' => array('application/msword', 'application/octet-stream'),
				'docx' => array('application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
				'xls' => array('application/excel', 'application/vnd.ms-excel', 'application/x-excel',
						'application/x-msexcel', 'application/octet-stream'),
				'xlsx' => array('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','application/octet-stream'),
				'csv' => array('text/csv'),
				'pdf'  => array('application/octet-stream','application/zip', 'application/pdf'),
				'jpg'  => array('image/jpeg'),
				'jpeg'  => array('image/jpeg'),
				'png'  => array('image/png'),
				'gif'  => array('image/gif')
				);
			$this->preg_black_file_names = "/\.(php|asp|htm|html|exe|js|ts|sql|css|zip)/i";
			$this->logfile          = "";
			$this->sts_tble         = "actions_log_".date("Y_m_d");
			$this->con_redis_security = false;
			$this->_ignore_keys = ["series_code","event_terms","mail_subject","cars_message","auction_remarks","location_sub","message"];
			$this->bypassed_fields = ["statustext"=>"\<a href"];
	}
  

    /**
	 * [filter description]
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function filter($data){
		foreach ( (array) $data as $key => $value) {
			if(is_array($value))
				$this->filter($value);
			// if(in_array($key, $this->_ignore_keys)) continue;
			if (isitin($key,$this->_ignore_keys)) continue;
			$this->data = $value;
			$this->_key = $key;
			$this->xss();
			/**checking Keys START**/
			$this->data = $key;
			$this->_key = $key;
			$this->xss();
			/**checking Keys END**/
		}
	}
	/**
	 * [xss description]
	 * @return [type] [description]
	 */
	function xss(){
		if(is_array($this->data)){
			return true;
		}

		// foreach ($this->filters as $fltr) {
        //     if(preg_match("/".$fltr."/i", $this->data , $mtc)){
        //         $this->op[$this->_key] = $this->data;
        //         $this->opmt[$this->_key] = $fltr;
        //         if($this->bypassed_fields[trim($this->_key)] <> trim($fltr))
        //             $this->block = true;
        //         else
        //             $this->block = false;
        //         // $this->msg[] = "Filters Matched : $fltr";    
        //     }
        // }

		foreach ($this->filters as $fltr) {
			// Ensure subject passed to preg_match is a string (avoid PHP 8.1+ deprecation when null)
			$subject = $this->data ?? '';
			// If data is not a scalar (array/object), normalize to empty string
			if (!is_scalar($subject)) {
				$subject = '';
			}
			$subject = (string)$subject;
			if(preg_match("/".$fltr."/i", $subject , $mtc)){
				$this->op[$this->_key] = $this->data;
				$this->opmt[$this->_key] = $fltr;
				if($this->bypassed_fields[trim($this->_key)] <> trim($fltr))
					$this->block = true;
				else
					$this->block = false;
				// $this->msg[] = "Filters Matched : $fltr";	
			}
		}
	}
	/**
	 * [fileUpload description]
	 * @return [type] [description]
	 */
	function fileUpload(){
		$global_file_size = 10; //10MB
		if(isset($_FILES))
		{
			foreach ($_FILES as $key => $value) {
				if(is_array($value['name']) && count($value['name']) > 0)
				{
					foreach ($value['name'] as $img_key => $filetyp) {
					if ($_FILES[$key]['size'][$img_key]!=0) {
						$_FILES[$key]['name'][$img_key] = htmlspecialchars($filetyp,ENT_QUOTES,'UTF-8');
						$_xt = strtolower(pathinfo($filetyp)['extension']);
						if( $_xt && !in_array(trim($_xt),$this->white_list_files) ){
							$_block_request = true;
						}else{
							$tempname = $value['tmp_name'][$img_key];
							$finfo = finfo_open( FILEINFO_MIME_TYPE );
							$mime_type = finfo_file( $finfo, $tempname );
							finfo_close( $finfo );
							if($mime_type && !in_array(trim($mime_type), $this->mime_file_exe[$_xt])){
								$_block_request = true;
							}

							$_basename = strtolower(pathinfo($filetyp)['filename']);
							if(preg_match($this->preg_black_file_names, $_basename)){
								$_block_request = true;
							}

							$input_file_size_kb = round($value['size'][$img_key]/1024, 0);
							$input_file_size_mb = round($input_file_size_kb/1024, 2);
							if($input_file_size_mb > $global_file_size){
								$_block_request = true;
							}
						}
					}

				   }
				}else{
					if($value['name'] !=''){
						$_xt = strtolower(pathinfo($value['name'])['extension']);
						$_FILES[$key]['name'] = htmlspecialchars($value['name'],ENT_QUOTES,'UTF-8');
						if( $_xt && !in_array(trim($_xt),$this->white_list_files)){
							$_block_request = true;
						} else {
							$tempname = $value['tmp_name'];
							$finfo = finfo_open( FILEINFO_MIME_TYPE );
							$mime_type = finfo_file( $finfo, $tempname );
							finfo_close( $finfo );
							if($mime_type && !in_array(trim($mime_type), (array)$this->mime_file_exe[$_xt])){
								$_block_request = true;
							}

							$_basename = strtolower(pathinfo($value['name'])['filename']);
							if(preg_match($this->preg_black_file_names, $_basename)){
								$_block_request = true;
							}

							$input_file_size_kb = round($value['size']/1024, 0);
							$input_file_size_mb = round($input_file_size_kb/1024, 2);
							if($input_file_size_mb > $global_file_size){
								$_block_request = true;
							}
						}
					}	
				}
			}
			if(isset($_block_request) && $_block_request === true)
			{
				$this->block = true;
				$this->msg[] = "Blocked Arbitary Fileuploads";	
			}
		}
	}


    /**
     * [actionlog description]
     * @return [type] [description]
     */
    function actionlog()
    {
        $_n = 0;
        if (!file_exists($this->logfile)) {
            $_n = 1;
        }
        $fp = @fopen($this->logfile, "a");
        if (!$fp) {
            error_log("File open failed at common_security_class.php (".$this->logfile.")");
        } else {
            if ($_n) {
                $_tblstart = '<table style="text-align:center;width:100%;border-collapse:collapse;font-size: 12px;border-bottom: 1px solid gray;" cellpadding="0" cellspacing="0" border=1>';
                $_tblstart .= '<tr><th>TIME</th><th>REQUEST METHOD</th><th>Action</th><th>Host</th><th>IP address</th><th>URL</th><th>Info</th><th>Msg</th></tr>';
                @fwrite($fp, $_tblstart);
            }
            $_m_s_g = "<tr>";
            if ($this->block) {
                $_m_s_g .= "<td>".Date('Y-m-d H:i:s')."</td>";
                $_m_s_g .= "<td>".$_SERVER['REQUEST_METHOD']."</td><td>blocked</td>";
                $_m_s_g .= "<td>".$_SERVER['HTTP_HOST']."</td>";
                $_m_s_g .= "<td><a target='_new' style='font-weight:bold;' href='https://ipinfo.io/".getUserIPAddress()."'>".getUserIPAddress()."</a></td>";
                $_m_s_g .= "<td><div  style='width:600px;overflow:auto;'>".htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES)."</div></td>";
            }
            if (is_array($this->op)) {
                $_m_s_g .= "<td><table width='100%'><tr><td width='10%' style='font-size:11px;font-weight:bold;'>key</td><td width='15%'  style='font-size:11px;font-weight:bold;'>Regex</td><td style='font-size:11px;font-weight:bold;'>value</td></tr>";
                foreach ($this->op as $k => $_op) {
                    $_m_s_g .= "<tr><td style='color:brown;font-size:10px;'>".htmlspecialchars($k, ENT_QUOTES)." </td><td style='color:brown;font-size:10px;'> ".$this->opmt[$k].".</td><td  style='color:brown;font-size:10px;'>".htmlspecialchars($_op, ENT_QUOTES)."</td></tr>";
                }
                $_m_s_g .= "</table></td>";
            }
            if (is_array($this->msg)) {
                $_m_s_g .= "<td><table width='100%'>";
                $_m_s_g .= "<tr><th>Msg</th></tr>";
                foreach (array_unique($this->msg) as $_msg) {
                    $_m_s_g .="<tr><td>".htmlspecialchars($_msg)."</td></tr>";
                }
                $_m_s_g .= "</table></td>";
            }
            $_m_s_g .= '</td></tr>';
            @fwrite($fp, $_m_s_g."\n");
            @fclose($fp);
        }
    }
  


    function logtable(){
        // global $actions_host, $actions_user, $actions_pass, $actions_db, $actions_port, $actionslog_server;
        // $actions_log_connection = @mysqli_init();
        // @mysqli_options( $actions_log_connection, MYSQLI_OPT_CONNECT_TIMEOUT, 3 );
        // mysqli_report(MYSQLI_REPORT_OFF);
        // @mysqli_real_connect( $actions_log_connection, $actions_host, $actions_user, $actions_pass, $actions_db, $actions_port );
        // $actions_log_error = '';
        // if( mysqli_connect_error() ){
        //     $actions_log_error = "Db connection Error: 111 " . mysqli_connect_error();
        // }
        // if( !$actions_log_connection ){
        //     $actions_log_error = "Db connection Error: 222 " . mysqli_connect_error();
        // }
        // if( $actions_log_error == "" ){
        //     $url = $_SERVER['REQUEST_URI'];
        //     if(!preg_match("/\/cron\_/",$url)){
        //         $_stable = "actions_log_".date("Y_m_d");
        //         $query = "insert into `".$_stable."` ( date, time, ip, domain, url, method, server, data, scriptname, blocked, action ) values (
        //         '" . date("Y-m-d") . "',
        //         '" . date("H:m:s") . "',
        //         '" . mysqli_escape_string( $actions_log_connection,getUserIPAdrss()) . "',
        //         '" . $_SERVER['HTTP_HOST'] . "', 
        //         '" . mysqli_escape_string( $actions_log_connection, $_SERVER['REQUEST_URI']) . "',
        //         '" . mysqli_escape_string( $actions_log_connection,$_SERVER['REQUEST_METHOD']) . "',
        //         '" . mysqli_escape_string( $actions_log_connection,$actionslog_server?$actionslog_server:"UNKNOWN" ) . "',
        //         '" . mysqli_escape_string( $actions_log_connection, str_replace( "\",","\",\n", json_encode( ($_SERVER['REQUEST_METHOD'] == "POST")?$_POST:$_GET ) ) ) . "', 
        //         '" . mysqli_escape_string( $actions_log_connection,$_SERVER['SCRIPT_NAME']) . "',
        //         '" . mysqli_escape_string( $actions_log_connection,$this->block?'1':'0') . "',
        //         '" . mysqli_escape_string( $actions_log_connection,$_POST['action'] ) . "'
        //         )";

        //         mysqli_query( $actions_log_connection, $query );
        //         if( @mysqli_error( $actions_log_connection ) ){
        //         if( !preg_match( "/(exist)/i", @mysqli_error( $actions_log_connection ) ) ){
        //                 $actions_log_error .= $query .";\n";
        //                 $actions_log_error .= "Action Log Insert Error: " . mysqli_error( $actions_log_connection );
        //             }else{
        //                 $_create_query = "CREATE TABLE IF NOT EXISTS `".$_stable."` (
        //                 `id` int(11) NOT NULL AUTO_INCREMENT,
        //                 `date` date NOT NULL,
        //                 `time` time NOT NULL,
        //                 `ip` varchar(20) NOT NULL,
        //                 `domain` varchar(50) NOT NULL,
        //                 `server` varchar(50) NOT NULL,
        //                 `url` text NOT NULL,
        //                 `method` varchar(4) NOT NULL,
        //                 `data` text NOT NULL,
        //                 `scriptname` varchar(200) NOT NULL,
        //                 `blocked` int(11) NOT NULL,
        //                 `action` varchar(20) NOT NULL,
        //                 PRIMARY KEY (`id`)
        //                 ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
        //                 @mysqli_query( $actions_log_connection, $_create_query );
        //                 if( @mysqli_error( $actions_log_connection ) ){
        //                 $actions_log_error .= $_create_query .";\n";
        //                 $actions_log_error .= "Action Log Create Error: " . mysqli_error( $actions_log_connection );
        //                 }
        //             }
        //         }
        //     }
        // }
        // if( isset($actions_log_error) && $actions_log_error == "" ){
        //     /* next things */
        //     }
        //  @mysqli_close( $actions_log_connection );
    }


   	/**
	 * [redis_ip_block description]
	 * @return [type] [description]
	 */
	function redis_ip_block( $action = false ){
		$key = "IP:BL:".$_SERVER['HTTP_HOST'].":".getUserIPAddress();
		if( $this->con_redis_security ){ 
			if( $action == "clear" ){
				$this->con_redis_security->del( $key );
				echo "cleared";
				exit;
			}
			$ip_requests_cnt = $this->con_redis_security->hLen($key);
			header("badcount:" . $ip_requests_cnt);
			if( $ip_requests_cnt > 20 ){
				$this->block = true;
				$this->redis_ip_blocked = true;
				header("http/1.1 429 Too Many Requests");
				include("page_400_2.php");
				exit;
			}else{
				if( $this->block ){
					$_rmsg  = "###Server: ".@json_encode( $_SERVER );
					$_rmsg .= "###SESSION:<br>".@json_encode( $_SESSION );
					$_rmsg .= "###REQUEST<br>".@json_encode( $_REQUEST );
					$_rmsg .= "###COOKIE<br>".@json_encode( $_COOKIE );
					$this->con_redis_security->hset( $key, date('YmdHisv'), $_rmsg );
					$this->con_redis_security->expire( $key, 900 ); //15 hour
				}
				return false;
			}
		}
	}
}
