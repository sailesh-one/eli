<?php
include('config.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type='text/css'>
        *{font-family:arial; font-size:14px;}
        .minitable{border:1px #ddd solid; border-collapse: collapse;}
        .minitable thead{background:#eee; padding:5px;}
        .minitable thead th, .minitable td{padding: 6px; border-bottom:1px #ddd solid; border-right:1px #ddd solid;}
        a{color:#333;}
		.left{float:left;width:33.3%;}   
		li{line-height:30px;}   
		a{text-decoration: none;}
		
</style>



<title>Tools related files </title>
<meta robots="noindex,nofollow">
</head>
<body>

<h3 style="text-align:center">Tools Related Files </h3>

	<table class='minitable left'>
		<thead><th>Important Tools</th></thead>
		<tr><td><a target='_blank' href='tool_phpinfo.php' >PHP Info</a></td></tr>			
		<tr><td><a target='_blank' href='tool_compare_db.php' >Compare DB</a></td></tr>					
		<tr><td><a target='_blank' href='api_server_monitor.php' >API Server Monitor</a></td></tr>
		<tr><td><a target='_blank' href='tool_mysql_stats.php' >MySql Stats</a></td></tr>
		<tr><td><a target='_blank' href='tool_redis_db_overview_total.php' >Redis Total Overview</a></td></tr>	
	</table>	

	<table class='minitable left'>
		<thead><th>Dev Related</th></thead>
		<tr><td><a target='_blank' href='tool_error_log_new.php' >Error Log</a></td></tr>
		<tr><td><a target='_blank' href='tool_findfiles.php' >Find Files</a></td></tr>
		<tr><td><a target='_blank' href='tool_redis.php' >Tools Redis</a></td></tr>				
		<?php if($env_server == "dev" ){ ?>		
		<!-- <tr><td><a target='_blank' href='_MASTER_PMA/' >PMA</a></td></tr>							 -->
		<!--<tr><td><a target='_blank' href='uat_new/' >New UAT PMA</a></td></tr>
		<tr><td><a target='_blank' href='tool_redis_uat.php' >Tools Redis New UAT</a></td></tr>-->
		<?php } ?>		
		<tr><td><a target='_blank' href='tool_sqlerrors_log.php' >MYSQL Error Log</a></td></tr>		
		<tr><td><a target='_blank' href='tool_sendgrid_logs.php' >Sendgrid Log</a></td></tr>	
		<tr><td><a target='_blank' href='tool_sms_log.php' >SMS Log</a></td></tr>			
		<tr><td><a target='_blank' href='tool_actions_log.php' >Actions Log</a></td></tr>	
		<tr><td><a target='_blank' href='tool_api_req_resp_log.php' >API Request & Response Log</a></td></tr>		
		<!-- <tr><td><a target='_blank' href='tool_keywise_action_log.php' >Keywise Actions Log</a></td></tr> -->
	</table>	
	<table class='minitable left'>
		<thead><th>Testing Tools</th></thead>		
		<tr><td><a target='_blank' href='tool_dealer_logins.php' >Dealers List</a></td></tr>		
		<!-- <tr><td><a target='_blank' href='tool_finddealer_url.php'>Dealer URLs</a></td></tr>				 -->
		<tr><td><a target='_blank' href='tool_branches_list.php' >Branches List</a></td></tr>		
		<tr><td><a target='_blank' href='tool_branches_executives_mapping.php' >Branches Executives Mapping</a></td></tr>	
		<tr><td><a target='_blank' href='tool_buyleads.php' >Latest SM Leads (Buyleads)</a></td></tr>		
		<tr><td><a target='_blank' href='tool_sellleads.php' >Latest PM Leads (Sellleads)</a></td></tr>						
		<!-- <tr><td><a target='_blank' href='tool_request_call_back_leads.php' >Request Call Back Leads </a></td></tr>-->
		<!-- <tr><td><a target='_blank' href='tool_certification_status_check.php' >Certification Status Check </a></td></tr>	
		<tr><td><a target='_blank' href='tool_inventory_certification_status_check.php' > Inventory Certification Status Check </a></td></tr>	
		<tr><td><a target='_blank' href='tool_dealer_shared_stock.php' > Dealers Stock in Website </a></td></tr>
		<tr><td><a target='_blank' href='tool_demo_dealer_stock_in_usedcars.php' > Demo Dealers Stock in Website </a></td></tr>
		<tr><td><a target='_blank' href='tool_gfv_contract_cases.php' > GFV contract cases </a></td></tr>
		<tr><td><a target='_blank' href='tool_exchange_module.php' >Exchange Module</a></td></tr>
		<tr><td><a target='_blank' href='tool_stock_ready.php' >Reserve Now Eligible Stocks</a></td></tr>
		<tr><td><a target='_blank' href='tool_inventory_certification_date.php' >Tools Inventory Certification Date</a></td></tr> -->

	</table>	

</body>
</html>
