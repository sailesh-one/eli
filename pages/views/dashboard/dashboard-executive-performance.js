export default{
     name: 'DashboardExecutivePerformance',
     data(){
        return {
			global_error: "",
			global_token:"",
			api_calls_queue: [],
			api_call_busy: false,
			ep_data:{},
			evaluator_data:{},
			seller_data:{},
			enc_branch_id:"all",
			branches:{},
			user_rights:{},
			tab:"dashboard_executive_performance"
		}
     },
     template:`<div class="py-3 px-4 min-vh-100 bg-light">
                    <div class="container">
                        <h2 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1">Dashboard Executive Performance</h2>
                        <div class="dash-overview" style="background-color: rgb(247, 247, 248);">
                        <div class="col-12 py-2">
                            <dashboard_tabs :active_tab="tab" v-on:clicked="show_tab($event)"></dashboard_tabs>
                        </div>
                            <div class="col-sm-12 d-flex justify-content-end">
                                <div class="d-flex align-items-center">
                                    <div class="col"><b>Select Branch</b></div>
                                    <div class="col">
                                        <select class="form form-control form-control-sm" v-model="enc_branch_id" v-on:change="get_executiveperformance_data" style="min-width:200px;">
                                        <option value="all">All</option>
                                    </select>
                                    </div>
                                </div>
                            </div>
                        <div class="col-12 mt-3">
                            <div class="row">
                                <h6>Executive Performance (MTD)</h6>
                                <div class="card p-3">
                                <table class="table table-sm text-center table-bordered evenodd mb-0">
                                    <thead>
                                        <tr bgcolor="#ffffff">
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Sell Car Enquiries</th>
                                            <th>Evaluations Done</th>
                                            <th>Negotiations Done</th>
                                            <th>Trade-ins</th>
                                            <th>Certifications done</th>
                                            <th>Retail Enquiries</th>
                                            <th>Negotiations Done</th>
                                            <th>Trade-Outs</th>
                                        </tr>
                                    </thead>
                                    <tr v-for="v,i in ep_data"  class="oddcl">
                                        <td>{{v['emp_name']}}</td>
                                        <td>{{v['emp_role']}}</td>
                                        <td>{{v['mtd_counts']['mtd_eval_req']}}</td>
                                        <td>{{v['mtd_counts']['mtd_eval_done']}}</td>
                                        <td>{{v['mtd_counts']['mtd_neg_done']}}</td>
                                        <td>{{v['mtd_counts']['mtd_tradein_done']}}</td>
                                        <td>{{v['mtd_counts']['mtd_cert_done']}}</td>
                                        <td>{{v['mtd_counts']['mtd_sales_leads']}}</td>
                                        <td>{{v['mtd_counts']['mtd_sales_neg_done']}}</td>
                                        <td>{{v['mtd_counts']['mtd_trade_out']}}</td>
                                    </tr>
                                </table>
                                </div>
                                
                                <h6 class="mt-3">Executive Performance (YTD)</h6>
                                <div class="card p-3">
                                <table class="table table-sm text-center table-bordered  evenodd mb-0">
                                <thead>
                                    <tr bgcolor="#ffffff">
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Sell Car Enquiries</th>
                                        <th>Evaluations Done</th>
                                        <th>Negotiations Done</th>
                                        <th>Trade-ins</th>
                                        <th>Certifications done</th>
                                        <th>Retail Enquiries</th>
                                        <th>Negotiations Done</th>
                                        <th>Trade-Outs</th>
                                    </tr>
                                </thead>
                                    <tr v-for="v,i in ep_data" class="oddcl">
                                        <td>{{v['emp_name']}}</td>
                                        <td>{{v['emp_role']}}</td>
                                        <td>{{v['ytd_counts']['ytd_eval_req']}}</td>
                                        <td>{{v['ytd_counts']['ytd_eval_done']}}</td>
                                        <td>{{v['ytd_counts']['ytd_neg_done']}}</td>
                                        <td>{{v['ytd_counts']['ytd_tradein_done']}}</td>
                                        <td>{{v['ytd_counts']['ytd_cert_done']}}</td>
                                        <td>{{v['ytd_counts']['ytd_sales_leads']}}</td>
                                        <td>{{v['ytd_counts']['ytd_sales_neg_done']}}</td>
                                        <td>{{v['ytd_counts']['ytd_trade_out']}}</td>
                                    </tr>
                                </table>
                                </div>
                                
                            </div>
                            <div class="row" v-if="evaluator_data.length>0">
                            <div class="card p-3">
                                <table class="table tab-v-m" v-for="eval,i in evaluator_data">
                                    <thead>
                                        <tr>
                                            <td width="20%" valign="middle"><div><h5>{{eval['emp_name']}}</h5></div><div>{{eval['emp_role']}} </div></td>
                                            <td width="80%" valign="middle">
                                                <div class="d-flex justify-content-center enqass mt-3">
                                            
                                            
                                                <div class="font-md-txt">Enquiries Assigned 
                                                
                                                <div class="position-relative mx-3"><h5 style="position:absolute; top:-15px; left:0; right:0; margin:auto; text-align:center; ">{{eval['avg_cnt']['avg_eval_req']}} Days <small>(Avg)</small></h5><div class="mborder"></div><span class="font-lg"><img src="/images/arrow_left_icon.svg" width="25px"><img src="/images/arrow_right_icon.svg" width="25px" style="margin-left: auto;"></span> </div>
                                                
                                                Evaluations Done 
                                                
                                                <div class="position-relative mx-3"><h5 style="position:absolute; top:-15px; left:0; right:0; margin:auto; text-align:center; ">{{eval['avg_cnt']['avg_eval_trade_done']}} Days <small>(Avg)</small></h5><div class="mborder"></div><span class="font-lg"><img src="/images/arrow_left_icon.svg" width="25px"><img src="/images/arrow_right_icon.svg" width="25px" style="margin-left: auto;"></span></div> 
                                                
                                                Trade-Ins 
                                                
                                                <div class="position-relative mx-3"><h5 style="position:absolute; top:-15px; left:0; right:0; margin:auto; text-align:center; ">{{eval['avg_cnt']['avg_trad_in_ro']}} Days <small>(Avg)</small></h5><div class="mborder"></div><span class="font-lg"><img src="/images/arrow_left_icon.svg" width="25px"><img src="/images/arrow_right_icon.svg" width="25px" style="margin-left: auto;"></span> </div>
                                                
                                                
                                                Ready For Sale</div>
                                                
                                                </div>
                                            </td>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            </div>
                            <div class="row" v-if="seller_data.length>0">
                            <div class="card p-3">
                                <table class="table tab-v-m enqass mt-3" v-for="seller,i in seller_data">
                                    <thead>	
                                    <tr>
                                        <td width="20%" valign="middle"><div><h5>{{seller['emp_name']}}</h5></div><div>{{seller['emp_role']}} </div></td>
                                        <td width="70%" valign="middle">
                                            <div class="font-md-txt">Enquiries Assigned 
                                            <div class="position-relative mx-3"><h5 style="position:absolute; top:-15px; left:0; right:0; margin:auto; text-align:center; ">{{seller['avg_cnt']['avg_sale_enq_neg']}} Days <small>(Avg)</small></h5><div class="mborder"></div><span class="font-lg"><img src="/images/arrow_left_icon.svg" width="25px"><img src="/images/arrow_right_icon.svg" width="25px" style="margin-left: auto;"></span> </div>
                                            Negotiations Done 
                                        <div class="position-relative mx-3"><h5 style="position:absolute; top:-15px; left:0; right:0; margin:auto; text-align:center; ">{{seller['avg_cnt']['avg_sale_neg_tradeout']}} Days <small>(Avg)</small></h5><div class="mborder"></div><span class="font-lg"><img src="/images/arrow_left_icon.svg" width="25px"><img src="/images/arrow_right_icon.svg" width="25px" style="margin-left: auto;"></span> </div>
                                            Trade-Outs
                                            
                                            </div>
                                        </td>
                                        <td>&nbsp;</td>
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