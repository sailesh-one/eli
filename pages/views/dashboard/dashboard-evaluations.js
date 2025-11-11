export default {
    name: 'LayoutDashboardEvaluations',
    data(){
        return {
			global_error: "",
			global_token: "",
			api_calls_queue: [],
			api_call_busy: false,
			last7days: [],
			f_last7days: [],
			evaluators: {},
			branches: {},
			color: "#1da381",
			weekly_eval_counts: {},
			monthly_eval_counts: {},
			monthly_eval_done: {},
			leads_ratio: {
				lead_to_eval: Number(this.eval_data?.leads_ratio?.lead_to_eval || 0),
				eval_to_tradein: Number(this.eval_data?.leads_ratio?.eval_to_tradein || 0),
				lead_to_lost: Number(this.eval_data?.leads_ratio?.lead_to_lost || 0)
			},
			eval_data: {},
			graph_counts: {},
			eval_req_opt: [],
			eval_done_opt: [],
			tradein_cont_opt: [],
			tradein_opt: [],
			lost_leads_opt: [],
			search_form: {
				enc_branch_id: "all",
				enc_executive_id: "all"
			},
			percentage: 10,
			myChart2: null,
			user_rights:{},
			tab: "dashboard_evaluations",

		}
    },
    setup() {
       
    },
    watch(){
        
    },
    computed(){
       
    },
    mount(){
    },
    methods:{
    },
    template: `
        <div class="py-3 px-4 min-vh-100 bg-light">
            <div class="container">
                <h2 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1">Dashboard Evaluations</h2>
           
            <div class="dash-overview" style="background-color: #f7f7f8;">
	<div class="col-12 py-2">
		<dashboard_tabs :active_tab="tab" v-on:clicked="show_tab($event)"></dashboard_tabs>
	</div>
	<div class="col-12 mt-3">
		<div class="row">
			<div class="col-sm-12 d-flex justify-content-end">
				<div class="d-flex align-items-center">
					<div class="col">
						<label class="mb-0">Select&nbsp;Branch&nbsp;:</label>
					</div>
					<div class="col">
						<select class="form form-control form-control-sm" v-model="search_form['enc_branch_id']" v-on:change="get_executives_list" style="min-width:200px;">
							<option value="all">All</option>
							<option v-for="v,i in branches" v-bind:value="v['enc_branch_id']">{{v['brn_name']}}</option>
						</select>
					</div>
				</div>
				<div class="d-flex align-items-center">
					<div class="col">
						<label class="mb-0">Select&nbsp;Evaluator&nbsp;:</label>
					</div>
					<div class="col">
						<select class="form form-control form-control-sm" v-model="search_form['enc_executive_id']" v-on:change="get_evaluation_data" style="min-width:200px;">
							<option value="all">All</option>
							<option v-for="v,i in evaluators" v-bind:value="v['enc_executive_id']">{{v['emp_name']}}</option>
						</select>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-9">
				<div class="row">
					<div class="card p-3">
						<table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
							<thead>
								<tr>
									<th bgcolor="#fff" rowspan="2" class="align-middle">Status</th>
									<th colspan="7" bgcolor="#fff">Weekly Count</th>
									<th bgcolor="#fff" rowspan="2" class="align-middle">MTD Total</th>
								</tr>
								<tr bgcolor="#fff">
									<td v-for="date in f_last7days">{{date}}</td>
								</tr>
								<tr bgcolor="#f2f2f2">
									<th>Evaluations Required</th>
									<td v-for="date in last7days">{{weekly_eval_counts['weekly_eval_req'][date]}}</td>
									<td>{{ monthly_eval_counts.total?.weekly_eval_req }}</td>
								</tr>
								<tr bgcolor="#ffffff">
									<th>Evaluations Done</th>
									<td v-for="date in last7days">{{weekly_eval_counts['weekly_eval_done'][date]}}</td>
									<td>{{ monthly_eval_counts.total?.monthly_eval_done }}</td>

								</tr>
								<tr bgcolor="#f2f2f2">
									<th>Deals Done</th>
									<td v-for="date in last7days">{{weekly_eval_counts['weekly_tradeIn_contacts'][date]}}</td>
									<!-- <td>{{monthly_eval_counts['total']['monthly_tradeIn_contacts']}} </td> -->
									<td>{{ monthly_eval_counts.total?.monthly_tradeIn_contacts }}</td>
								</tr>
								<tr bgcolor="#ffffff">
									<th>Possession Done</th>
									<td v-for="date in last7days">{{weekly_eval_counts['weekly_trade_ins'][date]}}</td>
									<!-- <td>{{monthly_eval_counts['total']['monthly_trade_ins']}} </td> -->
									<td>{{ monthly_eval_counts.total?.monthly_trade_ins }}</td>
								</tr>
								<tr bgcolor="#f2f2f2">
									<th>Lost Leads</th>
									<td v-for="date in last7days">{{weekly_eval_counts['weekly_lost_leads'][date]}}</td>
									<!-- <td>{{monthly_eval_counts['total']['monthly_lost_leads']}} </td> -->
									<td>{{ monthly_eval_counts.total?.monthly_lost_leads }}</td>
								</tr>
							</thead>
						</table>
					</div>
				</div>
				<div class="row">
					<div class="card px-3 pt-3">
						<div id="chart">
							<div style="float:left; width:100%; padding-bottom:15px;" >
								<canvas id="myChart2" width="1200" height="360" style="width:100%; height:360px;" ></canvas>
							</div>
						</div>
					</div>
				</div>
				<div class="row mt-2">
					<div class="col-sm-6 pl-0">
						<div class="card p-3">
							<table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
								<thead>
									<tr>
										<th bgcolor="#fff" rowspan="2" class="align-middle">Status</th>
										<th colspan="8" bgcolor="#fff">MTD Total</th>
									</tr>
									<tr bgcolor="#fff">
										<th>MG</th>
										<th>Non-MG</th>
									</tr>
									<tr bgcolor="#f2f2f2">
										<th>Evaluations Required</th>
										<td>{{monthly_eval_counts.mg?.monthly_eval_req}} </td>
										<td>{{monthly_eval_counts.non_mg?.monthly_eval_req}} </td>
			
									</tr>
									<tr bgcolor="#ffffff">
										<th>Evaluations Done</th>
										<!-- <td>{{monthly_eval_counts['mg']['monthly_eval_done']}} </td> -->
										<td>{{monthly_eval_counts.mg?.monthly_eval_done}} </td>
										<!-- <td>{{monthly_eval_counts['non_mg']['monthly_eval_done']}} </td> -->
										<td>{{monthly_eval_counts.non_mg?.monthly_eval_done}} </td>
									</tr>
									<tr bgcolor="#f2f2f2">
										<th>Deals Done</th>
										<td>{{monthly_eval_counts.mg?.monthly_tradeIn_contacts}} </td>
										<td>{{monthly_eval_counts.non_mg?.monthly_tradeIn_contacts}} </td>
									</tr>
									<tr bgcolor="#ffffff">
										<th>Possession Done</th>
										<td>{{monthly_eval_counts.mg?.monthly_trade_ins}} </td>
										<td>{{monthly_eval_counts.non_mg?.monthly_trade_ins}} </td>
									</tr>
									<tr bgcolor="#f2f2f2">
										<th>Lost Leads</th>
										<td>{{monthly_eval_counts.mg?.monthly_lost_leads}} </td>
										<td>{{monthly_eval_counts.non_mg?.monthly_lost_leads}} </td>
									</tr>
								</thead>
							</table>
						</div>
					</div>
					<div class="col-sm-6 pr-0">
						<div class="card p-3">
							<table width="100%" border="0" cellspacing="1" cellpadding="8" bgcolor="#333333" class="table table-sm align-middle text-center table-bordered p-3 mb-0">
								<thead>
									<tr>
										<th bgcolor="#fff" rowspan="2" class="align-middle">Evaluations Done</th>
										<th colspan="8" bgcolor="#fff">MTD Total</th>
									</tr>
									<tr bgcolor="#fff">
										<th>MG</th>
										<th>Non-MG</th>
									</tr>
									<tr bgcolor="#f2f2f2">
										<th>At Showroom</th>
										<td>{{monthly_eval_done['mg_showroom']}} </td>
										<td>{{monthly_eval_done['nonmg_showroom']}} </td>
									</tr>
									<tr bgcolor="#ffffff">
										<th>On Field</th>
										<td>{{monthly_eval_done['mg_field']}} </td>
										<td>{{monthly_eval_done['nonmg_field']}} </td>
									</tr>
								</thead>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="col-sm-3">

				<div class="card p-3">
					<h6 CLASS="mb-2">YTD ANALYSIS</h6>
					<div class="d-flex justify-content-center" style="margin:auto;text-align: center;">
						<circle-progress v-if="leads_ratio && typeof leads_ratio.lead_to_eval === 'number'" :percent="leads_ratio['lead_to_eval']" :dynamicColor="color" :staticWidth="12" :dynamicWidth="12"  :size="150" dashboard>
						<p><span>{{leads_ratio['lead_to_eval']}}</span> %</p>
						</circle-progress>
					</div>
					<div class="d-flex justify-content-center" style="color: #cf2230; font-weight:600; margin: 3px 20px 0 20px;padding: 4px 12px;text-align:center;">Lead-To-Evaluations Ratio</div>
					<div class="d-flex justify-content-center" style="margin-top:25px;text-align: center;">
						<circle-progress v-if="leads_ratio && typeof leads_ratio.lead_to_eval === 'number'" :percent="leads_ratio['eval_to_tradein']" :dynamicColor="color" :staticWidth="12" :dynamicWidth="12" :size="150" dashboard>
						<p><span>{{leads_ratio['eval_to_tradein']}}</span> %</p>
						</circle-progress>
					</div>
					<div class="d-flex justify-content-center" style="color: #cf2230; font-weight:600;margin: 3px 20px 0 20px;padding: 4px 12px;text-align:center;">Evaluations-To-Tradein Ratio</div>
					<div class="d-flex justify-content-center" style="margin-top:25px;text-align: center;">
						<circle-progress v-if="leads_ratio && typeof leads_ratio.lead_to_eval === 'number'" :percent="leads_ratio['lead_to_lost']" :dynamicColor="color" :staticWidth="12" :dynamicWidth="12" :size="150" dashboard>
						<p><span>{{leads_ratio['lead_to_lost']}}</span> %</p>
						</circle-progress>
					</div>
					<div class="d-flex justify-content-center" style="color: #cf2230; font-weight:600;margin: 3px 20px 0 20px;padding: 4px 12px;text-align:center;">Leads-To-Lost Ratio</div>
				</div>
			</div>
		</div>
	</div>
</div>
 </div>
</div>
        `
};