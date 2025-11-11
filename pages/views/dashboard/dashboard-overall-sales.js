export default {
    name: 'DashboardOverAllSales',
    data(){
        return {
			global_error: "",
			global_token:"",
			api_calls_queue: [],
			api_call_busy: false,
			w_dates:{},
			today_overview_data:{},
			user_rights:{},
			ytd_data:{},
			mtd_data:{},
			ytd_costs_data:{},
			mtd_costs_data:{},
			tat_data:{},
			dealer_branches:[],
			mtd_table_one:{},
			ovall_week_sal:{},
			ovall_mtn_sal:{},
			ovall_sal_y_cnt:{},
			mg_ovall_week_sal:{},
			mg_ovall_mtn_sal:{},
			mg_ovall_sal_y_cnt:{},
			nmg_ovall_week_sal:{},
			nmg_ovall_mtn_sal:{},
			nmg_ovall_sal_y_cnt:{},
			ov:{
				"branch_id":"0",
			   },
			tab:"dashboard_overall_sales",
			convdt:{},
			}
    },
     template: `<div class="py-3 px-4 min-vh-100 bg-light">
                    <div class="container">
                        <h2 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1">Dashboard Over All Sales</h2>
                        <div class="dash-overview" style="background-color: #f7f7f8; min-height:800px;">
                        <div class="col-12 py-2">
                            <dashboard_tabs :active_tab="tab" v-on:clicked="show_tab($event)"></dashboard_tabs>
                        </div>
                        
                        <div class="row mt-3 align-items-center">
                        <div class="col-sm-12 d-flex justify-content-end">
                            <div class="d-flex align-items-center">
                                <div class="col"><b>Select Branch</b></div>
                                <div class="col">
                                    <select v-model="ov['branch_id']" v-on:change="chg_branch" class="form-control form-control-sm" style="min-width:200px;">
                                        <option value="0">All</option>
                                        <option v-for="res,i in dealer_branches" v-bind:value="res.enc_branch_id">{{res.brn_name}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        </div>

                        
                        <div class="row mt-3">
                        <div class=" col-sm-10">
                    
                        <h6>Lead Analysis</h6>
                    
                        <div class="row p-0">
                            <div class="col-sm-9 pl-0">
                        
                                <div class="row card p-3">
                                    <div class="col-md-auto p-0">
                                        
                        <table width="100%" border="0" cellspacing="1" cellpadding="10" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
                            <thead>  
                            <tr>
                            <th bgcolor="#ffffff" rowspan="2" class="align-middle">Status</th>
                            <th colspan="7" bgcolor="#ffffff">Weekly Count</th>
                            
                            <th bgcolor="#ffffff" rowspan="2" class="align-middle">MTD Total</th>
                            
                            </tr>
                            <tr bgcolor="#ffffff">
                                <td  v-for="v,i in convdt">{{v}}</td>
                            </tr>

                            
                        <tr bgcolor="#f2f2f2">
                            <th>Total Leads</th>
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[0]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tot_leads?.[w_dates?.[6]] || 0 }}
                            </td>
                            <td>
                                {{ovall_mtn_sal?.mnt_totl_leads}} 
                            </td>
                            
                            </tr>
                            
                            <tr bgcolor="#ffffff">
                            <th>Online Leads</th>
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[0]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_web_leads?.[w_dates?.[6]] || 0 }}
                            </td>
                            <td>
                                {{ovall_mtn_sal?.mnt_web_leads}} 
                            </td>
                            </tr>
                            
                            <tr bgcolor="#f2f2f2">
                            <th>Manual Leads</th>
                            <td>
                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[0]] || 0 }}
                            </td>
                            <td>

                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>

                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_walkin_leads?.[w_dates?.[6]] || 0 }}
                            </td>
                            
                                <td>
                                {{ovall_mtn_sal?.mnt_walkin_leads}} 
                            </td>
                            
                            </tr>
                            
                            <tr bgcolor="#ffffff">
                            <th>Test Drive Taken	</th>
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[0]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_tdrive?.[w_dates?.[6]] || 0 }}
                            </td>
                            <td>
                                {{ovall_mtn_sal?.mnth_test_driv}} 
                            </td>
                            
                            </tr>
                            
                            
                            <tr bgcolor="#f2f2f2">
                            <th>Booking Done</th>
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[0]] || 0 }}

                            </td>
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.week_booking_done?.[w_dates?.[6]] || 0 }}
                            </td>
                            <td>
                                {{ovall_mtn_sal?.mnt_booking_done}} 
                            </td>
                            
                            </tr>
                            
                            <tr bgcolor="#ffffff">
                            <th>Delivered</th>
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[0]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_delivered?.[w_dates?.[6]] || 0 }}
                            </td>
                                <td>
                                {{ovall_mtn_sal?.mnt_dlvry}} 
                            </td>
                            
                            </tr>
                                <tr bgcolor="#f2f2f2">
                            <th>Lost</th>
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[0]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[1]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[2]] || 0 }}
                            </td> 
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[3]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[4]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[5]] || 0 }}
                            </td>
                            <td>
                                {{ ovall_week_sal.weekly_lossed_leads?.[w_dates?.[6]] || 0 }}
                            </td>
                                <td>
                                {{ovall_mtn_sal?.mnt_los_leads}} 
                            </td>
                            
                            </tr>
                            </thead>
                    
                    </table>


                                        
                                        
                                </div>
                            </div>
                            
                            
                            
                                        
                        </div>
                        
                        
                        <div class="col-sm-3 mt-3 pr-0">
                    
                    
                    <div class="row card p-3 mt-0 align-width-eq" style="justify-content:flex-start;">
                            <table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
                            <thead>
                                            <tr>
                                                <th bgcolor="#ffffff" rowspan="2" class="align-middle">Status</th>
                                                <th colspan="7" bgcolor="#ffffff">MTD Total</th>
                                                
                                            </tr>
                                            <tr bgcolor="#ffffff">

                                                <td>JLR</td>
                                                <td>Non-JLR</td>
                                                
                                            </tr>
                                            
                                            <tr bgcolor="#f2f2f2">
                                                <th>Total Leads</th>
                                                <td>{{ovall_mtn_sal['mnt_mg_tot_leads']}}</td>
                                <td>{{ovall_mtn_sal['mnt_nmg_tot_leads']}}</td>
                                            </tr>
                                            <tr bgcolor="#ffffff">
                                                <th>Test Drive Taken</th>
                                                <td>{{ovall_mtn_sal['mnt_mg_test_drves']}}</td>
                                <td>{{ovall_mtn_sal['mnt_nmg_test_drves']}}</td>
                                            </tr>
                                            <tr bgcolor="#f2f2f2">
                                                <th>Booking Done</th>
                                                <td>{{ovall_mtn_sal['mnt_mg_boking_done']}}</td>
                                <td>{{ovall_mtn_sal['mnt_nmg_boking_done']}}</td>
                                            </tr>
                                    <tr bgcolor="#ffffff">
                                                <th>Delivered</th>
                                                <td>{{ovall_mtn_sal['mnt_mg_delvred']}}</td>
                                <td>{{ovall_mtn_sal['mnt_nmg_delvred']}}</td>
                                            </tr>
                                <tr bgcolor="#f2f2f2">
                                <th>Lost</th>
                                <td>{{ovall_mtn_sal['mnt_mg_los_leads']}}</td>
                                <td>{{ovall_mtn_sal['mnt_nmg_los_leads']}}</td>
                                </tr>
                            </thead>
                                        </table>
                    </div>
                    
                    </div>
                    </div>
                    
                    
                    
                    
                    </div>
                        
                        
                        <div class="px-0 mt-0 col-md-2 pl-3">
                        <h6 class="pl-0">YTD Analysis</h6>
                        <div class="block1" style="margin-bottom:20px;">
                            <div class="row card p-3 green1">
                            <table width="100%" cellpadding="1" cellspacing="1" class="table table-sm align-middle text-center table-bordered p-1 mb-0">
                                <thead>
                                
                                <tr>
                                        <th rowspan="3" class="align-middle text-left" width="120">Leads</th>
                                        <!-- <td class="text-left">JLR</td>
                                        <td>{{mg_ovall_sal_y_cnt['ytd_tot_leads']}}</td> -->
                                    
                                    <!-- <tr>
                                        <td class="text-left">NON JLR</td>
                                        <td>{{nmg_ovall_sal_y_cnt['ytd_tot_leads']}}</td>
                                    </tr> -->
                                    
                                        <!-- <td class="text-left">TOTAL</td> -->
                                        <td>{{ovall_sal_y_cnt['ytd_tot_leads']}}</td>
                                    </tr>
                                </thead>
                                    
                                    
                                    
                            </table>
                        </div>
                        </div>
                        <div class="block1" style="margin-bottom:20px;">
                                <div class="row card p-3 green2">
                                    <table width="100%" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
                                    <thead>
                                        <tr>
                                            <th rowspan="3" class="align-middle text-left" width="120">Test&nbsp;Drives</th>
                                            <!-- <td class="text-left">JLR</td>
                                            <td>{{mg_ovall_sal_y_cnt['ytd_test_drves']}}</td> -->
                                    
                                        <!-- <tr>
                                            <td class="text-left">NON JLR</td>
                                            <td>{{nmg_ovall_sal_y_cnt['ytd_test_drves']}}</td>
                                        </tr> -->
                                        
                                            <!-- <td class="text-left">TOTAL</td> -->
                                            <td>{{ovall_sal_y_cnt['ytd_test_drves']}}</td>
                                        </tr>  
                                    </thead>
                                    </table>
                                </div>
                                </div>
                                <div class="block1" style="margin-bottom:20px;">
                                <div class="row card p-3 green3">
                                    <!-- <p style="backgroud-color:red;text-align: center;">Negotiations</p> -->
                                    <table width="100%" class="table table-sm align-middle text-center table-bordered p-3 mb-0">   
                                    <thead>
                                    
                                    <tr>
                                        <th rowspan="3" class="align-middle text-left" width="120">Negotiations</th>
                                        <!-- <td class="text-left">JLR</td>
                                        <td>{{mg_ovall_sal_y_cnt['ytd_neg_leads']}}</td> -->
                                    
                                    <!--  <tr>
                                        <td class="text-left">NON JLR</td>
                                        <td>{{nmg_ovall_sal_y_cnt['ytd_neg_leads']}}</td>
                                    </tr> -->
                                    
                                        <!-- <td class="text-left">TOTAL</td> -->
                                        <td> {{ovall_sal_y_cnt['ytd_neg_leads']}}</td>
                                    </tr>                  
                                    </thead>               
                                    </table>
                                </div> 
                                </div>
                                <div class="block1">
                                <div class="row card p-3 green4">
                                    <table width="100%" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
                                    <thead>
                                        <tr>
                                            <th rowspan="3" class="align-middle text-left" width="120">Wins</th>
                                            <!-- <td class="text-left">JLR</td>
                                            <td>{{mg_ovall_sal_y_cnt['ytd_wins_leads']}}</td> -->
                                    
                                        <!-- <tr>
                                            <td class="text-left">NON JLR</td>
                                            <td>{{nmg_ovall_sal_y_cnt['ytd_wins_leads']}}</td>
                                        </tr> -->
                                    
                                            <!-- <td class="text-left">TOTAL</td> -->
                                            <td>{{ovall_sal_y_cnt['ytd_wins_leads']}}</td>
                                        </tr>
                                    </thead>
                                    </table>
                                    </div>
                                </div>
                            </div>
                            
                            
                        
                        </div>
                        
                        <div class="row pl-3">
                        <div class="col-sm-12 p-0">
                        <div class="col-sm-12 pl-0 ">
                                                <h6 class="pl-0 py-2">Over All Sales</h6>
                                            </div>
                            <div class="row card p-3 mt-1" style="overflow-x:auto;">
                                            <table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered p-3 mb-0">  

                                                <thead>
                                                    
                    <tr>
                        <th bgcolor="#ffffff" rowspan="3" class="align-middle">YTD Report</th>
                    
                    
                    </tr>
                    <tr bgcolor="#ffffff">
                        <th colspan="3" class="align-middle">Numbers</th>
                        <th colspan="3" class="align-middle">Gross Margin (in ₹)</th>
                        <th colspan="3" class="align-middle">Net Margin (in ₹)</th>
                        <th colspan="3" class="align-middle">RTI (Net Margin / Buying Price)</th>
                    </tr>
                    <tr bgcolor="#ffffff">
                        <td>JLR Certified</td>
                        <td>JLR Non Certified</td>
                        <td>NON MG</td>
                        <td class="text-right">JLR Certified</td>
                        <td class="text-right">JLR Non Certified</td>
                        <td class="text-right">NON JLR</td>
                        <td class="text-right">JLR Certified</td>
                        <td class="text-right">JLR Non Certified</td>
                        <td class="text-right">NON JLR</td>
                        <td>JLR Certified</td>
                        <td>JLR Non Certified</td>
                        <td>NON JLR</td>
                    </tr>
                    

                    <tr bgcolor="#f2f2f2">
                        <th>Retails</th>
                        <td>{{ovall_sal_y_cnt['ytd_ret_mg_certi_y']}}</td>
                        <td>{{ovall_sal_y_cnt['ytd_ret_mg_certi_n']}}</td>
                        <td>{{ovall_sal_y_cnt['ytd_ret_nmg_certi']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_grs_prf_sum_mg_y']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_grs_prf_sum_mg_n']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_grs_prf_sum_nmg']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_net_prf_sum_mg_y']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_net_prf_sum_mg_n']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_net_prf_sum_nmg']}}</td>
                        <td>{{ovall_sal_y_cnt['y_rti_net_mg_certify_y']}} %</td>
                        <td>{{ovall_sal_y_cnt['y_rti_net_mg_certify_n']}} %</td>
                        <td>{{ovall_sal_y_cnt['y_rti_net_nmg']}} %</td>
                    
                    </tr>
                        <tr bgcolor="#ffffff">
                        <th>B2B</th>
                        <td>{{ovall_sal_y_cnt['ytd_b2b_mg_certi_y']}}</td>
                        <td>{{ovall_sal_y_cnt['ytd_b2b_mg_certi_n']}}</td>
                        <td>{{ovall_sal_y_cnt['ytd_b2b_nmg_certi']}}</td>
                        <td class="text-right">&#x20B9; 0</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['yb2b_grs_prf_mg_no_sum']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['yb2b_grs_prf_nmg_sum']}}</td>
                        <td class="text-right">&#x20B9; 0</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['yb2b_net_prf_mg_no_sum']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['yb2b_net_prf_nmg_sum']}}</td>
                        <td>0%</td>
                        <td>{{ovall_sal_y_cnt['y_rti_b2bnet_mg_n']}} %</td>
                        <td>{{ovall_sal_y_cnt['y_rti_b2bnet_nmg']}} %</td>
                    
                    </tr>

                    <tr bgcolor="#f2f2f2">
                        <th>Total</th>
                        <td>{{Number(ovall_sal_y_cnt['ytd_ret_mg_certi_y'])+Number(ovall_sal_y_cnt['ytd_b2b_mg_certi_y'])}}</td>
                        <td>{{Number(ovall_sal_y_cnt['ytd_ret_mg_certi_n'])+Number(ovall_sal_y_cnt['ytd_b2b_mg_certi_n'])}}</td>
                        <td>{{Number(ovall_sal_y_cnt['ytd_ret_nmg_certi'])+Number(ovall_sal_y_cnt['ytd_b2b_nmg_certi'])}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_grs_prf_sum_mg_y']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_grs_prf_mgn_tot']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_grs_prf_nmg_tot']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_net_prf_sum_mg_y']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_net_prf_mgn_tot']}}</td>
                        <td class="text-right">&#x20B9; {{ovall_sal_y_cnt['y_net_prf_nonmg_tot']}} </td>
                        
                        <td>{{ovall_sal_y_cnt['y_rti_net_mg_certify_y']}} %</td>
                        <td>{{ovall_sal_y_cnt['y_rti_mg_no_tot']}} %</td>
                        <td>{{ovall_sal_y_cnt['y_rti_nmg_tot']}} %</td>
                    </tr>

                                                </thead>
                    </table>
                                
                                        
                                </div>  
                                
                    </div>
                    
                    
                    </div>
                        
                                    
                    </div>
            </div>
    </div>`

}