const [{ useStoreDashboard }] = await $importComponent([
  '/pages/stores/store_dashboard.js',
]);
export const DashboardOverview = {
    name: 'DashboardOverview',
     props: {
    },
    data(){
        return {
            global_error: "",
            global_token:"",
            api_calls_queue: [],
            api_call_busy: false,
            today_overview_data:{},
            user_rights:{},
            ytd_data:{},
            mtd_data:{},
            ytd_costs_data:{},
            mtd_costs_data:{},
            tat_data:{},
            //Jlr
            jlr_today_overview_data:{},
            jlr_ytd_data:{},
            jlr_mtd_data:{},
            jlr_ytd_costs_data:{},
            jlr_mtd_costs_data:{},
            jlr_tat_data:{},
            //Non Jlr
            njlr_today_overview_data:{},
            njlr_ytd_data:{},
            njlr_mtd_data:{},
            njlr_ytd_costs_data:{},
            njlr_mtd_costs_data:{},
            njlr_tat_data:{},
            dealer_branches:[],
            lead_analysis_data:[],
            dashboard_data:{},
            ov:{
              "branch_id":"0",
              "car_ty":"total"
            },
		    }
    },

  setup() {
    const dashboardStore = useStoreDashboard();
    return { dashboardStore };
  },
   watch(){
        
    },
    computed:{
        dashboard_conf: vm => vm.dashboardStore || {},
    },
    mount(){
     
    },
    methods:{
        chg_branch:function(){
            this.ov['branch_id']=this.ov['branch_id'];
            this.ov['car_ty']=this.ov['car_ty'];
        },
        show_tab:function(){
            
        }
    },
    template: `<div class="py-3 px-4 min-vh-100 bg-light">
  <div class="container">\
  <h2 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['m_title']}}</h2>
  <div class="dash-overview" style="background-color: #f7f7f8;">
    <div class="col-12 py-2">
      <dashboard_tabs :active_tab="tab" v-on:clicked="show_tab($event)"></dashboard_tabs>
    </div>
    <div class="row px-4 mt-3 align-items-center">
      <div class="col-6">
        <h6 class="text-center ml-3"> {{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-1']['title']}}</h6>
      </div>
      <div class="col-6 float-right">
        <div style="margin-left:auto; width:290px;" class="d-flex">
          <label class="mb-0" style="line-height:37px; margin-right:6px;">
            <strong>Select&nbsp;Branch:</strong>
          </label>
          <select v-model="ov['branch_id']" v-on:change="chg_branch" class="form-control form-control-sm" style="min-width: 200px;">
            <option value="0">All</option>
          </select>&nbsp;
        </div>
      </div>
    </div>
    <div class="col-12 mt-2 clearfix">
      <div class="row p-0 m-0">
        <div class="col-sm-6 mt-0 p-0">
          <div class="row mt-0 pl-0">
            <div class="col-sm-4 text-center">
              <div class="card">
                <div class="count1">0{{today_overview_data['today_eval_req']}}</div>
                <div>
                  <span>JLR-{{jlr_today_overview_data['today_eval_req']}}</span>&nbsp;, <span>NON JLR-{{njlr_today_overview_data['today_eval_req']}}</span>
                </div>
                <div>
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-1']['data']['ele-0']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="col-sm-4 text-center">
              <div class="card">
                <div class="count1">0{{today_overview_data['today_eval_done']}}</div>
                <div>
                  <span>JLR-0{{jlr_today_overview_data['today_eval_done']}}</span>&nbsp;, <span>NON JLR-0{{njlr_today_overview_data['today_eval_done']}}</span>
                </div>
                <div>
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-1']['data']['ele-1']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="col-sm-4 text-center">
              <div class="card">
                <div class="count1">0{{today_overview_data['today_tradein_done']}}</div>
                <div>
                  <span>JLR-{{jlr_today_overview_data['today_tradein_done']}}</span>&nbsp;, <span>NON JLR-{{njlr_today_overview_data['today_tradein_done']}}</span>
                </div>
                <div>
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-1']['data']['ele-2']['sub_title']}}</strong>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-0 justify-content-center">
            <div class="col-sm-4 text-center">
              <div class="card">
                <div class="count1">0{{today_overview_data['today_sales_leads']}}</div>
                <div>
                  <span>JLR-{{jlr_today_overview_data['today_sales_leads']}}</span>&nbsp;, <span>NON JLR-{{njlr_today_overview_data['today_sales_leads']}}</span>
                </div>
                <div>
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-1']['data']['ele-3']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="col-sm-4 text-center">
              <div class="card">
                <div class="count1">0{{today_overview_data['today_trade_out']}}</div>
                <div>
                  <span>JLR-{{jlr_today_overview_data['today_trade_out']}}</span>&nbsp;, <span>NON JLR-{{njlr_today_overview_data['today_trade_out']}}</span>
                </div>
                <div>
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-1']['data']['ele-4']['sub_title']}}</strong>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-0 mx-3">
            <div class="card p-3 tatreports">
              <h6 class="text-center"> TAT Reports </h6>
              <div class="row p-2 text-center mt-3">
                <div class="col-sm-6 text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <h5>0{{tat_data['tat_trade_refu']}} Days</h5>
                    <sub>(Avg)</sub>
                  </div>
                  <!--div class="d-flex align-items-center justify-content-center"><span>0{{jlr_tat_data['tat_trade_refu']}}(JLR)</span>/<span>0{{njlr_tat_data['tat_trade_refu']}}(Non JLR)  Days</span><small>(Avg)</small></div-->
                  <div class="d-flex align-items-center justify-content-center my-3">
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-0']['images']['image-1']" width="45px" />
                    <span style="font-size:25px; " class="px-3">&#8594;</span>
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-0']['images']['image-2']" width="45px" />
                  </div>
                  <div class="text-md">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-0']['sub_title']}}</div>
                </div>
                <div class="col-sm-6 text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <h5>0{{tat_data['tat_eval_trade_done']}} Days</h5>
                    <sub>(Avg)</sub>
                  </div>
                  <!--div class="d-flex align-items-center justify-content-center"><span>0{{jlr_tat_data['tat_eval_trade_done']}}(JLR)</span>/<span>0{{njlr_tat_data['tat_eval_trade_done']}}(Non JLR)  Days</span><small>(Avg)</small></div-->
                  <div class="d-flex align-items-center justify-content-center my-3">
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-1']['images']['image-1']" width="45px" />
                    <span style="font-size:25px; " class="px-3">&#8594;</span>
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-1']['images']['image-2']" width="45px" />
                  </div>
                  <div class="text-md">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-1']['sub_title']}}</div>
                </div>
              </div>
              <div class="row p-2 mt-5">
                <div class="col-sm-6 text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <h5>0{{tat_data['tat_trad_in_ro']}} Days</h5>
                    <sub>(Avg)</sub>
                  </div>
                  <!--div class="d-flex align-items-center justify-content-center"><span>0{{jlr_tat_data['tat_trad_in_ro']}}(JLR)</span>/<span>0{{jlr_tat_data['tat_trad_in_ro']}}(Non JLR)  Days</span><small>(Avg)</small></div-->
                  <div class="d-flex align-items-center justify-content-center my-3">
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-2']['images']['image-1']" width="45px" />
                    <span style="font-size:25px; " class="px-3">&#8594;</span>
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-2']['images']['image-2']" width="45px" />
                  </div>
                  <div class="text-md">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-2']['sub_title']}}</div>
                </div>
                <div class="col-sm-6 text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <h5>0{{tat_data['tat_ro_to_booking']}} Days</h5>
                    <sub>(Avg)</sub>
                  </div>
                  <!--div class="d-flex align-items-center justify-content-center"><span>0{{jlr_tat_data['tat_ro_to_booking']}}(JLR)</span>/<span>0{{njlr_tat_data['tat_ro_to_booking']}}(Non JLR)  Days</span><small>(Avg)</small></div-->
                  <div class="d-flex align-items-center justify-content-center my-3">
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-3']['images']['image-1']" width="45px" />
                    <span style="font-size:25px; " class="px-3">&#8594;</span>
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-3']['images']['image-2']" width="45px" />
                  </div>
                  <div class="text-md">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-3']['sub_title']}}</div>
                </div>
              </div>
              <div class="row p-2 mt-5">
                <div class="col-sm-6 text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <h5>0{{tat_data['tat_booking_to_dvry']}} Days</h5>
                    <sub>(Avg)</sub>
                  </div>
                  <!--div class="d-flex align-items-center justify-content-center"><span>0{{jlr_tat_data['tat_booking_to_dvry']}}(JLR)</span>/<span>0{{njlr_tat_data['tat_booking_to_dvry']}}(Non JLR)  Days</span><small>(Avg)</small></div-->
                  <div class="d-flex align-items-center justify-content-center my-3">
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-4']['images']['image-1']" width="45px" />
                    <span style="font-size:25px; " class="px-3">&#8594;</span>
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-4']['images']['image-2']" width="45px" />
                  </div>
                  <div class="text-md">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-4']['sub_title']}}</div>
                </div>
                <div class="col-sm-6 text-center">
                  <div class="d-flex align-items-center justify-content-center">
                    <h5>0{{tat_data['tat_delivery_to_rc']}} Days</h5>
                    <sub>(Avg)</sub>
                  </div>
                  <!--div class="d-flex align-items-center justify-content-center"><span>0{{jlr_tat_data['tat_delivery_to_rc']}}(JLR)</span>/<span>0{{njlr_tat_data['tat_delivery_to_rc']}}(Non JLR)  Days</span><small>(Avg)</small></div-->
                  <div class="d-flex align-items-center justify-content-center my-3">
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-5']['images']['image-1']" width="45px" />
                    <span style="font-size:25px; " class="px-3">&#8594;</span>
                    <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-5']['images']['image-2']" width="45px" />
                  </div>
                  <div class="text-md">{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['left-block-2']['data']['ele-5']['sub_title']}}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 mt-0">
          <div class="card px-2 py-4">
            <div class="row p-2 border-bottom">
              <div class="col-sm-3"> &nbsp; </div>
              <div class="col-sm-4">
                <h6>MTD</h6>
              </div>
              <div class="col-sm-4">
                <h6>YTD</h6>
              </div>
            </div>
            <div class="row p-2 border-bottom">
              <div class="col-sm-3">
                <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-0']['images']['image-1']" />
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{mtd_data['mtd_eval_req']}}</div>
                <div>
                  <span>JLR-0{{jlr_mtd_data['mtd_eval_req']}}</span>&nbsp;, <span>NON JLR-0{{njlr_mtd_data['mtd_eval_req']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-0']['sub_title']}}</strong>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{ytd_data['ytd_eval_req']}}</div>
                <div>
                  <span>JLR-0{{jlr_ytd_data['ytd_eval_req']}}</span>&nbsp;, <span>NON JLR-0{{njlr_ytd_data['ytd_eval_req']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data1']['ele-0']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="row p-2 border-bottom">
              <div class="col-sm-3">
                <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-1']['images']['image-1']" />
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{mtd_data['mtd_eval_done']}}</div>
                <div>
                  <span>JLR-0{{jlr_mtd_data['mtd_eval_done']}}</span>&nbsp;, <span>NON JLR-0{{njlr_mtd_data['mtd_eval_done']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-1']['sub_title']}}</strong>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{ytd_data['ytd_eval_done']}}</div>
                <div>
                  <span>JLR-0{{jlr_ytd_data['ytd_eval_done']}}</span>&nbsp;, <span>NON JLR-0{{njlr_ytd_data['ytd_eval_done']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data1']['ele-1']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="row p-2 border-bottom">
              <div class="col-sm-3">
                <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-2']['images']['image-1']" />
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{mtd_data['mtd_tradein_done']}}</div>
                <div>
                  <span>JLR-0{{jlr_mtd_data['mtd_tradein_done']}}</span>&nbsp;, <span>NON JLR-0{{njlr_mtd_data['mtd_tradein_done']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-2']['sub_title']}}</strong>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{ytd_data['ytd_tradein_done']}}</div>
                <div>
                  <span>0{{jlr_ytd_data['ytd_tradein_done']}}(JLR)</span>&nbsp;, <span>0{{njlr_ytd_data['ytd_tradein_done']}}(Non JLR)</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data1']['ele-2']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="row p-2 border-bottom">
              <div class="col-sm-3">
                <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-3']['images']['image-1']" />
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{mtd_costs_data['mtd_sales_leads']}}</div>
                <div>
                  <span>JLR-0{{jlr_mtd_costs_data['mtd_sales_leads']}}</span>&nbsp;, <span>NON JLR-0{{njlr_mtd_costs_data['mtd_sales_leads']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-3']['sub_title']}}</strong>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{ytd_costs_data['ytd_sales_leads']}}</div>
                <div>
                  <span>JLR-0{{jlr_ytd_costs_data['ytd_sales_leads']}}</span>&nbsp;, <span>NON JLR-0{{njlr_ytd_costs_data['ytd_sales_leads']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data1']['ele-3']['sub_title']}}</strong>
                </div>
              </div>
            </div>
            <div class="row p-2 border-bottom">
              <div class="col-sm-3">
                <img :src="dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-4']['images']['image-1']" />
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{mtd_costs_data['mtd_trade_out']}}</div>
                <div>
                  <span>JLR-0{{jlr_mtd_costs_data['mtd_trade_out']}}</span>&nbsp;, <span>NON JLR-0{{njlr_mtd_costs_data['mtd_trade_out']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data']['ele-4']['sub_title']}}</strong>
                </div>
              </div>
              <div class="col-sm-4">
                <div class="count1">0{{ytd_costs_data['ytd_trade_out']}}</div>
                <div>
                  <span>JLR-0{{jlr_ytd_costs_data['ytd_trade_out']}}</span>&nbsp;, <span>NON JLR-0{{njlr_ytd_costs_data['ytd_trade_out']}}</span>
                </div>
                <div class="text-sm">
                  <strong>{{dashboard_conf['dashboard_template']['dashboard-elements']['over-view']['section']['right-block-1']['data1']['ele-4']['sub_title']}}</strong>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-4">
            <div class="card p-3">
              <table class="table table-md text-lg " width="100%">
                <thead>
                  <tr>
                    <th style="border-top: 1px solid transparent;"></th>
                    <th style="border-top: 1px solid transparent;">
                      <h6>MTD</h6>
                    </th>
                    <th style="border-top: 1px solid transparent;">
                      <h6>YTD</h6>
                    </th>
                  </tr>
                  <tr>
                    <td class="t-titles">Trade-Ins</td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius:3px 0px 0px 3px; font-size: 18px;" v-if="mtd_costs_data['mtd_tradeins_sum']!='NaN'">&#x20B9; {{mtd_costs_data['mtd_tradeins_sum']}}0 </span>
                    </td>
                    <td>
                      <span style="padding: 2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="ytd_data['ytd_tradeins_sum']!='NaN'">&#x20B9; {{ytd_data['ytd_tradeins_sum']}}0</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="t-titles">ROs Generated</td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="mtd_costs_data['mtd_refurb_sum']!='NaN'">&#x20B9; {{mtd_costs_data['mtd_refurb_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="ytd_costs_data['ytd_refurb_sum']!='NaN'">&#x20B9; {{ytd_costs_data['ytd_refurb_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="t-titles">Sales</td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="mtd_data['mtd_sales_sum']!='NaN'">&#x20B9; {{mtd_data['mtd_sales_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="ytd_costs_data['ytd_sales_sum']!='NaN'">&#x20B9; {{ytd_costs_data['ytd_sales_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="t-titles">Gross Profit</td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="mtd_costs_data['m_grs_prf_sum']!='NaN'">&#x20B9; {{mtd_costs_data['m_grs_prf_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="ytd_data['y_grs_prf_sum']!='NaN'">&#x20B9; {{ytd_costs_data['y_grs_prf_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="t-titles">Net Profit</td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="mtd_costs_data['m_grs_prf_sum']!='NaN'">&#x20B9; {{mtd_costs_data['m_net_prf_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                    <td>
                      <span style="padding:2px 8px 2px 0; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-if="ytd_data['y_net_prf_sum']!='NaN'">&#x20B9; {{ytd_costs_data['y_net_prf_sum']}}0</span>
                      <span style="padding:2px 8px; border-radius: 3px 0px 0px 3px; font-size: 18px;" v-else>&#x20B9; 0</span>
                    </td>
                  </tr>
                </thead>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-2"></div>
    </div>
  </div>
</div>
</div>`
};