export default {
    name: 'DashboardStocks',
    data(){
        return {
			global_error: "",
			global_token:"",
			api_calls_queue: [],
			api_call_busy: false,
			user_rights:{},
			last7days:[],
            conv_dts:[],
            dealer_branches:[],
           	search_form:{
				enc_branch_id:"all"	
			},
           stocks_data:[],
           ytd_stock_analysis_counts:{},
           weekly_stock_counts:{},
           mtd_total_counts:{},
           refurb_amount:{},
           ytd_stock_age_analysis_counts:{}, 
           live_stock:[],
           branch_name_selected:'All',
     	}
    },
    template: `<div class="py-3 px-4 min-vh-100 bg-light">
                    <div class="container">
                        <h2 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1">Dashboard Stocks</h2>
                        <div class="dash-overview" style="background-color: #f7f7f8;">
                        <div class="col-12 py-2">
                            <dashboard_tabs :active_tab="tab" v-on:clicked="show_tab($event)"></dashboard_tabs>
                        </div>
                        <div class="col-sm-12 d-flex justify-content-end">
                            <div class="d-flex align-items-center">
                                <div class="col"><b>Select Branch</b></div>
                                <div class="col">
                                    <select v-model="search_form['enc_branch_id']" v-on:change="chg_branch" class="form-control form-control-sm" style="min-width:200px;">
                                        <option value="all">All</option>
                                        <option v-for="res,i in dealer_branches" v-bind:value="res.enc_branch_id">{{res.brn_name}}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-sm-6">
                            <div class="col-sm-12 m-0 p-0">
                                        <h6>Stock Analysis</h6>
                                    </div>
                                <div class="row card p-3" style="min-height:410px;">
                                    
                                    <div class="row">
                                        <!-- {{ytd_stock_analysis_counts}}-->
                                        <table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th bgcolor="#ffffff" rowspan="2" class="align-middle">YTD Report</th>
                                                    <th colspan="2" bgcolor="#ffffff">Numbers</th>
                                                    <th colspan="2" bgcolor="#ffffff">Value(in &#x20B9;)</th>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th>JLR</th>
                                                    <th>Non-JLR</th>
                                                    <th class="text-right">JLR</th>
                                                    <th class="text-right">Non-JLR</th>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th class="text-left pl-3">No.of Stocks Added</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_add_mg']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_add_nonmg']}}</th>
                                                    <th class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_add_mg_sum']}}</th>
                                                    <th class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_add_nonmg_sum']}}</th>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <td class="text-left pl-5"> Retail Stocks Added</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_add_r_mg']}}</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_add_r_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_add_r_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_add_r_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <td class="text-left pl-5">B2B Stocks Added</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_add_b2b_mg']}}</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_add_b2b_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_add_b2b_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_add_b2b_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th class="text-left pl-3">No.of Stocks Sold</th>
                                                    <th style="color:#059805">{{ytd_stock_analysis_counts['ytd_no_stock_sold_mg']}}</th>
                                                    <th style="color:#059805">{{ytd_stock_analysis_counts['ytd_no_stock_sold_nonmg']}}</th>
                                                    <th style="color:#059805" class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_sold_mg_sum']}}</th>
                                                    <th style="color:#059805" class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_sold_nonmg_sum']}}</th>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <td class="text-left pl-5">Retail Stocks Sold</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_sold_r_mg']}}</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_sold_r_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_sold_r_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_sold_r_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <td class="text-left pl-5">B2B Stocks Sold</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_sold_b2b_mg']}}</td>
                                                    <td>{{ytd_stock_analysis_counts['ytd_no_stock_sold_b2b_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_sold_b2b_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_sold_b2b_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th class="text-left pl-3">Un-Sold Stocks</th>
                                                    <th style="color:#cf2230">{{ytd_stock_analysis_counts['ytd_no_stock_unsold_mg']}}</th>
                                                    <th style="color:#cf2230">{{ytd_stock_analysis_counts['ytd_no_stock_unsold_nonmg']}}</th>
                                                    <th style="color:#cf2230" class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_unsold_mg_sum']}}</th>
                                                    <th style="color:#cf2230" class="text-right">&#x20B9;{{ytd_stock_analysis_counts['ytd_no_stock_unsold_nonmg_sum']}}</th>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th class="text-left pl-3">Certified Stocks</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_cert_mg']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_cert_nonmg']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_cert_mg_sum']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_cert_nonmg_sum']}}</th>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th class="text-left pl-3">Non-Certified Stocks</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_uncert_mg']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_uncert_nonmg']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_uncert_mg_sum']}}</th>
                                                    <th>{{ytd_stock_analysis_counts['ytd_no_stock_uncert_nonmg_sum']}}</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                                
                                <div class="col-sm-12 mt-4 p-0">
                                        <h6>Stock Age Analysis</h6>
                                    </div>
                                <div class="row card p-3 mt-3">
                                    
                                    <div class="row ">
                                        <table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th bgcolor="#ffffff" rowspan="2" class="align-middle">YTD Report</th>
                                                    <th colspan="2" bgcolor="#ffffff">Numbers</th>
                                                    <th colspan="2" bgcolor="#ffffff">Value(in &#x20B9;)</th>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th>JLR</th>
                                                    <th>Non-JLR</th>
                                                    <th class="text-right">JLR</th>
                                                    <th class="text-right">Non-JLR</th>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th>0 ~ 15 Days</th>
                                                    <td>{{ytd_stock_age_analysis_counts['lessthen_15_mg']}}</td>
                                                    <td>{{ytd_stock_age_analysis_counts['lessthen_15_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['lessthen_15_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['lessthen_15_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th>16 ~ 30 Days</th>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_15_mg']}}</td>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_15_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_15_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_15_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th>31 ~ 45 Days</th>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_30_mg']}}</td>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_30_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_30_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_30_nonmg_sum']}}</td>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th>46 ~ 60 Days</th>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_45_mg']}}</td>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_45_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_45_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_45_nonmg_sum']}}</td>
                                                </tr>
                                                <tr style="color:#cf2230" bgcolor="#f2f2f2">
                                                    <th>61 ~ 90 Days</th>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_60_mg']}}</td>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_60_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_60_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_60_nonmg_sum']}}</td>
                                                </tr>
                                                <tr style="color:#cf2230" bgcolor="#ffffff">
                                                    <th>Above 90</th>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_90_mg']}}</td>
                                                    <td>{{ytd_stock_age_analysis_counts['morethen_90_nonmg']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_90_mg_sum']}}</td>
                                                    <td class="text-right">&#x20B9;{{ytd_stock_age_analysis_counts['morethen_90_nonmg_sum']}}</td>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                            <div class="col-sm-12 m-0 p-0">
                                        <h6>Weekly Stock Count</h6>
                                    </div>
                                <div class="row card p-3">
                                
                                    <div class="row ">
                                        <table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered p-3">
                                            <thead>
                                                <tr>
                                                    <th bgcolor="#ffffff" rowspan="2" class="align-middle">Status</th>
                                                    <th colspan="7" bgcolor="#ffffff">Weekly Count</th>
                                                    <th bgcolor="#ffffff" rowspan="2" class="align-middle">MTD Total</th>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <!--{{conv_dts}}-->
                                                    <td v-for="date in conv_dts">{{date}}</td>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th>No.of Stocks Added</th>
                                                    <td v-for="date in last7days">{{weekly_stock_counts['weekly_no_stock_add'][date]}}</td>
                                                    <td>{{mtd_total_counts['mtd_stock_add']}}</td>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th>Refurbishment Done</th>
                                                    <td v-for="date in last7days">{{weekly_stock_counts['weekly_ref_done'][date]}}</td>
                                                    <td>{{mtd_total_counts['mtd_ref_done']}}</td>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th>Ready for Sale</th>
                                                    <td v-for="date in last7days">{{weekly_stock_counts['weekly_ready_for_sale'][date]}}</td>
                                                    <td>{{mtd_total_counts['mtd_ready_for_sale']}}</td>
                                                </tr>
                                                <tr bgcolor="#ffffff">
                                                    <th>Booked Stocks</th>
                                                    <td v-for="date in last7days">{{weekly_stock_counts['weekly_booked_stock'][date]}}</td>
                                                    <td>{{mtd_total_counts['mtd_booked_stock']}}</td>
                                                </tr>
                                                <tr bgcolor="#f2f2f2">
                                                    <th>Sold Stocks</th>
                                                    <td v-for="date in last7days">{{weekly_stock_counts['weekly_sold_stock'][date]}}</td>
                                                    <td>{{mtd_total_counts['mtd_sold_stock']}}</td>
                                                </tr>
                                            </thead>
                                        </table>
                                        <div class="col-sm-12 p-0">
                                            <table border="0" cellpadding="10" cellspacing="0" bgcolor="#ffffff" style="width: 100%; margin-top: 6px;  border:1px solid #f2f2f2;">
                                                <thead>
                                                    <tr class="border-bottom">
                                                        <th align="center" colspan="2">
                                                        </th>
                                                        <th align="center" colspan="2" class="text-right">
                                                            <h6>MTD</h6>
                                                        </th>
                                                        <th align="center" colspan="2" class="text-right">
                                                            <h6>YTD</h6>
                                                        </th>
                                                    </tr>
                                                    <tr class="border-bottom">
                                                        <th align="center" colspan="2">
                                                            Refurbishment Amount (JLR)
                                                        </th>
                                                        <th align="center" colspan="2" class="text-right">
                                                            {{'&#8377;&nbsp;' +refurb_amount['mtd_refurb_amount_mg']}}
                                                        </th>
                                                        <th align="center" colspan="2" class="text-right">
                                                            {{'&#8377;&nbsp;' +refurb_amount['ytd_refurb_amount_mg']}}
                                                        </th>
                                                    </tr>
                                                    <tr class="border-bottom">
                                                        <th align="center" colspan="2">
                                                            Refurbishment Amount (Non-JLR)
                                                        </th>
                                                        <th align="center" colspan="2" class="text-right">
                                                            {{'&#8377;&nbsp;' +refurb_amount['mtd_refurb_amount_nonmg']}}
                                                        </th>
                                                        <th align="center" colspan="2" class="text-right">
                                                            {{'&#8377;&nbsp;' +refurb_amount['ytd_refurb_amount_nonmg']}}
                                                        </th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-12 mt-4 p-0">
                                        <h6>Live Stock ({{branch_name_selected}})</h6>
                                </div>
                                <div class="row card p-3 mt-3" style="overflow-x:auto;">
                                    
                                    <div class="row  dev_height">
                                        <!--bgcolor="#333333"-->
                                        <table width="100%" border="0" cellspacing="1" cellpadding="8" class="table table-sm align-middle  table-bordered mb-0" v-if="live_stock.length!=0">
                                            <thead>
                                                <tr bgcolor="#ffffff">
                                                    <th bgcolor="#ffffff" rowspan="1">Sr.No.</th>
                                                    <th><span style="margin-left: 80px;">Vehicles</span></th>
                                                    <th class="text-right">Listing Price (&#x20B9;)</th>
                                                    <th class="text-right">Total RF Cost (&#x20B9;)</th>
                                                    <th class="text-right">Test Drives Taken</th>
                                                    <th class="text-right">Live Leads</th>
                                                </tr>
                                                <tr v-for="val,i in live_stock" :class="{even: i % 2, odd: !(i % 2)}">
                                                    <td class="text-center">{{i+1}}</td>
                                                    <td style="color:#056BA6;cursor: pointer;" v-on:click="redirect_mystock_module(val['enc_id'])"><u>{{val['manuf_year']+'&nbsp;'+val['make']+'&nbsp;'+val['model']+'('+val['reg_num']+')'}}</u></td>
                                                    <td class="text-right">&#x20B9;{{val['car_listing_price']}}</td>
                                                    <td class="text-right">
                                                    <div class="m-0 p-0" v-if="val['final_refurb_cost']">&#x20B9;{{val['final_refurb_cost']}}</div>
                                                    <div v-else class="m-0 p-0">&#x20B9; 0</div>
                                                    </td>
                                                    <td class="text-center">{{val['testdr_cnt']}}</td>
                                                    <td class="text-center">{{val['live_leads']}}</td>
                                                </tr>
                                            </thead>
                                        </table>
                                        <div v-if="live_stock.length==0" style="display: flex;align-items: center;justify-content: center;width: 100%;">No Data Found !!!</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
               </div>`
}