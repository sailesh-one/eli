const [
  {
    loadChartJS,
    baseChartOptions,
    createBarChart,
    createLineChart,
    createDoughnutChart,
    createPieChart
  }
] = await $importComponent(['/pages/lib/charts.js']);

export default {
  name: 'LayoutDashboard',
  data() {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const formatDate = (d) => d.toISOString().slice(0, 10); // YYYY-MM-DD

    return {
      chartInstances: [],
      isDestroyed: false,
      evalBarChart: null,
      activeEvalTab: 'evaluation', 
      resData: {},
      ctx: null,
      fromDate: formatDate(firstDay),
      toDate: formatDate(today)
    };
  },

  async mounted() {
    if (!this.isDestroyed) await loadChartJS();
    this.get_data();
  },

  methods: {
    async request(action, data = {}) {
      try {
        const res = await $http(
          'POST',
          `${g.$base_url_api}/admin/dashboard`,
          { action, ...data },
          {}
        );
        return res;
      } catch (e) {
        return { status: e.status, body: e.body };
      }
    },

    async get_data() {
      const res = await this.request('get', { from_date: this.fromDate, to_date: this.toDate });
      if (res.body.status === 'ok') {
        const data = res.body.data || {};
        this.resData = data; // store globally
        console.log(this.resData);

        const ctColors = {
          chartColors: ['#002C3F', '#007A87', '#93A8B3', '#D6DEE5', '#F5A623', '#D0021B']
        };

        // Canvas context helper
        const ctx = (ref) => this.$refs[ref] && this.$refs[ref].getContext ? this.$refs[ref].getContext('2d') : null;
        this.ctx = ctx;

        // Helper to build Pie/Doughnut data
        const buildChartData = (arr, labelKey, valueKey) => (Array.isArray(arr) ? {
          labels: arr.map(item => item[labelKey]),
          datasets: [{
            label: 'Leads',
            data: arr.map(item => Number(item[valueKey])),
            backgroundColor: ctColors.chartColors,
            borderColor: '#ffffff',
            borderWidth: 2,
            hoverOffset: 10
          }]
        } : { labels: [], datasets: [] });
        

        const pmSourcesData = buildChartData(data.pm_sources, 'source_name', 'total');
        const smSourcesData = buildChartData(data.sm_sources, 'source_name', 'total');
        const pmStatusData  = buildChartData(data.pm_statuses, 'status_name', 'total');
        const smStatusData  = buildChartData(data.sm_statuses, 'status_name', 'total');

        // Destroy old charts
        this.destroyCharts();

        // Pie/Doughnut options: no axes
        const noAxisOptions = { plugins: { scales: { x: { display: false }, y: { display: false } } } };

        // Create charts
        this.chartInstances = [
          createPieChart(ctx('pmSourcesPie'), pmSourcesData, noAxisOptions),
          createPieChart(ctx('smSourcesPie'), smSourcesData, noAxisOptions),
          createDoughnutChart(ctx('pmStatusDoughnut'), pmStatusData, noAxisOptions),
          createDoughnutChart(ctx('smStatusDoughnut'), smStatusData, noAxisOptions)
        ];

         this.switchtab("evaluation");
      }
    },

    switchtab(type) {
      this.activeEvalTab = type;

      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      const targets = [];
      const achieved = [];

      for (let i = 1; i <= 12; i++) {
        if (type === 'evaluation') {
          targets.push(this.resData.kra_targets?.[i]?.evaluation || 0);
          achieved.push(this.resData.eval_data?.[i] || 0);
        } else if (type === 'sales') {
          targets.push(this.resData.kra_targets?.[i]?.sales || 0);
          achieved.push(this.resData.sold_data?.[i] || 0);
        } else if (type === 'purchase') {
          targets.push(this.resData.kra_targets?.[i]?.purchase || 0);
          achieved.push(this.resData.purchase_data?.[i] || 0);
        }
      }

      const barData = {
        labels: months,
        datasets: [
          { label: 'Target', data: targets, backgroundColor: '#002C3F', borderRadius: 6 },
          { label: 'Achievements', data: achieved, backgroundColor: '#007A87', borderRadius: 6 }
        ]
      };

      const barOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { color: '#6A7881' } },
          datalabels: { anchor: 'end', align: 'end', color: '#000', font: { weight: '600' }, formatter: v => v }
        },
        scales: { x: { ticks: { color: '#6A7881' } }, y: { display: false } }
      };

      if (this.evalBarChart) {
        this.evalBarChart.destroy();
        this.evalBarChart = null;
      }

      this.evalBarChart = createBarChart(this.ctx('evalBarChart'), barData, barOptions);
    },

    destroyCharts() {
      this.chartInstances.forEach(chart => { if (chart?.destroy) chart.destroy(); });
      if (this.evalBarChart) { this.evalBarChart.destroy(); this.evalBarChart = null; }
      this.chartInstances = [];
    }
  },

  template: /*html*/ `
<div class="py-3 px-4 min-vh-100 bg-light">
  <div class="container">
    <h2 class="fw-light mb-2 text-secondary">Dashboard Overview</h2>

    <!-- Metrics cards -->
    <div class="row g-4 mb-5">
      <div class="col-sm-6 col-md-3" v-for="card in 4" :key="card">
        <div class="card border-0 rounded-2 p-4 text-center shadow-sm">
          <small class="text-uppercase fw-bold text-secondary">Metric {{ card }}</small>
          <h3 class="fw-bold mt-2 mb-0 text-dark">{{ 1000 * card }}</h3>
        </div>
      </div>
    </div>

    <!-- Date Range Filter -->
    <!-- <div class="row g-3 mb-5 align-items-end">
      <div class="col-auto">
        <label class="form-label fw-bold">From Date</label>
        <input type="date" class="form-control" v-model="fromDate">
      </div>
      <div class="col-auto">
        <label class="form-label fw-bold">To Date</label>
        <input type="date" class="form-control" v-model="toDate">
      </div>
      <div class="col-auto">
        <button class="btn btn-primary" @click="get_data">Apply</button>
      </div>
    </div>  -->


    <!-- Evaluation / Sales / Purchases Bar Chart -->
    <div class="col-12 mb-5">
      <div class="card border-0 rounded-3 p-4">
        <div class="d-flex gap-2 mb-3">
          <button 
            class="btn flex-fill"
            :class="{'btn-dark text-white': activeEvalTab==='evaluation', 'btn-light text-dark': activeEvalTab!=='evaluation'}"
            @click="switchtab('evaluation')">
            Evaluation
          </button>
          <button 
            class="btn flex-fill"
            :class="{'btn-dark text-white': activeEvalTab==='purchase', 'btn-light text-dark': activeEvalTab!=='purchase'}"
            @click="switchtab('purchase')">
            Purchase
          </button>
          <button 
            class="btn flex-fill"
            :class="{'btn-dark text-white': activeEvalTab==='sales', 'btn-light text-dark': activeEvalTab!=='sales'}"
            @click="switchtab('sales')">
            Sales
          </button>
        </div>
        <div style="height: 400px; overflow-x: auto;">
          <canvas ref="evalBarChart" style="height: 100%; min-width: 700px;"></canvas>
        </div>
      </div>
    </div>

     <!-- Sources Pie & Status Doughnut Charts -->
<!-- Sources Pie & Status Doughnut Charts -->
<div class="row g-4 mt-4">

  <div class="col-md-6">
    <div class="card border-0 rounded-3 p-4 text-center chart-card">
      <h5 class="my-3 text-secondary">Sources Wise Purchase</h5>
      <canvas ref="pmSourcesPie"></canvas>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0 rounded-3 p-4 text-center chart-card">
      <h5 class="my-3 text-secondary">Sources Wise Sales</h5>
      <canvas ref="smSourcesPie"></canvas>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0 rounded-3 p-4 text-center chart-card">
      <h5 class="my-3 text-secondary">Status Wise Purchase</h5>
      <canvas ref="pmStatusDoughnut"></canvas>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0 rounded-3 p-4 text-center chart-card">
      <h5 class="my-3 text-secondary">Status Wise Sales</h5>
      <canvas ref="smStatusDoughnut"></canvas>
    </div>
  </div>

</div>


  </div>
</div>
`
};
