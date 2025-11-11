<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/common/control_config.php";
global $connection;

echo "CRON INVOKED ON : " . date("Y-m-d h:i:s");
echo "<br>";
$insert_query='';
$totalbytes = memory_get_usage();
$mb = $totalbytes / (1024 * 1024);
$time_start = microtime(true);

$today = date("Y-m-d");
$yesterday = date("Y-m-d", strtotime("-1 days"));
$ytd_start_date = date("Y-01-01");
$mtd_start_date = date("Y-m-01");
//$last_week_dates = getLastNDays($yesterday,7, 'Y-m-d'); //last 7 dates

$make_ids = getJaguarMakeId();
$managers = [];
$qry = "SELECT 
        id,
        name,
        role_id,
        REPLACE(REPLACE(REPLACE(branch_id, '\"', ''), '[', ''), ']', '') AS branch_id
        FROM users
        WHERE role_id = 1 AND active = 'y'"; //UCM only access dashboard
$result = mysqli_query($connection, $qry);
while ($row = mysqli_fetch_assoc($result)) {
    $managers[] = $row;
}
$all_branches_data = [];
$insert_query = "INSERT INTO `dashboard_analytics` (usr_type,executive,type,dashbrd_data,created) VALUES ";
foreach ($managers as $i => $j) {
    $dealer = [];
    $y_bw_data = [];
    $m_bw_data = [];
    $ytd_costs_bw_data = [];
    $mtd_costs_bw_data = [];
    $tat_bw_data = [];
    $assign_branches = explode(",", $j["branch_id"]); //Dealer assigned branches
   
    $all_tat_trade_refu = 0;
    $all_tat_evalreq_evaldone = 0;
    $all_tat_eval_trade_done = 0;
    $all_tat_trad_in_ro = 0;
    $all_tat_ro_booking_days = 0;
    $all_tat_booking_to_dvry = 0;
    $all_tat_delivery_to_rc = 0;
    $all_tat_booking_deliv_avg = 0;
    $all_tat_delivery_avg = 0;
    $jlr_all_tat_trade_refu = 0;
    $jlr_all_tat_evalreq_evaldone = 0;
    $jlr_all_tat_eval_trade_done = 0;
    $jlr_all_tat_trad_in_ro = 0;
    $jlr_all_tat_ro_booking_days = 0;
    $jlr_all_tat_booking_to_dvry = 0;
    $jlr_all_tat_delivery_to_rc = 0;
    $jlr_all_tat_booking_deliv_avg = 0;
    $jlr_all_tat_delivery_avg = 0;
    $njlr_all_tat_trade_refu = 0;
    $njlr_all_tat_evalreq_evaldone = 0;
    $njlr_all_tat_eval_trade_done = 0;
    $njlr_all_tat_trad_in_ro = 0;
    $njlr_all_tat_ro_booking_days = 0;
    $njlr_all_tat_booking_to_dvry = 0;
    $njlr_all_tat_delivery_to_rc = 0;
    $njlr_all_tat_booking_deliv_avg = 0;
    $njlr_all_tat_delivery_avg = 0;
    $all_ytd_sales_sum = 0;
    $jlr_all_ytd_sales_sum = 0;
    $njlr_all_ytd_sales_sum = 0;
    $all_y_net_prf_sum = 0;
    $all_y_grs_prf_sum = 0;
    $all_m_net_prf_sum = 0;
    $all_m_grs_prf_sum = 0;
    $all_yb2b_net_prf_sum = 0;
    $all_yb2b_grs_prf_sum = 0;
    $all_mb2b_net_prf_sum = 0;
    $all_mb2b_grs_prf_sum = 0;

    if (count($assign_branches) > 0) {
        $ytd_counts = [];
        $mtd_counts = [];
        $ytd_costs = [];
        $mtd_costs = [];

        $all_ytd_eval_req = $all_ytd_eval_done = $all_ytd_tradein_done = $all_ytd_sales_leads = $all_ytd_trade_out = 0;
        $all_mtd_eval_req = $all_mtd_eval_done = $all_mtd_tradein_done = $all_mtd_sales_leads = $all_mtd_trade_out = 0;
        $all_ytd_tradeins_sum = $all_ytd_refurb_sum = $all_mtd_tradeins_sum = $all_mtd_refurb_sum = $all_mtd_sales_sum = 0;
        $all_trade_refu_days_sum = $all_eval_days_sum = $all_eval_trade_days_sum = 0;

        //JLR
        $jlr_all_ytd_eval_req = $jlr_all_ytd_eval_done = $jlr_all_ytd_tradein_done = $jlr_all_ytd_sales_leads = $jlr_all_ytd_trade_out = 0;
        $jlr_all_mtd_eval_req = $jlr_all_mtd_eval_done = $jlr_all_mtd_tradein_done = $jlr_all_mtd_sales_leads = $jlr_all_mtd_trade_out = 0;
        $jlr_all_ytd_tradeins_sum = $jlr_all_ytd_refurb_sum = $jlr_all_mtd_tradeins_sum = $jlr_all_mtd_refurb_sum = $jlr_all_mtd_sales_sum = 0;
        $jlr_all_trade_refu_days_sum = $jlr_all_eval_days_sum = $jlr_all_eval_trade_days_sum = 0;

        //Non JLR
        $njlr_all_ytd_eval_req = $njlr_all_ytd_eval_done = $njlr_all_ytd_tradein_done = $njlr_all_ytd_sales_leads = $njlr_all_ytd_trade_out = 0;
        $njlr_all_mtd_eval_req = $njlr_all_mtd_eval_done = $njlr_all_mtd_tradein_done = $njlr_all_mtd_sales_leads = $njlr_all_mtd_trade_out = 0;
        $njlr_all_ytd_tradeins_sum = $njlr_all_ytd_refurb_sum = $njlr_all_mtd_tradeins_sum = $njlr_all_mtd_refurb_sum = $njlr_all_mtd_sales_sum = 0;
        $njlr_all_trade_refu_days_sum = $njlr_all_eval_days_sum = $njlr_all_eval_trade_days_sum = 0;

        $all_tat_eval_trade_days_sum = $all_tat_eval_trade_days_req = $all_tat_trade_refu_days_sum = $all_tat_trade_refu_days_req = $all_tat_evalreq_evaldone_days_sum = $all_tat_evalreq_evaldone_days_req = $jlr_all_tat_evalreq_evaldone_days_sum = $jlr_all_tat_evalreq_evaldone_days_req = $njlr_all_tat_evalreq_evaldone_days_sum = $njlr_all_tat_evalreq_evaldone_days_req = $jlr_all_tat_eval_trade_days_sum = $jlr_all_tat_eval_trade_days_req = $njlr_all_tat_eval_trade_days_sum = $njlr_all_tat_eval_trade_days_req = $all_tat_trad_in_ro_days_sum = $all_tat_trad_in_ro_days_req = $jlr_all_tat_trad_in_ro_days_sum = $jlr_all_tat_trad_in_ro_days_req = $njlr_all_tat_trad_in_ro_days_sum = $njlr_all_tat_trad_in_ro_days_req = $all_tat_ro_booking_days_sum = $all_tat_ro_booking_days_req = $jlr_all_tat_ro_booking_days_sum = $jlr_all_tat_ro_booking_days_req = $njlr_all_tat_ro_booking_days_sum = $njlr_all_tat_ro_booking_days_req = 0;

        $all_tat_booking_to_dvry_days_sum = $all_tat_booking_to_dvry_days_req = $jlr_all_tat_booking_to_dvry_days_sum = $jlr_all_tat_booking_to_dvry_days_req = $njlr_all_tat_booking_to_dvry_days_sum = $njlr_all_tat_booking_to_dvry_days_req = $all_tat_delivery_to_rc_days_sum = $all_tat_delivery_to_rc_days_req = $jlr_all_tat_delivery_to_rc_days_sum = $jlr_all_tat_delivery_to_rc_days_req = $njlr_all_tat_delivery_to_rc_days_sum = $njlr_all_tat_delivery_to_rc_days_req = $all_tat_booking_deliv_avg_days_sum = $all_tat_booking_deliv_avg_days_req = $jlr_all_tat_booking_deliv_avg_days_sum = $jlr_all_tat_booking_deliv_avg_days_req = $njlr_all_tat_booking_deliv_avg_days_sum = $njlr_all_tat_booking_deliv_avg_days_req = $all_tat_delivery_avg_days_sum = $all_tat_delivery_avg_days_req = $jlr_all_tat_delivery_avg_days_sum = $jlr_all_tat_delivery_avg_days_req = $njlr_all_tat_delivery_avg_days_sum = $njlr_all_tat_delivery_avg_days_req = 0;

        for ($a = 0; $a < count($assign_branches); $a++) {
            $dealer_data = [];
            $y_net_prf_sum = $y_grs_prf_sum = 0;
            $m_net_prf_sum = $m_grs_prf_sum = 0;
            $yb2b_net_prf_sum = $yb2b_grs_prf_sum = 0;
            $mb2b_net_prf_sum = $mb2b_grs_prf_sum = 0;

            $ytd_tradeins_sum = $ytd_refurb_sum = $ytd_sales_sum = 0;
            $ytd_eval_req = $ytd_eval_done = $ytd_tradein_done = $ytd_sales_leads = $ytd_trade_out = 0;
            $mtd_eval_req = $mtd_eval_done = $mtd_tradein_done = $mtd_sales_leads = $mtd_trade_out = 0;
            $mtd_tradeins_sum = $mtd_refurb_sum = $mtd_sales_sum = 0;

            $trade_refu_days_sum = $eval_days_req = $eval_days_sum = $eval_trade_days_sum = $eval_trade_days_req = 0;

            //JLR
            $jlr_ytd_eval_req = $jlr_ytd_eval_done = $jlr_ytd_tradein_done = $jlr_ytd_sales_leads = $jlr_ytd_trade_out = 0;
            $jlr_mtd_eval_req = $jlr_mtd_eval_done = $jlr_mtd_tradein_done = $jlr_mtd_sales_leads = $jlr_mtd_trade_out = 0;
            $jlr_ytd_tradeins_sum = $jlr_ytd_refurb_sum = $jlr_ytd_sales_sum = $jlr_mtd_tradeins_sum = $jlr_mtd_refurb_sum = $jlr_mtd_sales_sum = 0;
            $jlr_trade_refu_days_sum = $jlr_eval_days_req = $jlr_eval_days_sum = $jlr_eval_trade_days_sum = $jlr_eval_trade_days_req = 0;
            $jlr_refurb_sum = $njlr_refurb_sum = 0;

            //Non JLR
            $njlr_ytd_eval_req = $njlr_ytd_eval_done = $njlr_ytd_tradein_done = $njlr_ytd_sales_leads = $njlr_ytd_trade_out = 0;
            $njlr_mtd_eval_req = $njlr_mtd_eval_done = $njlr_mtd_tradein_done = $njlr_mtd_sales_leads = $njlr_mtd_trade_out = 0;
            $njlr_ytd_tradeins_sum = $njlr_ytd_refurb_sum = $njlr_ytd_sales_sum = $njlr_mtd_tradeins_sum = $njlr_mtd_refurb_sum = $njlr_mtd_sales_sum = 0;
            $njlr_trade_refu_days_sum = $njlr_eval_days_req = $njlr_eval_days_sum = $njlr_eval_trade_days_sum = $njlr_eval_trade_days_req = 0;

            $get_sellleads_qry ="SELECT  id,
                                              make, 
                                              model,
                                              variant,
                                              mfg_year, 
                                              reg_date,
                                              date(purchased_date) as purchased_date,
                                              evaluation_done, 
                                              date(evaluation_date) as evaluation_date,
                                              state,
                                              city,
                                              pin_code, 
                                              branch,dealer,
                                              executive, 
                                              status,
                                              followup_date, 
                                              date(updated) as u_date,
                                              date(created) as c_date,
                                              price_selling
                                    FROM `sellleads` WHERE branch ='".$assign_branches[$a]."' ORDER BY id desc";
                         
            $get_sellleads = mysqli_query($connection, $get_sellleads_qry);
            while ($row = mysqli_fetch_assoc($get_sellleads)) {
                //TAT Evaluation required To Evaluation Done
                $c_date =
                    $row["created"] != "0000-00-00" ? $row["created"] : "";
                $evaluation_date = "";
                $date_diff = -1;
                if ($row["evaluation_date"] != "0000-00-00" && $row["evaluation_done"] == 'y') {
                    
                    $evaluation_date = $row["evaluation_date"];
                }
                if ($evaluation_date >= $c_date && $evaluation_date != "" && $c_date != "") {
                    $start_date = strtotime($c_date);
                    $end_date = strtotime($evaluation_date);
                    $date_diff = round(($end_date - $start_date) / 60 / 60 / 24);
                }
                if ($date_diff >= 0) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_eval_days_sum = $jlr_eval_days_sum + $date_diff;
                        $jlr_eval_days_req = $jlr_eval_days_req + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_eval_days_sum = $njlr_eval_days_sum + $date_diff;
                        $njlr_eval_days_req = $njlr_eval_days_req + 1;
                    }
                    $eval_days_sum = $eval_days_sum + $date_diff;
                    $eval_days_req = $eval_days_req + 1;
                }
                //Evaluation done to Trade in done
                $purchased_date =$row["purchased_date"] != "0000-00-00" ? $row["purchased_date"] : "";
                $date_diff = -1;

                if ($evaluation_date >= $purchased_date && $evaluation_date != "" && $purchased_date != "") {
                    $start_date = strtotime($pocession_date);
                    $end_date = strtotime($evaluation_date);
                    $date_diff = round(($end_date - $start_date) / 60 / 60 / 24);
                } elseif ($evaluation_date <= $purchased_date && $evaluation_date != "" && $purchased_date != "") {
                    $start_date = strtotime($evaluation_date);
                    $end_date = strtotime($purchased_date);
                    $date_diff = round(($end_date - $start_date) / 60 / 60 / 24);
                }
                //echo "date_diff=".$date_diff;
                //echo "<br>";
                if ($date_diff >= 0) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_eval_trade_days_sum =$jlr_eval_trade_days_sum + $date_diff;
                        $jlr_eval_trade_days_req = $jlr_eval_trade_days_req + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_eval_trade_days_sum =$jlr_eval_trade_days_sum + $date_diff;
                        $njlr_eval_trade_days_req =$jlr_eval_trade_days_req + 1;
                    }
                    $eval_trade_days_sum = $eval_trade_days_sum + $date_diff;
                    $eval_trade_days_req = $eval_trade_days_req + 1;
                }
                //YTD Evaluation Required
                if ($row["created"] != "0000-00-00" && $row["created"] >= $ytd_start_date && $row["created"] <= $yesterday) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_ytd_eval_req = $jlr_ytd_eval_req + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_ytd_eval_req = $njlr_ytd_eval_req + 1;
                    }
                    $ytd_eval_req = $ytd_eval_req + 1;
                }
                //YTD Evaluation Done
                if ($row["evaluation_done"] == "y" && $row["evaluation_date"] != "0000-00-00" && $row["evaluation_date"] >= $ytd_start_date &&
                    $row["evaluation_date"] <= $yesterday) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_ytd_eval_done = $jlr_ytd_eval_done + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_ytd_eval_done = $njlr_ytd_eval_done + 1;
                    }
                    $ytd_eval_done = $ytd_eval_done + 1;
                }
                //YTD Trade-In Done
                if ($row["status"] == 4 && $row["purchased_date"] != "0000-00-00" && $row["purchased_date"] >= $ytd_start_date &&
                    $row["purchased_date"] <= $yesterday) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_ytd_tradein_done = $jlr_ytd_tradein_done + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_ytd_tradein_done = $njlr_ytd_tradein_done + 1;
                    }
                    $ytd_tradein_done = $ytd_tradein_done + 1;
                }
                //YTD tradeins sum(Total sum)
                if (
                    $row["status"] == 4 &&
                    $row["purchased_date"] != "0000-00-00" &&
                    $row["purchased_date"] >= $ytd_start_date &&
                    $row["purchased_date"] <= $yesterday &&
                    $row["price_selling"] > 0
                ) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_ytd_tradeins_sum =
                            $jlr_ytd_tradeins_sum + $row["price_selling"];
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_ytd_tradeins_sum =
                            $njlr_ytd_tradeins_sum + $row["price_selling"];
                    }
                    $ytd_tradeins_sum =
                        $ytd_tradeins_sum + $row["price_selling"];
                }
                //MTD Evaluation Required
                if (
                    $row["created"] != "0000-00-00" &&
                    $row["created"] >= $mtd_start_date &&
                    $row["created"] <= $yesterday
                ) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_mtd_eval_req = $jlr_mtd_eval_req + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_mtd_eval_req = $njlr_mtd_eval_req + 1;
                    }
                    $mtd_eval_req = $mtd_eval_req + 1;
                }
                //MTD Evaluation Done
                if (
                    $row["evaluation_done"] == 'y' &&
                    $row["evaluation_date"] != "0000-00-00" &&
                    $row["evaluation_date"] >= $mtd_start_date &&
                    $row["evaluation_date"] <= $yesterday
                ) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_mtd_eval_done = $jlr_mtd_eval_done + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_mtd_eval_done = $njlr_mtd_eval_done + 1;
                    }
                    $mtd_eval_done = $mtd_eval_done + 1;
                }
                //MTD Tradein Done
                if (
                    $row["status"] == 4 &&
                    $row["purchased_date"] != "0000-00-00" &&
                    $row["purchased_date"] >= $mtd_start_date &&
                    $row["purchased_date"] <= $yesterday
                ) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_mtd_tradein_done = $jlr_mtd_tradein_done + 1;
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_mtd_tradein_done = $njlr_mtd_tradein_done + 1;
                    }

                    $mtd_tradein_done = $mtd_tradein_done + 1;
                }

                //MTD tradeins sum(Total sum)
                if (
                    $row["status"] == 4 &&
                    $row["purchased_date"] != "0000-00-00" &&
                    $row["purchased_date"] >= $mtd_start_date &&
                    $row["purchased_date"] <= $yesterday &&
                    $row["price_selling"] > 0
                ) {
                    if (in_array($row["make"], $make_ids)) {
                        $jlr_mtd_tradeins_sum =
                            $jlr_mtd_tradeins_sum + $row["price_selling"];
                    }
                    if (!in_array($row["make"], $make_ids)) {
                        $njlr_mtd_tradeins_sum =
                            $njlr_mtd_tradeins_sum + $row["price_selling"];
                    }
                    $mtd_tradeins_sum =
                        $mtd_tradeins_sum + $row["price_selling"];
                }

                //All Counts
                $all_ytd_eval_req = $all_ytd_eval_req + $ytd_eval_req;
                $all_ytd_eval_done = $all_ytd_eval_done + $ytd_eval_done;
                $all_ytd_tradein_done =
                    $all_ytd_tradein_done + $ytd_tradein_done;
                $all_ytd_tradeins_sum =
                    $all_ytd_tradeins_sum + $ytd_tradeins_sum;
                $all_mtd_eval_req = $all_mtd_eval_req + $mtd_eval_req;
                $all_mtd_eval_done = $all_mtd_eval_done + $mtd_eval_done;
                $all_mtd_tradein_done =
                    $all_mtd_tradein_done + $mtd_tradein_done;

                //JLR
                $jlr_all_ytd_eval_req =
                    $jlr_all_ytd_eval_req + $jlr_ytd_eval_req;
                $jlr_all_ytd_eval_done =
                    $jlr_all_ytd_eval_done + $jlr_ytd_eval_done;
                $jlr_all_ytd_tradein_done =
                    $jlr_all_ytd_tradein_done + $jlr_ytd_tradein_done;
                $jlr_all_ytd_tradeins_sum =
                    $jlr_all_ytd_tradeins_sum + $jlr_ytd_tradeins_sum;
                $jlr_all_mtd_eval_req =
                    $jlr_all_mtd_eval_req + $jlr_mtd_eval_req;
                $jlr_all_mtd_eval_done =
                    $jlr_all_mtd_eval_done + $jlr_mtd_eval_done;
                $jlr_all_mtd_tradein_done =
                    $jlr_all_mtd_tradein_done + $jlr_mtd_tradein_done;

                //Non JLR
                $njlr_all_ytd_eval_req =
                    $njlr_all_ytd_eval_req + $njlr_ytd_eval_req;
                $njlr_all_ytd_eval_done =
                    $njlr_all_ytd_eval_done + $njlr_ytd_eval_done;
                $njlr_all_ytd_tradein_done =
                    $njlr_all_ytd_tradein_done + $njlr_ytd_tradein_done;
                $njlr_all_ytd_tradeins_sum =
                    $njlr_all_ytd_tradeins_sum + $njlr_ytd_tradeins_sum;
                $njlr_all_mtd_eval_req =
                    $njlr_all_mtd_eval_req + $njlr_mtd_eval_req;
                $njlr_all_mtd_eval_done =
                    $njlr_all_mtd_eval_done + $njlr_mtd_eval_done;
                $njlr_all_mtd_tradein_done =
                    $njlr_all_mtd_tradein_done + $njlr_mtd_tradein_done;

                //Buyleads
                $delv_rc_days_req = $delv_rc_days_sum = 0;
                $jlr_delv_rc_days_sum = $jlr_delv_rc_days_req = 0;
                $njlr_delv_rc_days_sum = $njlr_delv_rc_days_req = 0;
                $get_buyleads_qry =
                    "SELECT id,
                                    date(booking_date) as booking_date,
                                    date(delivery_date) as delivery_date,
                                    date(sold_date) as sold_date,
                                    token_amount,paid_amount,
                                    branch,user,dealer,
                                    status,followup_date,
                                    date(updated) as u_date,date(created) as c_date 
                                    FROM `buyleads` WHERE branch='" .
                    $assign_branches[$a] .
                    "' ORDER BY id desc";
            
                $get_buyleads = mysqli_query($connection,$get_buyleads_qry);
                while ($fet_row = mysqli_fetch_assoc($get_buyleads)) {
                    //car delivery to RC Tranfer
                    $car_delivery_date =
                        $fet_row["delivery_date"] != "0000-00-00"
                            ? $fet_row["delivery_date"]
                            : "";
                }
                $trade_out_date =
                    $fet_row["sold_date"] != "0000-00-00"
                        ? $fet_row["sold_date"]
                        : "";
                $date_diff = -1;
                if (
                    $car_delivery_date >= $trade_out_date &&
                    $car_delivery_date != "" &&
                    $trade_out_date != ""
                ) {
                    $start_date = strtotime($trade_out_date);
                    $end_date = strtotime($car_delivery_date);
                    $date_diff = round(
                        ($end_date - $start_date) / 60 / 60 / 24
                    );
                } elseif (
                    $car_delivery_date <= $trade_out_date &&
                    $car_delivery_date != "" &&
                    $trade_out_date != ""
                ) {
                    $start_date = strtotime($car_delivery_date);
                    $end_date = strtotime($trade_out_date);
                    $date_diff = round(
                        ($end_date - $start_date) / 60 / 60 / 24
                    );
                }
                if ($date_diff >= 0) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_delv_rc_days_sum =
                            $jlr_delv_rc_days_sum + $date_diff;
                        $jlr_delv_rc_days_req = $njlr_delv_rc_days_req + 1;
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_delv_rc_days_sum =
                            $njlr_delv_rc_days_sum + $date_diff;
                        $njlr_delv_rc_days_req = $njlr_delv_rc_days_req + 1;
                    }
                    $delv_rc_days_sum = $delv_rc_days_sum + $date_diff;
                    $delv_rc_days_req = $delv_rc_days_req + 1;
                }
                //YTD sales leads
                if (
                    $fet_row["c_date"] != "0000-00-00" &&
                    $fet_row["c_date"] >= $ytd_start_date &&
                    $fet_row["c_date"] <= $yesterday
                ) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_ytd_sales_leads = $jlr_ytd_sales_leads + 1;
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_ytd_sales_leads = $njlr_ytd_sales_leads + 1;
                    }
                    $ytd_sales_leads = $ytd_sales_leads + 1;
                }
                //YTD Trade out leads
                if (
                    $fet_row["status"] >= 10000 &&
                    $fet_row["delivery_date"] != "0000-00-00" &&
                    $fet_row["delivery_date"] >= $ytd_start_date &&
                    $fet_row["delivery_date"] <= $yesterday
                ) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_ytd_trade_out = $jlr_ytd_trade_out + 1;
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_ytd_trade_out = $njlr_ytd_trade_out + 1;
                    }

                    $ytd_trade_out = $ytd_trade_out + 1;
                }

                //YTD sales sum
                if (
                    $fet_row["status"] >= 10000 &&
                    $fet_row["status"] < 99999 &&
                    $fet_row["delivery_date"] != "0000-00-00" &&
                    $fet_row["delivery_date"] >= $ytd_start_date &&
                    $fet_row["delivery_date"] <= $yesterday &&
                    $fet_row["quoted_sp"] > 0
                ) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_ytd_sales_sum =
                            $jlr_ytd_sales_sum + $fet_row["quoted_sp"];
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_ytd_sales_sum =
                            $njlr_ytd_sales_sum + $fet_row["quoted_sp"];
                    }
                    $ytd_sales_sum = $ytd_sales_sum + $fet_row["quoted_sp"];
                }
                //MTD sales lead
                if (
                    $fet_row["c_date"] != "0000-00-00" &&
                    $fet_row["c_date"] >= $mtd_start_date &&
                    $fet_rowq["c_date"] <= $yesterday
                ) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_mtd_sales_leads = $jlr_mtd_sales_leads + 1;
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_mtd_sales_leads = $njlr_mtd_sales_leads + 1;
                    }
                    $mtd_sales_leads = $mtd_sales_leads + 1;
                }
                //MTD tradeOut
                if (
                    $fet_row["status"] >= 10000 &&
                    $fet_row["status"] < 99999 &&
                    $fet_row["delivery_date"] != "0000-00-00" &&
                    $fet_row["delivery_date"] >= $mtd_start_date &&
                    $fet_row["delivery_date"] <= $yesterday
                ) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_mtd_trade_out = $jlr_mtd_trade_out + 1;
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_mtd_trade_out = $njlr_mtd_trade_out + 1;
                    }
                    $mtd_trade_out = $mtd_trade_out + 1;
                }
                //MTD Sales Sum
                if (
                    $fet_row["status"] >= 10000 &&
                    $fet_row["status"] < 99999 &&
                    $fet_row["car_delivery_date"] != "0000-00-00" &&
                    $fet_row["delivery_date"] >= $mtd_start_date &&
                    $fet_row["delivery_date"] <= $yesterday &&
                    $fet_row["quoted_sp"] > 0
                ) {
                    if (in_array($fet_row["make"], $make_ids)) {
                        $jlr_mtd_sales_sum =
                            $jlr_mtd_sales_sum + $fet_row["quoted_sp"];
                    }
                    if (!in_array($fet_row["make"], $make_ids)) {
                        $njlr_mtd_sales_sum =
                            $njlr_mtd_sales_sum + $fet_row["quoted_sp"];
                    }

                    $mtd_sales_sum = $mtd_sales_sum + $fet_row["quoted_sp"];
                }
            } //Buyleads foreach close
            $all_ytd_sales_leads = $all_ytd_sales_leads + $ytd_sales_leads;
            $all_ytd_trade_out = $all_ytd_trade_out + $ytd_trade_out;
            $all_ytd_sales_sum = $all_ytd_sales_sum + $ytd_sales_sum;

            $all_mtd_sales_leads = $all_mtd_sales_leads + $mtd_sales_leads;
            $all_mtd_trade_out = $all_mtd_trade_out + $mtd_trade_out;
            $all_mtd_tradeins_sum = $all_mtd_tradeins_sum + $mtd_tradeins_sum;
            $all_mtd_sales_sum = $all_mtd_sales_sum + $mtd_sales_sum;

            //JLR
            $jlr_all_ytd_sales_leads =
                $jlr_all_ytd_sales_leads + $jlr_ytd_sales_leads;
            $jlr_all_ytd_trade_out =
                $jlr_all_ytd_trade_out + $jlr_ytd_trade_out;
            $jlr_all_ytd_sales_sum =
                $jlr_all_ytd_sales_sum + $jlr_ytd_sales_sum;
            $jlr_all_mtd_sales_leads =
                $jlr_all_mtd_sales_leads + $jlr_mtd_sales_leads;
            $jlr_all_mtd_trade_out =
                $jlr_all_mtd_trade_out + $jlr_mtd_trade_out;
            $jlr_all_mtd_tradeins_sum =
                $jlr_all_mtd_tradeins_sum + $jlr_mtd_tradeins_sum;
            $jlr_all_mtd_sales_sum =
                $jlr_all_mtd_sales_sum + $jlr_mtd_sales_sum;

            //NJLR
            $njlr_all_ytd_sales_leads =
                $njlr_all_ytd_sales_leads + $njlr_ytd_sales_leads;
            $njlr_all_ytd_trade_out =
                $njlr_all_ytd_trade_out + $njlr_ytd_trade_out;
            $njlr_all_ytd_sales_sum =
                $njlr_all_ytd_sales_sum + $njlr_ytd_sales_sum;
            $njlr_all_mtd_sales_leads =
                $njlr_all_mtd_sales_leads + $njlr_mtd_sales_leads;
            $njlr_all_mtd_trade_out =
                $njlr_all_mtd_trade_out + $njlr_mtd_trade_out;
            $njlr_all_mtd_tradeins_sum =
                $njlr_all_mtd_tradeins_sum + $njlr_mtd_tradeins_sum;
            $njlr_all_mtd_sales_sum =
                $njlr_all_mtd_sales_sum + $njlr_mtd_sales_sum;

            $ytd_counts["total"]["ytd_eval_req"] =
                $ytd_eval_req > 0 ? $ytd_eval_req : 0;
            $ytd_counts["total"]["ytd_eval_done"] =
                $ytd_eval_done > 0 ? $ytd_eval_done : 0;
            $ytd_counts["total"]["ytd_tradein_done"] =
                $ytd_tradein_done > 0 ? $ytd_tradein_done : 0;
            $ytd_counts["total"]["ytd_tradeins_sum"] =
                $ytd_tradeins_sum > 0 ? $ytd_tradeins_sum : 0;

            //JLR
            $ytd_counts["jlr"]["ytd_eval_req"] =
                $jlr_ytd_eval_req > 0 ? $jlr_ytd_eval_req : 0;
            $ytd_counts["jlr"]["ytd_eval_done"] =
                $jlr_ytd_eval_done > 0 ? $jlr_ytd_eval_done : 0;
            $ytd_counts["jlr"]["ytd_tradein_done"] =
                $jlr_ytd_tradein_done > 0 ? $jlr_ytd_tradein_done : 0;
            $ytd_counts["jlr"]["ytd_tradeins_sum"] =
                $jlr_ytd_tradeins_sum > 0 ? $jlr_ytd_tradeins_sum : 0;

            //NJLR
            $ytd_counts["non_jlr"]["ytd_eval_req"] =
                $njlr_ytd_eval_req > 0 ? $njlr_ytd_eval_req : 0;
            $ytd_counts["non_jlr"]["ytd_eval_done"] =
                $njlr_ytd_eval_done > 0 ? $njlr_ytd_eval_done : 0;
            $ytd_counts["non_jlr"]["ytd_tradein_done"] =
                $njlr_ytd_tradein_done > 0 ? $njlr_ytd_tradein_done : 0;
            $ytd_counts["non_jlr"]["ytd_tradeins_sum"] =
                $njlr_ytd_tradeins_sum > 0 ? $njlr_ytd_tradeins_sum : 0;

            $mtd_counts["total"]["mtd_eval_req"] =
                $mtd_eval_req > 0 ? $mtd_eval_req : 0;
            $mtd_counts["total"]["mtd_eval_done"] =
                $mtd_eval_done > 0 ? $mtd_eval_done : 0;
            $mtd_counts["total"]["mtd_tradein_done"] =
                $mtd_tradein_done > 0 ? $mtd_tradein_done : 0;
            $mtd_counts["total"]["mtd_sales_sum"] =
                $mtd_sales_sum > 0 ? $mtd_sales_sum : 0;

            //JLR
            $mtd_counts["jlr"]["mtd_eval_req"] =
                $jlr_mtd_eval_req > 0 ? $jlr_mtd_eval_req : 0;
            $mtd_counts["jlr"]["mtd_eval_done"] =
                $jlr_mtd_eval_done > 0 ? $jlr_mtd_eval_done : 0;
            $mtd_counts["jlr"]["mtd_tradein_done"] =
                $jlr_mtd_tradein_done > 0 ? $jlr_mtd_tradein_done : 0;
            $mtd_counts["jlr"]["mtd_sales_sum"] =
                $jlr_mtd_sales_sum > 0 ? $jlr_mtd_sales_sum : 0;

            //NJLR
            $mtd_counts["non_jlr"]["mtd_eval_req"] =
                $njlr_mtd_eval_req > 0 ? $njlr_mtd_eval_req : 0;
            $mtd_counts["non_jlr"]["mtd_eval_done"] =
                $njlr_mtd_eval_done > 0 ? $njlr_mtd_eval_done : 0;
            $mtd_counts["non_jlr"]["mtd_tradein_done"] =
                $njlr_mtd_tradein_done > 0 ? $njlr_mtd_tradein_done : 0;
            $mtd_counts["non_jlr"]["mtd_sales_sum"] =
                $njlr_mtd_sales_sum > 0 ? $njlr_mtd_sales_sum : 0;

            $ytd_costs["total"]["ytd_sales_leads"] =
                $ytd_sales_leads > 0 ? $ytd_sales_leads : 0;

            $ytd_costs["total"]["ytd_trade_out"] =
                $ytd_trade_out > 0 ? $ytd_trade_out : 0;
            $ytd_costs["total"]["ytd_sales_sum"] =
                $ytd_sales_sum > 0 ? $ytd_sales_sum : 0;

            //JLR
            $ytd_costs["jlr"]["ytd_sales_leads"] =
                $jlr_ytd_sales_leads > 0 ? $jlr_ytd_sales_leads : 0;
            $ytd_costs["jlr"]["ytd_trade_out"] =
                $jlr_ytd_trade_out > 0 ? $jlr_ytd_trade_out : 0;
            $ytd_costs["jlr"]["ytd_refurb_sum"] =
                $jlr_refurb_sum > 0 ? $jlr_refurb_sum : 0;
            $ytd_costs["jlr"]["ytd_sales_sum"] =
                $jlr_ytd_sales_sum > 0 ? $jlr_ytd_sales_sum : 0;

            //NJLR
            $ytd_costs["non_jlr"]["ytd_sales_leads"] =
                $njlr_ytd_sales_leads > 0 ? $njlr_ytd_sales_leads : 0;
            $ytd_costs["non_jlr"]["ytd_trade_out"] =
                $njlr_ytd_trade_out > 0 ? $njlr_ytd_trade_out : 0;
            $ytd_costs["non_jlr"]["ytd_refurb_sum"] =
                $njlr_refurb_sum > 0 ? $njlr_refurb_sum : 0;
            $ytd_costs["non_jlr"]["ytd_sales_sum"] =
                $njlr_ytd_sales_sum > 0 ? $njlr_ytd_sales_sum : 0;

            $mtd_costs["total"]["mtd_sales_leads"] =
                $mtd_sales_leads > 0 ? $mtd_sales_leads : 0;
            $mtd_costs["total"]["mtd_trade_out"] =
                $mtd_trade_out > 0 ? $mtd_trade_out : 0;
            $mtd_costs["total"]["mtd_tradeins_sum"] =
                $mtd_tradeins_sum > 0 ? $mtd_tradeins_sum : 0;

            //JLR
            $mtd_costs["jlr"]["mtd_sales_leads"] =
                $jlr_mtd_sales_leads > 0 ? $jlr_mtd_sales_leads : 0;
            $mtd_costs["jlr"]["mtd_trade_out"] =
                $jlr_mtd_trade_out > 0 ? $jlr_mtd_trade_out : 0;
            $mtd_costs["jlr"]["mtd_tradeins_sum"] =
                $jlr_mtd_tradeins_sum > 0 ? $jlr_mtd_tradeins_sum : 0;

            //NJLR
            $mtd_costs["non_jlr"]["mtd_sales_leads"] =
                $njlr_mtd_sales_leads > 0 ? $njlr_mtd_sales_leads : 0;
            $mtd_costs["non_jlr"]["mtd_trade_out"] =
                $njlr_mtd_trade_out > 0 ? $njlr_mtd_trade_out : 0;
            $mtd_costs["non_jlr"]["mtd_tradeins_sum"] =
                $njlr_mtd_tradeins_sum > 0 ? $njlr_mtd_tradeins_sum : 0;

            //Inventory
            $trade_refu_days_sum = $trade_refu_days_req = 0;
            $trade_refu_days_sum = $trade_refu_days_req = $trade_ro_days_req = $trade_ro_days_sum = $ro_booking_days_sum = $ro_booking_days_req = 0;
            $trade_ro_days_sum = $trade_ro_days_req = 0;
            $ro_booking_days_sum = $ro_booking_days_req = 0;
            $ro_booking_days_sum = $ro_booking_days_req = 0;
            $booking_to_dvry_req = $booking_to_dvry_sum = 0;

            $jlr_trade_refu_days_sum = $jlr_trade_refu_days_req = 0;
            $jlr_trade_refu_days_sum = $jlr_trade_refu_days_req = $jlr_trade_ro_days_req = $jlr_trade_ro_days_sum = $jlr_ro_booking_days_sum = $jlr_ro_booking_days_req = 0;
            $jlr_trade_ro_days_sum = $jlr_trade_ro_days_req = 0;
            $jlr_ro_booking_days_sum = $jlr_ro_booking_days_req = 0;
            $jlr_ro_booking_days_sum = $jlr_ro_booking_days_req = 0;
            $jlr_booking_to_dvry_req = $jlr_booking_to_dvry_sum = 0;

            $njlr_trade_refu_days_sum = $njlr_trade_refu_days_req = 0;
            $njlr_trade_refu_days_sum = $njlr_trade_refu_days_req = $njlr_trade_ro_days_req = $njlr_trade_ro_days_sum = $njlr_ro_booking_days_sum = $njlr_ro_booking_days_req = 0;
            $njlr_trade_ro_days_sum = $njlr_trade_ro_days_req = 0;
            $njlr_ro_booking_days_sum = $njlr_ro_booking_days_req = 0;
            $njlr_ro_booking_days_sum = $njlr_ro_booking_days_req = 0;
            $njlr_booking_to_dvry_req = $njlr_booking_to_dvry_sum = 0;

            $get_inv_qry =
                "SELECT id,
                    make,
                    model,
                    variant,
                    mfg_year,
                    reg_date,date(certified_date) as certification_date,
                    date(date_of_sale) as posting_date,
                    certification_status as certification_done,
                    branch,executive,dealer,
                    status,
                    date(updated_on) as u_date,date(added_on) as c_date,chassis
                                        FROM `inventory` WHERE branch='" .
                $assign_branches[$a] .
                "' ORDER BY id desc";
            //echo $get_inv_qry;exit;
            $get_invleads = mysqli_query($connection, $get_inv_qry);
            while ($fetrow = mysqli_fetch_assoc($get_invleads)) {
                //Tat Evlaution Required TO Trade In
                $trade_in_date =
                    $fetrow["trade_in_date"] != "0000-00-00"
                        ? $fetrow["trade_in_date"]
                        : "";
                $refurb_date =
                    $fetrow["refurb_date"] != "0000-00-00"
                        ? $fetrow["refurb_date"]
                        : "";

                $date_diff = -1;
                if (
                    $trade_in_date >= $refurb_date &&
                    $trade_in_date != "" &&
                    $refurb_date != ""
                ) {
                    $start_date = strtotime($refurb_date);
                    $end_date = strtotime($trade_in_date);
                    $date_diff = round(
                        ($end_date - $start_date) / 60 / 60 / 24
                    );
                } elseif (
                    $trade_in_date <= $refurb_date &&
                    $trade_in_date != "" &&
                    $refurb_date != ""
                ) {
                    $start_date = strtotime($trade_in_date);
                    $end_date = strtotime($refurb_date);
                    $date_diff = round(
                        ($end_date - $start_date) / 60 / 60 / 24
                    );
                }
                if ($date_diff >= 0) {
                    if (in_array($fetrow["make"], $make_ids)) {
                        $jlr_trade_refu_days_sum =
                            $jlr_trade_refu_days_sum + $date_diff;
                        $jlr_trade_refu_days_req = $jlr_trade_refu_days_req + 1;
                    }
                    if (!in_array($fetrow["make"], $make_ids)) {
                        $njlr_trade_refu_days_sum =
                            $njlr_trade_refu_days_sum + $date_diff;
                        $njlr_trade_refu_days_req =
                            $njlr_trade_refu_days_req + 1;
                    }
                    $trade_refu_days_sum = $trade_refu_days_sum + $date_diff;
                    $trade_refu_days_req = $trade_refu_days_req + 1;
                }
                //Trade-in RO Received
                if (
                    in_array($fetrow["make"], $make_ids) &&
                    $fetrow["chassis_num"] != ""
                ) {
                    $ro_dt_qry = $connection->get_record([
                        "table" => "vehicle_service_history",
                        "fields" => ["ro_opendate,ro_closedate"],
                        "where" => [
                            "vin" => $fetrow["chassis_num"],
                            "service_order_type" => 515,
                        ],
                        "orderby" => "id desc",
                        "limit" => 1,
                    ]);
                    $ro_opendate =
                        isset($ro_dt_qry["ro_opendate"]) &&
                        $ro_dt_qry["ro_opendate"] != "0000-00-00"
                            ? $ro_dt_qry["ro_opendate"]
                            : "";
                    $trade_in_date =
                        $fetrow["trade_in_date"] != "0000-00-00"
                            ? $fetrow["trade_in_date"]
                            : "";

                    $date_diff = -1;
                    if (
                        $trade_in_date >= $ro_opendate &&
                        $trade_in_date != "" &&
                        $ro_opendate != ""
                    ) {
                        $start_date = strtotime($ro_opendate);
                        $end_date = strtotime($trade_in_date);
                        $date_diff = round(
                            ($end_date - $start_date) / 60 / 60 / 24
                        );
                    } elseif (
                        $trade_in_date <= $ro_opendate &&
                        $trade_in_date != "" &&
                        $ro_opendate != ""
                    ) {
                        $start_date = strtotime($trade_in_date);
                        $end_date = strtotime($ro_opendate);
                        $date_diff = round(
                            ($end_date - $start_date) / 60 / 60 / 24
                        );
                    }
                    if ($date_diff >= 0) {
                        if (in_array($fetrow["make"], $make_ids)) {
                            $jlr_trade_ro_days_sum =
                                $jlr_trade_ro_days_sum + $date_diff;
                            $jlr_trade_ro_days_req = $jlr_trade_ro_days_req + 1;
                        }
                        $trade_ro_days_sum = $trade_ro_days_sum + $date_diff;
                        $trade_ro_days_req = $trade_ro_days_req + 1;
                    }
                    //Tat RO-Booking
                    $ro_closedate =
                        isset($ro_dt_qry["ro_closedate"]) &&
                        $ro_dt_qry["ro_closedate"] != "0000-00-00"
                            ? $ro_dt_qry["ro_closedate"]
                            : "";
                    $booking_date =
                        $fetrow["booking_date"] != "0000-00-00"
                            ? $fetrow["booking_date"]
                            : "";

                    $date_diff = -1;
                    if (
                        $booking_date >= $ro_closedate &&
                        $booking_date != "" &&
                        $ro_closedate != ""
                    ) {
                        $start_date = strtotime($ro_closedate);
                        $end_date = strtotime($booking_date);
                        $date_diff = round(
                            ($end_date - $start_date) / 60 / 60 / 24
                        );
                    } elseif (
                        $booking_date <= $ro_closedate &&
                        $booking_date != "" &&
                        $ro_closedate != ""
                    ) {
                        $start_date = strtotime($booking_date);
                        $end_date = strtotime($ro_closedate);
                        $date_diff = round(
                            ($end_date - $start_date) / 60 / 60 / 24
                        );
                    }
                    if ($date_diff >= 0) {
                        if (in_array($fetrow["make"], $make_ids)) {
                            $jlr_ro_booking_days_sum =
                                $jlr_ro_booking_days_sum + $date_diff;
                            $jlr_ro_booking_days_req =
                                $jlr_ro_booking_days_req + 1;
                        }
                        $ro_booking_days_sum =
                            $ro_booking_days_sum + $date_diff;
                        $ro_booking_days_req = $ro_booking_days_req + 1;
                    }
                }
                //Tat Booking-Delivere
                $car_delivery_date =
                    $fetrow["delivery_date"] != "0000-00-00"
                        ? $fetrow["delivery_date"]
                        : "";
                $booking_date =
                    $fetrow["booking_date"] != "0000-00-00"
                        ? $fetrow["booking_date"]
                        : "";

                $date_diff = -1;
                if (
                    $booking_date >= $car_delivery_date &&
                    $booking_date != "" &&
                    $car_delivery_date != ""
                ) {
                    $start_date = strtotime($car_delivery_date);
                    $end_date = strtotime($booking_date);
                    $date_diff = round(
                        ($end_date - $start_date) / 60 / 60 / 24
                    );
                } elseif (
                    $booking_date <= $car_delivery_date &&
                    $booking_date != "" &&
                    $car_delivery_date != ""
                ) {
                    $start_date = strtotime($booking_date);
                    $end_date = strtotime($car_delivery_date);
                    $date_diff = round(
                        ($end_date - $start_date) / 60 / 60 / 24
                    );
                }
                if ($date_diff >= 0) {
                    if (in_array($fetrow["make"], $make_ids)) {
                        $jlr_booking_to_dvry_sum =
                            $jlr_booking_to_dvry_sum + $date_diff;
                        $jlr_booking_to_dvry_req = $jlr_booking_to_dvry_req + 1;
                    }
                    $booking_to_dvry_sum = $booking_to_dvry_sum + $date_diff;
                    $booking_to_dvry_req = $booking_to_dvry_req + 1;
                }
                //YTD Refurb sum
                if (
                    $fetrow["final_refurb_cost"] > 0 &&
                    $fetrow["c_date"] != "0000-00-00" &&
                    $fetrow["c_date"] >= $ytd_start_date &&
                    $fetrow["c_date"] <= $yesterday &&
                    in_array($fetrow["make"], $make_ids)
                ) {
                    $jlr_ytd_refurb_sum =
                        $jlr_ytd_refurb_sum + $fetrow["final_refurb_cost"];
                    $ytd_refurb_sum =
                        $ytd_refurb_sum + $fetrow["final_refurb_cost"];
                }
                //MTD Refurb sum
                if (
                    $fetrow["final_refurb_cost"] > 0 &&
                    $fetrow["c_date"] != "0000-00-00" &&
                    $fetrow["c_date"] >= $mtd_start_date &&
                    $fetrow["c_date"] <= $yesterday &&
                    in_array($fetrow["make"], $make_ids)
                ) {
                    $jlr_mtd_refurb_sum =
                        $jlr_mtd_refurb_sum + $fetrow["final_refurb_cost"];
                    $mtd_refurb_sum =
                        $mtd_refurb_sum + $fetrow["final_refurb_cost"];
                }
            } //inventory close

            $tat_trade_refu = $tat_evalreq_evaldone = $jlr_tat_evalreq_evaldone = $njlr_tat_evalreq_evaldone = $tat_eval_trade_done = $jlr_tat_eval_trade_done = $njlr_tat_eval_trade_done = $tat_trad_in_ro = $jlr_tat_trad_in_ro = $njlr_tat_trad_in_ro = $tat_ro_booking_days = $jlr_tat_ro_booking_days = $njlr_tat_ro_booking_days = $tat_booking_to_dvry = $jlr_tat_booking_to_dvry = $njlr_tat_booking_to_dvry = $tat_delivery_to_rc = $jlr_tat_delivery_to_rc = $njlr_tat_delivery_to_rc = 0;
            if ($trade_refu_days_req > 0) {
                $tat_trade_refu = round(
                    $trade_refu_days_sum / $trade_refu_days_req,
                    1
                );
                //$all_tat_trade_refu=$all_tat_trade_refu+$tat_trade_refu;
                $all_tat_trade_refu_days_sum =
                    $all_tat_trade_refu_days_sum + $trade_refu_days_sum;
                $all_tat_trade_refu_days_req =
                    $all_tat_trade_refu_days_req + $trade_refu_days_req;
                $all_tat_trade_refu = round(
                    $all_tat_trade_refu_days_sum / $all_tat_trade_refu_days_req,
                    1
                );
            }

            if ($eval_days_req > 0) {
                $tat_evalreq_evaldone = round(
                    $eval_days_sum / $eval_days_req,
                    1
                );
                //$all_tat_evalreq_evaldone=$all_tat_evalreq_evaldone+$tat_evalreq_evaldone;
                $all_tat_evalreq_evaldone_days_sum =
                    $all_tat_evalreq_evaldone_days_sum + $eval_days_sum;
                $all_tat_evalreq_evaldone_days_req =
                    $all_tat_evalreq_evaldone_days_req + $eval_days_req;
                $all_tat_evalreq_evaldone = round(
                    $all_tat_evalreq_evaldone_days_sum /
                        $all_tat_evalreq_evaldone_days_req,
                    1
                );
            }
            if ($jlr_eval_days_req > 0) {
                $jlr_tat_evalreq_evaldone = round(
                    $jlr_eval_days_sum / $jlr_eval_days_req,
                    1
                );
                //$jlr_all_tat_evalreq_evaldone=$jlr_all_tat_evalreq_evaldone+$jlr_tat_evalreq_evaldone;
                $jlr_all_tat_evalreq_evaldone_days_sum =
                    $jlr_all_tat_evalreq_evaldone_days_sum + $jlr_eval_days_sum;
                $jlr_all_tat_evalreq_evaldone_days_req =
                    $jlr_all_tat_evalreq_evaldone_days_req + $jlr_eval_days_req;
                $jlr_all_tat_evalreq_evaldone = round(
                    $jlr_all_tat_evalreq_evaldone_days_sum /
                        $jlr_all_tat_evalreq_evaldone_days_req,
                    1
                );
            }
            if ($njlr_eval_days_req > 0) {
                $njlr_tat_evalreq_evaldone = round(
                    $njlr_eval_days_sum / $njlr_eval_days_req,
                    1
                );
                //$njlr_all_tat_evalreq_evaldone=$njlr_all_tat_evalreq_evaldone+$njlr_tat_evalreq_evaldone;
                $njlr_all_tat_evalreq_evaldone_days_sum =
                    $njlr_all_tat_evalreq_evaldone_days_sum +
                    $njlr_eval_days_sum;
                $njlr_all_tat_evalreq_evaldone_days_req =
                    $njlr_all_tat_evalreq_evaldone_days_req +
                    $njlr_eval_days_req;
                $njlr_all_tat_evalreq_evaldone = round(
                    $njlr_all_tat_evalreq_evaldone_days_sum /
                        $njlr_all_tat_evalreq_evaldone_days_req,
                    1
                );
            }

            if ($eval_trade_days_req > 0) {
                $tat_eval_trade_done = round(
                    $eval_trade_days_sum / $eval_trade_days_req,
                    1
                );
                $all_tat_eval_trade_days_sum =
                    $all_tat_eval_trade_days_sum + $eval_trade_days_sum;
                $all_tat_eval_trade_days_req =
                    $all_tat_eval_trade_days_req + $eval_trade_days_req;
                $all_tat_eval_trade_done = round(
                    $all_tat_eval_trade_days_sum / $all_tat_eval_trade_days_req,
                    1
                );
            }
            if ($jlr_eval_trade_days_req > 0) {
                $jlr_tat_eval_trade_done = round(
                    $jlr_eval_trade_days_sum / $jlr_eval_trade_days_req,
                    1
                );
                //$jlr_all_tat_eval_trade_done=$jlr_all_tat_eval_trade_done+$jlr_tat_eval_trade_done;
                $jlr_all_tat_eval_trade_days_sum =
                    $jlr_all_tat_eval_trade_days_sum + $jlr_eval_trade_days_sum;
                $jlr_all_tat_eval_trade_days_req =
                    $jlr_all_tat_eval_trade_days_req + $jlr_eval_trade_days_req;
                $jlr_all_tat_eval_trade_done = round(
                    $jlr_all_tat_eval_trade_days_sum /
                        $jlr_all_tat_eval_trade_days_req,
                    1
                );
            }
            if ($njlr_eval_trade_days_req > 0) {
                $njlr_tat_eval_trade_done = round(
                    $njlr_eval_trade_days_sum / $njlr_eval_trade_days_req,
                    1
                );
                //$njlr_all_tat_eval_trade_done=$njlr_all_tat_eval_trade_done+$njlr_tat_eval_trade_done;
                $njlr_all_tat_eval_trade_days_sum =
                    $njlr_all_tat_eval_trade_days_sum +
                    $njlr_eval_trade_days_sum;
                $njlr_all_tat_eval_trade_days_req =
                    $njlr_all_tat_eval_trade_days_req +
                    $njlr_eval_trade_days_req;
                $njlr_all_tat_eval_trade_done = round(
                    $njlr_all_tat_eval_trade_days_sum /
                        $njlr_all_tat_eval_trade_days_req,
                    1
                );
            }

            if ($trade_ro_days_req > 0) {
                $tat_trad_in_ro = round(
                    $trade_ro_days_sum / $trade_ro_days_req,
                    1
                );
                //$all_tat_trad_in_ro=$all_tat_trad_in_ro+$tat_trad_in_ro;
                $all_tat_trad_in_ro_days_sum =
                    $all_tat_trad_in_ro_days_sum + $trade_ro_days_sum;
                $all_tat_trad_in_ro_days_req =
                    $all_tat_trad_in_ro_days_req + $trade_ro_days_req;
                $all_tat_trad_in_ro = round(
                    $all_tat_trad_in_ro_days_sum / $all_tat_trad_in_ro_days_req,
                    1
                );
            }
            if ($jlr_trade_ro_days_req > 0) {
                $jlr_tat_trad_in_ro = round(
                    $jlr_trade_ro_days_sum / $jlr_trade_ro_days_req,
                    1
                );
                //$jlr_all_tat_trad_in_ro=$jlr_all_tat_trad_in_ro+$jlr_tat_trad_in_ro;
                $jlr_all_tat_trad_in_ro_days_sum =
                    $jlr_all_tat_trad_in_ro_days_sum + $jlr_trade_ro_days_sum;
                $jlr_all_tat_trad_in_ro_days_req =
                    $jlr_all_tat_trad_in_ro_days_req + $jlr_trade_ro_days_req;
                $jlr_all_tat_trad_in_ro = round(
                    $jlr_all_tat_trad_in_ro_days_sum /
                        $jlr_all_tat_trad_in_ro_days_req,
                    1
                );
            }
            if ($njlr_trade_ro_days_req > 0) {
                $njlr_tat_trad_in_ro = round(
                    $njlr_trade_ro_days_sum / $njlr_trade_ro_days_req,
                    1
                );
                //$njlr_all_tat_trad_in_ro=$njlr_all_tat_trad_in_ro+$njlr_tat_trad_in_ro;
                $njlr_all_tat_trad_in_ro_days_sum =
                    $njlr_all_tat_trad_in_ro_days_sum + $njlr_trade_ro_days_sum;
                $njlr_all_tat_trad_in_ro_days_req =
                    $njlr_all_tat_trad_in_ro_days_req + $njlr_trade_ro_days_req;
                $njlr_all_tat_trad_in_ro = round(
                    $njlr_all_tat_trad_in_ro_days_sum /
                        $njlr_all_tat_trad_in_ro_days_req,
                    1
                );
            }

            if ($ro_booking_days_req > 0) {
                $tat_ro_booking_days = round(
                    $ro_booking_days_sum / $ro_booking_days_req,
                    1
                );
                //$all_tat_ro_booking_days=$all_tat_ro_booking_days+$tat_ro_booking_days;
                $all_tat_ro_booking_days_sum =
                    $all_tat_ro_booking_days_sum + $ro_booking_days_sum;
                $all_tat_ro_booking_days_req =
                    $all_tat_ro_booking_days_req + $ro_booking_days_req;
                $all_tat_ro_booking_days = round(
                    $all_tat_ro_booking_days_sum / $all_tat_ro_booking_days_req,
                    1
                );
            }
            if ($jlr_ro_booking_days_req > 0) {
                $jlr_tat_ro_booking_days = round(
                    $jlr_ro_booking_days_sum / $jlr_ro_booking_days_req,
                    1
                );
                //$jlr_all_tat_ro_booking_days=$jlr_all_tat_ro_booking_days+$jlr_tat_ro_booking_days;
                $jlr_all_tat_ro_booking_days_sum =
                    $jlr_all_tat_ro_booking_days_sum + $jlr_ro_booking_days_sum;
                $jlr_all_tat_ro_booking_days_req =
                    $jlr_all_tat_ro_booking_days_req + $jlr_ro_booking_days_req;
                $jlr_all_tat_ro_booking_days = round(
                    $jlr_all_tat_ro_booking_days_sum /
                        $jlr_all_tat_ro_booking_days_req,
                    1
                );
            }
            if ($njlr_ro_booking_days_req > 0) {
                $njlr_tat_ro_booking_days = round(
                    $njlr_ro_booking_days_sum / $njlr_ro_booking_days_req,
                    1
                );
                //$njlr_all_tat_ro_booking_days=$njlr_all_tat_ro_booking_days+$njlr_tat_ro_booking_days;
                $njlr_all_tat_ro_booking_days_sum =
                    $njlr_all_tat_ro_booking_days_sum +
                    $njlr_ro_booking_days_sum;
                $njlr_all_tat_ro_booking_days_req =
                    $njlr_all_tat_ro_booking_days_req +
                    $njlr_ro_booking_days_req;
                $njlr_all_tat_ro_booking_days = round(
                    $njlr_all_tat_ro_booking_days_sum /
                        $njlr_all_tat_ro_booking_days_req,
                    1
                );
            }

            if ($booking_to_dvry_req > 0) {
                //$tat_booking_to_dvry=round($booking_to_dvry_sum/$booking_to_dvry_req, 1);
                //$all_tat_booking_to_dvry=$all_tat_booking_to_dvry+$tat_booking_to_dvry;
                $all_tat_booking_to_dvry_days_sum =
                    $all_tat_booking_to_dvry_days_sum + $booking_to_dvry_sum;
                $all_tat_booking_to_dvry_days_req =
                    $all_tat_booking_to_dvry_days_req + $booking_to_dvry_req;
                //$all_tat_booking_to_dvry=round($all_tat_booking_to_dvry_days_sum/$all_tat_booking_to_dvry_days_req, 1);
            }
            if ($jlr_booking_to_dvry_req > 0) {
                //$jlr_tat_booking_to_dvry=round($jlr_booking_to_dvry_sum/$jlr_booking_to_dvry_req, 1);
                //$jlr_all_tat_booking_to_dvry=$jlr_all_tat_booking_to_dvry+$jlr_tat_booking_to_dvry;
                $jlr_all_tat_booking_to_dvry_days_sum =
                    $jlr_all_tat_booking_to_dvry_days_sum +
                    $jlr_booking_to_dvry_sum;
                $jlr_all_tat_booking_to_dvry_days_req =
                    $jlr_all_tat_booking_to_dvry_days_req +
                    $jlr_booking_to_dvry_req;
                //$jlr_all_tat_booking_to_dvry=round($jlr_all_tat_booking_to_dvry_days_sum/$jlr_all_tat_booking_to_dvry_days_req, 1);
            }
            if ($njlr_booking_to_dvry_req > 0) {
                //$njlr_tat_booking_to_dvry=round($njlr_booking_to_dvry_sum/$njlr_booking_to_dvry_req, 1);
                //$njlr_all_tat_booking_to_dvry=$njlr_all_tat_booking_to_dvry+$njlr_tat_booking_to_dvry;
                $njlr_all_tat_booking_to_dvry_days_sum =
                    $njlr_all_tat_booking_to_dvry_days_sum +
                    $njlr_booking_to_dvry_sum;
                $njlr_all_tat_booking_to_dvry_days_req =
                    $njlr_all_tat_booking_to_dvry_days_req +
                    $njlr_booking_to_dvry_req;
                //$njlr_all_tat_booking_to_dvry=round($njlr_all_tat_booking_to_dvry_days_sum/$njlr_all_tat_booking_to_dvry_days_req, 1);
            }

            if ($delv_rc_days_req > 0) {
                //$tat_delivery_to_rc=round($delv_rc_days_sum/$delv_rc_days_req, 1);
                //$all_tat_delivery_to_rc=$all_tat_delivery_to_rc+$tat_delivery_to_rc;
                $all_tat_delivery_to_rc_days_sum =
                    $all_tat_delivery_to_rc_days_sum + $delv_rc_days_sum;
                $all_tat_delivery_to_rc_days_req =
                    $all_tat_delivery_to_rc_days_req + $delv_rc_days_req;
                //$all_tat_delivery_to_rc=round($all_tat_delivery_to_rc_days_sum/$all_tat_delivery_to_rc_days_req, 1);
            }
            if ($jlr_delv_rc_days_req > 0) {
                //$jlr_tat_delivery_to_rc=round($jlr_delv_rc_days_sum/$jlr_delv_rc_days_req, 1);
                //$jlr_all_tat_delivery_to_rc=$jlr_all_tat_delivery_to_rc+$jlr_tat_delivery_to_rc;
                $jlr_all_tat_delivery_to_rc_days_sum =
                    $jlr_all_tat_delivery_to_rc_days_sum +
                    $jlr_delv_rc_days_sum;
                $jlr_all_tat_delivery_to_rc_days_req =
                    $jlr_all_tat_delivery_to_rc_days_req +
                    $jlr_delv_rc_days_req;
                //$jlr_all_tat_delivery_to_rc=round($jlr_all_tat_delivery_to_rc_days_sum/$jlr_all_tat_delivery_to_rc_days_req, 1);
            }
            if ($njlr_delv_rc_days_req > 0) {
                //$njlr_tat_delivery_to_rc=round($njlr_delv_rc_days_sum/$njlr_delv_rc_days_req, 1);
                //$njlr_all_tat_delivery_to_rc=$njlr_all_tat_delivery_to_rc+$njlr_tat_delivery_to_rc;
                $njlr_all_tat_delivery_to_rc_days_sum =
                    $njlr_all_tat_delivery_to_rc_days_sum +
                    $njlr_delv_rc_days_sum;
                $njlr_all_tat_delivery_to_rc_days_req =
                    $njlr_all_tat_delivery_to_rc_days_req +
                    $njlr_delv_rc_days_req;
                //$njlr_all_tat_delivery_to_rc=round($njlr_all_tat_delivery_to_rc_days_sum/$njlr_all_tat_delivery_to_rc_days_req, 1);
            }
            $all_ytd_refurb_sum = $all_ytd_refurb_sum + $ytd_refurb_sum;
            $jlr_all_ytd_refurb_sum =
                $jlr_all_ytd_refurb_sum + $jlr_ytd_refurb_sum;
            $njlr_all_ytd_refurb_sum =
                $njlr_all_ytd_refurb_sum + $njlr_ytd_refurb_sum;

            $all_mtd_refurb_sum = $all_mtd_refurb_sum + $mtd_refurb_sum;
            $jlr_all_mtd_refurb_sum =
                $jlr_all_mtd_refurb_sum + $jlr_mtd_refurb_sum;
            $njlr_all_mtd_refurb_sum =
                $njlr_all_mtd_refurb_sum + $njlr_mtd_refurb_sum;

            $ytd_costs["total"]["ytd_refurb_sum"] =
                $ytd_refurb_sum > 0 ? $ytd_refurb_sum : 0;
            $mtd_costs["total"]["mtd_refurb_sum"] =
                $mtd_refurb_sum > 0 ? $mtd_refurb_sum : 0;

            $mtd_costs["jlr"]["mtd_refurb_sum"] =
                $jlr_mtd_refurb_sum > 0 ? $jlr_mtd_refurb_sum : 0;
            $mtd_costs["non_jlr"]["mtd_refurb_sum"] =
                $njlr_mtd_refurb_sum > 0 ? $njlr_mtd_refurb_sum : 0;
            //Broker inventory
            

            $tat_booking_deliv_sum = $tat_booking_deliv_req = $jlr_tat_booking_deliv_sum = $jlr_tat_booking_deliv_req = $njlr_tat_booking_deliv_sum = $njlr_tat_booking_deliv_req = 0;
            $tat_delivery_date_sum = $tat_delivery_date_req = $jlr_tat_delivery_date_sum = $jlr_tat_delivery_date_req = $njlr_tat_delivery_date_sum = $njlr_tat_delivery_date_req = 0;
           
            $tat_booking_deliv_avg = $jlr_tat_booking_deliv_avg = $njlr_tat_booking_deliv_avg = $tat_delivery_rcavg = $jlr_tat_delivery_rcavg = $njlr_tat_delivery_rcavg = 0;
            if ($tat_booking_deliv_req > 0) {
                //$tat_booking_deliv_avg=round($tat_booking_deliv_sum/$tat_booking_deliv_req, 1);
                //$all_tat_booking_deliv_avg=$all_tat_booking_deliv_avg+$tat_booking_deliv_avg;
                $all_tat_booking_deliv_avg_days_sum =
                    $all_tat_booking_deliv_avg_days_sum +
                    $tat_booking_deliv_sum;
                $all_tat_booking_deliv_avg_days_req =
                    $all_tat_booking_deliv_avg_days_req +
                    $tat_booking_deliv_req;
                //$all_tat_booking_deliv_avg=round($all_tat_booking_deliv_avg_days_sum/$all_tat_booking_deliv_avg_days_req, 1);
            }
            if ($jlr_tat_booking_deliv_req > 0) {
                //$jlr_tat_booking_deliv_avg=round($jlr_tat_booking_deliv_sum/$jlr_tat_booking_deliv_req, 1);
                //$jlr_all_tat_booking_deliv_avg=$jlr_all_tat_booking_deliv_avg+$jlr_tat_booking_deliv_avg;
                $jlr_all_tat_booking_deliv_avg_days_sum =
                    $jlr_all_tat_booking_deliv_avg_days_sum +
                    $jlr_tat_booking_deliv_sum;
                $jlr_all_tat_booking_deliv_avg_days_req =
                    $jlr_all_tat_booking_deliv_avg_days_req +
                    $jlr_tat_booking_deliv_req;
                //$jlr_all_tat_booking_deliv_avg=round($jlr_all_tat_booking_deliv_avg_days_sum/$jlr_all_tat_booking_deliv_avg_days_req, 1);
            }
            if ($njlr_tat_booking_deliv_req > 0) {
                //$njlr_tat_booking_deliv_avg=round($njlr_tat_booking_deliv_sum/$njlr_tat_booking_deliv_req, 1);
                //$njlr_all_tat_booking_deliv_avg=$njlr_all_tat_booking_deliv_avg+$njlr_tat_booking_deliv_avg;
                $njlr_all_tat_booking_deliv_avg_days_sum =
                    $njlr_all_tat_booking_deliv_avg_days_sum +
                    $njlr_tat_booking_deliv_sum;
                $njlr_all_tat_booking_deliv_avg_days_req =
                    $njlr_all_tat_booking_deliv_avg_days_req +
                    $njlr_tat_booking_deliv_req;
                //$njlr_all_tat_booking_deliv_avg=round($njlr_all_tat_booking_deliv_avg_days_sum/$njlr_all_tat_booking_deliv_avg_days_req, 1);
            }

            if ($tat_delivery_date_req > 0) {
                //$tat_delivery_rcavg=round($tat_delivery_date_sum/$tat_delivery_date_req, 1);
                //$all_tat_delivery_avg=$all_tat_delivery_avg+$tat_delivery_avg;
                $all_tat_delivery_avg_days_sum =
                    $all_tat_delivery_avg_days_sum + $tat_delivery_date_sum;
                $all_tat_delivery_avg_days_req =
                    $all_tat_delivery_avg_days_req + $tat_delivery_date_req;
                //$all_tat_delivery_avg=round($all_tat_delivery_avg_days_sum/$all_tat_delivery_avg_days_req, 1);
            }
            if ($jlr_tat_delivery_date_req > 0) {
                //$jlr_tat_delivery_rcavg=round($jlr_tat_delivery_date_sum/$jlr_tat_delivery_date_req, 1);
                //$jlr_all_tat_delivery_avg=$jlr_all_tat_delivery_avg+$jlr_tat_delivery_rcavg;
                $jlr_all_tat_delivery_avg_days_sum =
                    $jlr_all_tat_delivery_avg_days_sum +
                    $jlr_tat_delivery_date_sum;
                $jlr_all_tat_delivery_avg_days_req =
                    $jlr_all_tat_delivery_avg_days_req +
                    $jlr_tat_delivery_date_req;
                //$jlr_all_tat_delivery_avg=round($jlr_all_tat_delivery_avg_days_sum/$jlr_all_tat_delivery_avg_days_req, 1);
            }
            if ($njlr_tat_delivery_date_req > 0) {
                //$njlr_tat_delivery_rcavg=round($njlr_tat_delivery_date_sum/$njlr_tat_delivery_date_req, 1);
                //$njlr_all_tat_delivery_avg=$njlr_all_tat_delivery_avg+$njlr_tat_delivery_rcavg;
                $njlr_all_tat_delivery_avg_days_sum =
                    $njlr_all_tat_delivery_avg_days_sum +
                    $njlr_tat_delivery_date_sum;
                $njlr_all_tat_delivery_avg_days_req =
                    $njlr_all_tat_delivery_avg_days_req +
                    $njlr_tat_delivery_date_req;
                //$njlr_all_tat_delivery_avg=round($njlr_all_tat_delivery_avg_days_sum/$njlr_all_tat_delivery_avg_days_req, 1);
            }

            $tat_booking_to_dvry = $jlr_tat_booking_to_dvry = $njlr_tat_booking_to_dvry = $tat_delivery_to_rc = $jlr_tat_delivery_to_rc = $njlr_tat_delivery_to_rc = 0;
            $book_del_req = $book_del_sum = $jlr_book_del_req = $jlr_book_del_sum = $njlr_book_del_sum = $njlr_book_del_req = $del_rc_sum = $del_rc_req = $jlr_del_rc_sum = $jlr_del_rc_req = $njlr_del_rc_sum = $njlr_del_rc_req = 0;
            $all_book_del_sum = $all_book_del_req = $jlr_all_book_del_sum = $jlr_all_book_del_req = $njlr_all_book_del_sum = $njlr_all_book_del_req = $all_del_rc_sum = $all_del_rc_req = $jlr_all_del_rc_sum = $njlr_all_del_rc_sum = $jlr_all_del_rc_req = $njlr_all_del_rc_req = 0;
            //Booking to delivery average calculation of both retail and b2b
            $book_del_sum = $booking_to_dvry_sum + $tat_booking_deliv_sum;
            $book_del_req = $tat_booking_deliv_req + $booking_to_dvry_req;
            $tat_booking_to_dvry =
                $book_del_req > 0 ? round($book_del_sum / $book_del_req, 1) : 0;
            $all_book_del_sum =
                $all_tat_booking_to_dvry_days_sum +
                $all_tat_booking_deliv_avg_days_sum;
            $all_book_del_req =
                $all_tat_booking_to_dvry_days_req +
                $all_tat_booking_deliv_avg_days_req;
            $all_tat_booking_to_dvry =
                $all_book_del_req > 0
                    ? round($all_book_del_sum / $all_book_del_req, 1)
                    : 0;

            $jlr_book_del_sum =
                $jlr_booking_to_dvry_sum + $jlr_tat_booking_deliv_sum;
            $jlr_book_del_req =
                $jlr_tat_booking_deliv_req + $jlr_booking_to_dvry_req;
            $jlr_tat_booking_to_dvry =
                $jlr_book_del_req > 0
                    ? round($jlr_book_del_sum / $jlr_book_del_req, 1)
                    : 0;
            $jlr_all_book_del_sum =
                $jlr_all_tat_booking_to_dvry_days_sum +
                $jlr_all_tat_booking_deliv_avg_days_sum;
            $jlr_all_book_del_req =
                $jlr_all_tat_booking_to_dvry_days_req +
                $jlr_all_tat_booking_deliv_avg_days_req;
            $jlr_all_tat_booking_to_dvry =
                $jlr_all_book_del_req > 0
                    ? round($jlr_all_book_del_sum / $jlr_all_book_del_req, 1)
                    : 0;

            $njlr_book_del_sum =
                $njlr_booking_to_dvry_sum + $njlr_tat_booking_deliv_sum;
            $njlr_book_del_req =
                $njlr_tat_booking_deliv_req + $njlr_booking_to_dvry_req;
            $njlr_tat_booking_to_dvry =
                $njlr_book_del_req > 0
                    ? round($njlr_book_del_sum / $njlr_book_del_req, 1)
                    : 0;
            $njlr_all_book_del_sum =
                $njlr_all_tat_booking_to_dvry_days_sum +
                $njlr_all_tat_booking_deliv_avg_days_sum;
            $njlr_all_book_del_req =
                $njlr_all_tat_booking_to_dvry_days_req +
                $njlr_all_tat_booking_deliv_avg_days_req;
            $njlr_all_tat_booking_to_dvry =
                $njlr_all_book_del_req > 0
                    ? round($njlr_all_book_del_sum / $njlr_all_book_del_req, 1)
                    : 0;

            //Delivery to rc transferred average calculation of both retail and b2b
            $del_rc_sum = $delv_rc_days_sum + $tat_delivery_date_sum;
            $del_rc_req = $delv_rc_days_req + $tat_delivery_date_req;
            $tat_delivery_to_rc =
                $del_rc_req > 0 ? round($del_rc_sum / $del_rc_req, 1) : 0;
            $all_del_rc_sum =
                $all_tat_delivery_to_rc_days_sum +
                $all_tat_delivery_avg_days_sum;
            $all_del_rc_req =
                $all_tat_delivery_to_rc_days_req +
                $all_tat_delivery_avg_days_req;
            $all_tat_delivery_to_rc =
                $all_del_rc_req > 0
                    ? round($all_del_rc_sum / $all_del_rc_req, 1)
                    : 0;

            $jlr_del_rc_sum =
                $jlr_delv_rc_days_sum + $jlr_tat_delivery_date_sum;
            $jlr_del_rc_req =
                $jlr_delv_rc_days_req + $jlr_tat_delivery_date_req;
            $jlr_tat_delivery_to_rc =
                $jlr_del_rc_req > 0
                    ? round($jlr_del_rc_sum / $jlr_del_rc_req, 1)
                    : 0;
            $jlr_all_del_rc_sum =
                $jlr_all_tat_delivery_to_rc_days_sum +
                $jlr_all_tat_delivery_avg_days_sum;
            $jlr_all_del_rc_req =
                $jlr_all_tat_delivery_to_rc_days_req +
                $jlr_all_tat_delivery_avg_days_req;
            $jlr_all_tat_delivery_to_rc =
                $jlr_all_del_rc_req > 0
                    ? round($jlr_all_del_rc_sum / $jlr_all_del_rc_req, 1)
                    : 0;

            $njlr_del_rc_sum =
                $njlr_delv_rc_days_sum + $njlr_tat_delivery_date_sum;
            $njlr_del_rc_req =
                $njlr_delv_rc_days_req + $njlr_tat_delivery_date_req;
            $njlr_tat_delivery_to_rc =
                $njlr_del_rc_req > 0
                    ? round($njlr_del_rc_sum / $njlr_del_rc_req, 1)
                    : 0;
            $njlr_all_del_rc_sum =
                $njlr_all_tat_delivery_to_rc_days_sum +
                $njlr_all_tat_delivery_avg_days_sum;
            $njlr_all_del_rc_req =
                $njlr_all_tat_delivery_to_rc_days_req +
                $njlr_all_tat_delivery_avg_days_req;
            $njlr_all_tat_delivery_to_rc =
                $njlr_all_del_rc_req > 0
                    ? round($njlr_all_del_rc_sum / $njlr_all_del_rc_req, 1)
                    : 0;

            //$tat_bking_done=$tat_booking_to_dvry+$tat_booking_deliv_avg;
            //$tat_del_torc=$tat_delivery_to_rc+$tat_delivery_avg;

            $tat["total"]["tat_trade_refu"] =
                $tat_evalreq_evaldone > 0 ? $tat_evalreq_evaldone : 0;
            $tat["total"]["tat_eval_trade_done"] =
                $tat_eval_trade_done > 0 ? $tat_eval_trade_done : 0;
            $tat["total"]["tat_trad_in_ro"] =
                $tat_trad_in_ro > 0 ? $tat_trad_in_ro : 0;
            $tat["total"]["tat_ro_to_booking"] =
                $tat_ro_booking_days > 0 ? $tat_ro_booking_days : 0;
            $tat["total"]["tat_booking_to_dvry"] =
                $tat_booking_to_dvry > 0 ? $tat_booking_to_dvry : 0;
            $tat["total"]["tat_delivery_to_rc"] =
                $tat_delivery_to_rc > 0 ? $tat_delivery_to_rc : 0;

            //jlr
            //$jlr_tat_bking_done=$jlr_tat_booking_to_dvry+$jlr_tat_booking_deliv_avg;
            //$jlr_tat_del_torc=$jlr_tat_delivery_to_rc+$jlr_tat_delivery_avg;

            $tat["jlr"]["tat_trade_refu"] =
                $jlr_tat_evalreq_evaldone > 0 ? $jlr_tat_evalreq_evaldone : 0;
            $tat["jlr"]["tat_eval_trade_done"] =
                $jlr_tat_eval_trade_done > 0 ? $jlr_tat_eval_trade_done : 0;
            $tat["jlr"]["tat_trad_in_ro"] =
                $jlr_tat_trad_in_ro > 0 ? $jlr_tat_trad_in_ro : 0;
            $tat["jlr"]["tat_ro_to_booking"] =
                $jlr_tat_ro_booking_days > 0 ? $jlr_tat_ro_booking_days : 0;
            $tat["jlr"]["tat_booking_to_dvry"] =
                $jlr_tat_booking_to_dvry > 0 ? $jlr_tat_booking_to_dvry : 0;
            $tat["jlr"]["tat_delivery_to_rc"] =
                $jlr_tat_delivery_to_rc > 0 ? $jlr_tat_delivery_to_rc : 0;

            //njlr
            //$njlr_tat_bking_done=$njlr_tat_booking_to_dvry+$njlr_tat_booking_deliv_avg;
            //$njlr_tat_del_torc=$njlr_tat_delivery_to_rc+$njlr_tat_delivery_avg;

            $tat["non_jlr"]["tat_trade_refu"] =
                $njlr_tat_evalreq_evaldone > 0 ? $njlr_tat_evalreq_evaldone : 0;
            $tat["non_jlr"]["tat_eval_trade_done"] =
                $njlr_tat_eval_trade_done > 0 ? $njlr_tat_eval_trade_done : 0;
            $tat["non_jlr"]["tat_trad_in_ro"] =
                $njlr_tat_trad_in_ro > 0 ? $njlr_tat_trad_in_ro : 0;
            $tat["non_jlr"]["tat_ro_to_booking"] =
                $njlr_tat_ro_booking_days > 0 ? $njlr_tat_ro_booking_days : 0;
            $tat["non_jlr"]["tat_booking_to_dvry"] =
                $njlr_tat_booking_to_dvry > 0 ? $njlr_tat_booking_to_dvry : 0;
            $tat["non_jlr"]["tat_delivery_to_rc"] =
                $njlr_tat_delivery_to_rc > 0 ? $njlr_tat_delivery_to_rc : 0;

            //Gross Profit And Net profit[Inventory+Sales]
            /*$grs_qry =
                "SELECT i.actual_bp,i.expected_selling_price,i.final_refurb_cost,i.accessories_total,i.other_expenses,b.total_sp,b.base_sp,b.refurb_cost,b.accessories_cost,b.rto,b.tcs,date(b.car_delivery_date) car_delivery_date   FROM `inventory` as i inner join buyleads as b ON(b.booked_car=i.id and b.status>=10000 and b.status<99999 and b.branch='" .
                $assign_branches[$a] .
                "');";*/
            //echo $grs_qry;exit;
           // $get_grsprfleads = mysqli_query($connection, $grs_qry);
            //Gross And Net
            $y_net_prf_sum = $y_grs_prf_sum = $m_net_prf_sum = $m_grs_prf_sum = 0;
            // while ($get_row = mysqli_fetch_assoc($get_grsprfleads)) {
            //     $yn_lnd_cost = $mn_lnd_cost = 0;
            //     $ygrs_prf = $mgrs_prf = 0;
            //     $ybs_sel_pr = $mbs_sel_pr = 0;
            //     $ynet_prf = $mnet_prf = 0;
            //     $act_bypr = 0;
            //     if ($get_row["actual_bp"] > 0) {
            //         $act_bypr = $get_row["actual_bp"];
            //     }
            //     /*else if($get_row['expected_selling_price']>0){
            //             $act_bypr=$get_row['expected_selling_price'];
            //         }*/
            //     //YTD Gross And Net
            //     if (
            //         $get_row["car_delivery_date"] != "0000-00-00" &&
            //         $get_row["car_delivery_date"] >= $ytd_start_date &&
            //         $get_row["car_delivery_date"] <= $yesterday
            //     ) {
            //         $yn_lnd_cost =
            //             (int) $act_bypr +
            //             (int) $get_row["final_refurb_cost"] +
            //             (int) $get_row["accessories_total"] +
            //             (int) $get_row["other_expenses"];
            //         $ybs_sel_pr = $get_row["base_sp"];
            //         $ygrs_prf = $get_row["total_sp"] - $act_bypr;
            //         $ynet_prf = $ybs_sel_pr - $act_bypr;
            //         $y_net_prf_sum = $y_net_prf_sum + $ynet_prf;
            //         $y_grs_prf_sum = $y_grs_prf_sum + $ygrs_prf;
            //     }
            //     //MTD Gross And Net
            //     if (
            //         $get_row["car_delivery_date"] != "0000-00-00" &&
            //         $get_row["car_delivery_date"] >= $mtd_start_date &&
            //         $get_row["car_delivery_date"] <= $yesterday
            //     ) {
            //         $mn_lnd_cost =
            //             (int) $act_bypr +
            //             (int) $get_row["final_refurb_cost"] +
            //             (int) $get_row["accessories_total"] +
            //             (int) $get_row["other_expenses"];
            //         $mbs_sel_pr = $get_row["base_sp"];
            //         $mgrs_prf = $get_row["total_sp"] - $act_bypr;
            //         $mnet_prf = $mbs_sel_pr - $act_bypr;

            //         $m_net_prf_sum = $m_net_prf_sum + $mnet_prf;
            //         $m_grs_prf_sum = $m_grs_prf_sum + $mgrs_prf;
            //     }
            // } //gross forloop close

            //B2B Gross+Net
            
            $yb2b_net_prf_sum = $yb2b_grs_prf_sum = 0;
            $mb2b_net_prf_sum = $mb2b_grs_prf_sum = 0;
           

            $all_y_net_prf_sum =
                (int) $all_y_net_prf_sum +
                (int) $y_net_prf_sum +
                (int) $yb2b_net_prf_sum;
            $all_y_grs_prf_sum =
                (int) $all_y_grs_prf_sum +
                (int) $y_grs_prf_sum +
                (int) $yb2b_grs_prf_sum;

            $all_m_net_prf_sum =
                (int) $all_m_net_prf_sum +
                (int) $m_net_prf_sum +
                (int) $mb2b_net_prf_sum;
            $all_m_grs_prf_sum =
                (int) $all_m_grs_prf_sum +
                (int) $m_grs_prf_sum +
                (int) $mb2b_grs_prf_sum;

            $ytd_costs["total"]["y_net_prf_sum"] =
                (int) $y_net_prf_sum + (int) $yb2b_net_prf_sum;
            $ytd_costs["total"]["y_grs_prf_sum"] =
                (int) $y_grs_prf_sum + (int) $yb2b_grs_prf_sum;
            $mtd_costs["total"]["m_net_prf_sum"] =
                (int) $m_net_prf_sum + (int) $mb2b_net_prf_sum;
            $mtd_costs["total"]["m_grs_prf_sum"] =
                (int) $m_grs_prf_sum + (int) $mb2b_grs_prf_sum;

            $dealer["manager_id"] = $j["id"];
            $dealer["manager_name"] = $j["name"];
            $dealer["assign_branches"] = $assign_branches;
            $dealer_data["ytd_counts"] = $ytd_counts ? $ytd_counts : "";
            $dealer_data["mtd_counts"] = $mtd_counts ? $mtd_counts : "";
            $dealer_data["ytd_costs"] = $ytd_costs ? $ytd_costs : "";
            $dealer_data["mtd_costs"] = $mtd_costs ? $mtd_costs : "";
            $dealer_data["tat"] = $tat ? $tat : "";
            $dealer[$assign_branches[$a]] = $dealer_data;
        }//assign branch close 
        $y_bw_data['total']["ytd_eval_req"]=$all_ytd_eval_req>0?$all_ytd_eval_req:0;
        $y_bw_data['total']["ytd_eval_done"]=$all_ytd_eval_done>0?$all_ytd_eval_done:0;
        $y_bw_data['total']["ytd_tradein_done"]=$all_ytd_tradein_done>0?$all_ytd_tradein_done:0;
        $y_bw_data['total']["ytd_tradeins_sum"]=$all_ytd_tradeins_sum>0?$all_ytd_tradeins_sum:0;



        //JLR
        $y_bw_data['jlr']["ytd_eval_req"]=isset($jlr_all_ytd_eval_req)?$jlr_all_ytd_eval_req:0;
        $y_bw_data['jlr']["ytd_eval_done"]=isset($jlr_all_ytd_eval_done)?$jlr_all_ytd_eval_done:0;
        $y_bw_data['jlr']["ytd_tradein_done"]=isset($jlr_all_ytd_tradein_done)?$jlr_all_ytd_tradein_done:0;
        $y_bw_data['jlr']["ytd_tradeins_sum"]=isset($jlr_all_ytd_tradeins_sum)?$jlr_all_ytd_tradeins_sum:0;

        //NJLR
        $y_bw_data['non_jlr']["ytd_eval_req"]=isset($njlr_all_ytd_eval_req)?$njlr_all_ytd_eval_req:0;
        $y_bw_data['non_jlr']["ytd_eval_done"]=isset($njlr_all_ytd_eval_done)?$njlr_all_ytd_eval_done:0;
        $y_bw_data['non_jlr']["ytd_tradein_done"]=isset($njlr_all_ytd_tradein_done)?$njlr_all_ytd_tradein_done:0;
        $y_bw_data['non_jlr']["ytd_tradeins_sum"]=isset($njlr_all_ytd_tradeins_sum)?$njlr_all_ytd_tradeins_sum:0;

        $m_bw_data['total']["mtd_eval_req"]=$all_mtd_eval_req>0?$all_mtd_eval_req:0;
        $m_bw_data['total']["mtd_eval_done"]=$all_mtd_eval_done>0?$all_mtd_eval_done:0;
        $m_bw_data['total']["mtd_tradein_done"]=$all_mtd_tradein_done>0?$all_mtd_tradein_done:0;
        $m_bw_data['total']["mtd_sales_sum"]=$all_mtd_sales_sum>0?$all_mtd_sales_sum:0;


        //JLR
        $m_bw_data['jlr']["mtd_eval_req"]=$jlr_all_mtd_eval_req>0?$jlr_all_mtd_eval_req:0;
        $m_bw_data['jlr']["mtd_eval_done"]=$jlr_all_mtd_eval_done>0?$jlr_all_mtd_eval_done:0;
        $m_bw_data['jlr']["mtd_tradein_done"]=$jlr_all_mtd_tradein_done>0?$jlr_all_mtd_tradein_done:0;
        $m_bw_data['jlr']["mtd_sales_sum"]=$jlr_all_mtd_sales_sum>0?$jlr_all_mtd_sales_sum:0;


        //NJLR
        $m_bw_data['non_jlr']["mtd_eval_req"]=$njlr_all_mtd_eval_req>0?$njlr_all_mtd_eval_req:0;
        $m_bw_data['non_jlr']["mtd_eval_done"]=$njlr_all_mtd_eval_done>0?$njlr_all_mtd_eval_done:0;
        $m_bw_data['non_jlr']["mtd_tradein_done"]=$njlr_all_mtd_tradein_done>0?$njlr_all_mtd_tradein_done:0;
        $m_bw_data['non_jlr']["mtd_sales_sum"]=$njlr_all_mtd_sales_sum>0?$njlr_all_mtd_sales_sum:0;

        $ytd_costs_bw_data['total']["ytd_sales_leads"]=$all_ytd_sales_leads>0?$all_ytd_sales_leads:0;
        $ytd_costs_bw_data['total']["ytd_trade_out"]=$all_ytd_trade_out>0?$all_ytd_trade_out:0;
        $ytd_costs_bw_data['total']['ytd_refurb_sum']=$all_ytd_refurb_sum>0?$all_ytd_refurb_sum:0;
        $ytd_costs_bw_data['total']["ytd_sales_sum"]=$all_ytd_sales_sum>0?$all_ytd_sales_sum:0;
        $ytd_costs_bw_data['total']["y_net_prf_sum"]=$all_y_net_prf_sum;
        $ytd_costs_bw_data['total']["y_grs_prf_sum"]=$all_y_grs_prf_sum;

        //JLR
        $ytd_costs_bw_data['jlr']["ytd_sales_leads"]=$jlr_all_ytd_sales_leads>0?$jlr_all_ytd_sales_leads:0;
        $ytd_costs_bw_data['jlr']["ytd_trade_out"]=$jlr_all_ytd_trade_out>0?$jlr_all_ytd_trade_out:0;
        $ytd_costs_bw_data['jlr']['ytd_refurb_sum']=$jlr_all_ytd_refurb_sum>0?$jlr_all_ytd_refurb_sum:0;
        $ytd_costs_bw_data['jlr']["ytd_sales_sum"]=$jlr_all_ytd_sales_sum>0?$jlr_all_ytd_sales_sum:0;

        //NJLR
        $ytd_costs_bw_data['non_jlr']["ytd_sales_leads"]=$njlr_all_ytd_sales_leads>0?$njlr_all_ytd_sales_leads:0;
        $ytd_costs_bw_data['non_jlr']['ytd_refurb_sum']=$njlr_all_ytd_refurb_sum>0?$njlr_all_ytd_refurb_sum:0;
        $ytd_costs_bw_data['non_jlr']["ytd_trade_out"]=$njlr_all_ytd_trade_out>0?$njlr_all_ytd_trade_out:0;
        $ytd_costs_bw_data['non_jlr']["ytd_sales_sum"]=$njlr_all_ytd_sales_sum>0?$njlr_all_ytd_sales_sum:0;

        $mtd_costs_bw_data['total']["mtd_sales_leads"]=$all_mtd_sales_leads>0?$all_mtd_sales_leads:0;
        $mtd_costs_bw_data['total']["mtd_trade_out"]=$all_mtd_trade_out>0?$all_mtd_trade_out:0;
        $mtd_costs_bw_data['total']['mtd_refurb_sum']=$all_mtd_refurb_sum>0?$all_mtd_refurb_sum:0;
        $mtd_costs_bw_data['total']["mtd_tradeins_sum"]=$all_mtd_tradeins_sum>0?$all_mtd_tradeins_sum:0;
        $mtd_costs_bw_data['total']['m_net_prf_sum']=$all_m_net_prf_sum;
        $mtd_costs_bw_data['total']['m_grs_prf_sum']=$all_m_grs_prf_sum;  

        //JLR
        $mtd_costs_bw_data['jlr']["mtd_sales_leads"]=$jlr_all_mtd_sales_leads>0?$jlr_all_mtd_sales_leads:0;
        $mtd_costs_bw_data['jlr']["mtd_trade_out"]=$jlr_all_mtd_trade_out>0?$jlr_all_mtd_trade_out:0;
        $mtd_costs_bw_data['jlr']['mtd_refurb_sum']=$jlr_all_mtd_refurb_sum>0?$jlr_all_mtd_refurb_sum:0;
        $mtd_costs_bw_data['jlr']["mtd_tradeins_sum"]=$jlr_all_mtd_tradeins_sum>0?$jlr_all_mtd_tradeins_sum:0;

        //NJLR
        $mtd_costs_bw_data['non_jlr']["mtd_sales_leads"]=$njlr_all_mtd_sales_leads>0?$njlr_all_mtd_sales_leads:0;
        $mtd_costs_bw_data['non_jlr']["mtd_trade_out"]=$njlr_all_mtd_trade_out>0?$njlr_all_mtd_trade_out:0;
        $mtd_costs_bw_data['non_jlr']['mtd_refurb_sum']=$njlr_all_mtd_refurb_sum>0?$njlr_all_mtd_refurb_sum:0;
        $mtd_costs_bw_data['non_jlr']["mtd_tradeins_sum"]=$njlr_all_mtd_tradeins_sum>0?$njlr_all_mtd_tradeins_sum:0;

        $tat_bw_data['total']["tat_trade_refu"]=$all_tat_evalreq_evaldone>0?$all_tat_evalreq_evaldone:0;
        $tat_bw_data['total']["tat_eval_trade_done"]=$all_tat_eval_trade_done>0?$all_tat_eval_trade_done:0;
        $tat_bw_data['total']['tat_trad_in_ro']=$all_tat_trad_in_ro>0?$all_tat_trad_in_ro:0; 
        $tat_bw_data['total']['tat_ro_to_booking']=$all_tat_ro_booking_days>0?$all_tat_ro_booking_days:0;
        $tat_bw_data['total']['tat_booking_to_dvry']=$all_tat_booking_to_dvry>0?$all_tat_booking_to_dvry:0;
        $tat_bw_data['total']["tat_delivery_to_rc"]=$all_tat_delivery_to_rc>0?$all_tat_delivery_to_rc:0;

        //JLR
        $tat_bw_data['jlr']["tat_trade_refu"]=$jlr_all_tat_evalreq_evaldone>0?$jlr_all_tat_evalreq_evaldone:0;
        $tat_bw_data['jlr']["tat_eval_trade_done"]=$jlr_all_tat_eval_trade_done>0?$jlr_all_tat_eval_trade_done:0;
        $tat_bw_data['jlr']['tat_trad_in_ro']=$jlr_all_tat_trad_in_ro>0?$jlr_all_tat_trad_in_ro:0; 
        $tat_bw_data['jlr']['tat_ro_to_booking']=$jlr_all_tat_ro_booking_days>0?$jlr_all_tat_ro_booking_days:0;
        $tat_bw_data['jlr']['tat_booking_to_dvry']=$jlr_all_tat_booking_to_dvry>0?$jlr_all_tat_booking_to_dvry:0;
        $tat_bw_data['jlr']["tat_delivery_to_rc"]=$jlr_all_tat_delivery_to_rc>0?$jlr_all_tat_delivery_to_rc:0;
        //Non JLR
        $tat_bw_data['non_jlr']["tat_trade_refu"]=$njlr_all_tat_evalreq_evaldone>0?$njlr_all_tat_evalreq_evaldone:0;
        $tat_bw_data['non_jlr']["tat_eval_trade_done"]=$njlr_all_tat_eval_trade_done>0?$njlr_all_tat_eval_trade_done:0;
        $tat_bw_data['non_jlr']['tat_trad_in_ro']=$njlr_all_tat_trad_in_ro>0?$njlr_all_tat_trad_in_ro:0; 
        $tat_bw_data['non_jlr']['tat_ro_to_booking']=$njlr_all_tat_ro_booking_days>0?$njlr_all_tat_ro_booking_days:0;
        $tat_bw_data['non_jlr']['tat_booking_to_dvry']=$njlr_all_tat_booking_to_dvry>0?$njlr_all_tat_booking_to_dvry:0;
        $tat_bw_data['non_jlr']["tat_delivery_to_rc"]=$njlr_all_tat_delivery_to_rc>0?$njlr_all_tat_delivery_to_rc:0;

    } else {
        $dealer["manager_id"] = $j["id"];
        $dealer["manager_name"] = $j["name"];
        $dealer["assign_branches"] = [];
    }

    $dealer["all"]["ytd_counts"] = $y_bw_data;
    $dealer["all"]["mtd_counts"] = $m_bw_data;
    $dealer["all"]["ytd_costs"] = $ytd_costs_bw_data;
    $dealer["all"]["mtd_costs"] = $mtd_costs_bw_data;
    $dealer["all"]["tat"] = $tat_bw_data;

    $dealer["all"] = $dealer["all"];
    $executive=$dealer['manager_id'];
    $result=json_encode($dealer,true);
    $user_type=1; //dealer
    $type=1; //Over View
    $created_dt=date("Y-m-d H:m:s");
    $values[] = "(
        '" . mysqli_escape_string($connection, $user_type) . "',
        '" . mysqli_escape_string($connection, $executive) . "',
        '" . mysqli_escape_string($connection, $type) . "',
        '" . mysqli_escape_string($connection, $result) . "',
        '" . mysqli_escape_string($connection, $created_dt) . "'
    )";   
}
$insert_que = implode(", ", $values) . ";";
$inserted_query = $insert_query . $insert_que;

$rs=(isset($inserted_query) && $inserted_query!='')?mysqli_query($connection,$inserted_query):'';
$totalbytes = memory_get_usage();
$mb = $totalbytes / (1024 * 1024);
$time_start = microtime(true);
echo "<p >MEMORY USAGE: " . memory_get_usage() / 1024 / 1024 . "<br>";
echo date("Y-m-d H:i:s") . "<br>";
$time_end = microtime(true);
$total_time = ($time_end - $time_start) / 60;
echo "Total execution time  " . $total_time . " seconds</p>";

//Last 7days
function getLastNDays($day, $days, $format = "Y-m-d")
{
    $exp = explode("-", $day);
    $m = $exp[1];
    $de = $exp[2];
    $y = $exp[0];
    $dateArray = [];
    for ($i = 0; $i <= $days - 1; $i++) {
        $dateArray[] = date($format, mktime(0, 0, 0, $m, $de - $i, $y));
    }
    return array_reverse($dateArray);
}
//get jaguar ID
function getJaguarMakeId()
{
    global $connection;
    $make_data = [];
    $qry =
        "select id from master_makes where make='jaguar' or make='land rover'";
    $record = mysqli_query($connection, $qry);
    while ($row = mysqli_fetch_assoc($record)) {
        $make_data[] = $row["id"];
    }
    return $make_data;
}
