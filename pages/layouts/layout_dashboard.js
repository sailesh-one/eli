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
    const formatDate = (d) => d.toISOString().slice(0, 10);

    return {
      chartInstances: [],
      isDestroyed: false,
      evalBarChart: null,
      activeEvalTab: 'evaluation',
      resData: {},
      ctx: null,
      fromDate: formatDate(firstDay),
      toDate: formatDate(today),
      isSwitchingTab: false,
      pendingAnimationFrames: new Set(), // Track all animation frames
      chartRenderKey: 0, // Force canvas recreation
    };
  },

  async mounted() {
    if (!this.isDestroyed) {
      try {
        await loadChartJS();
        console.log('Chart.js loaded:', !!window.Chart);
      } catch (e) {
        console.error('Failed to load Chart.js:', e);
      }
      await this.get_data();
    }
  },

  methods: {
    async request(action, data = {}) {
      try {
        const res = await $http(
          'POST',
          `${g.$base_url_api}/dashboard`,
          { action, ...data },
          {}
        );
        return res;
      } catch (e) {
        console.error('Request failed:', e);
        return { status: e.status, body: e.body };
      }
    },

    // Enhanced canvas context retrieval with retry logic
    getCanvasContext(ref, retries = 3) {
      const canvas = this.$refs[ref];
      
      if (!canvas) {
        console.warn(`Canvas ref "${ref}" not found in DOM.`);
        return null;
      }
      
      if (!(canvas instanceof HTMLCanvasElement)) {
        console.warn(`Ref "${ref}" is not a valid canvas element.`);
        return null;
      }
      
      try {
        const context = canvas.getContext('2d', { willReadFrequently: false });
        
        if (!context) {
          if (retries > 0) {
            console.log(`Retrying context retrieval for "${ref}"...`);
            return this.getCanvasContext(ref, retries - 1);
          }
          console.warn(`Failed to get 2D context for canvas ref "${ref}".`);
          return null;
        }
        
        if (typeof context.save !== 'function' || typeof context.restore !== 'function') {
          console.warn(`Context for "${ref}" lacks required methods.`);
          return null;
        }
        
        return context;
      } catch (e) {
        console.error(`Error accessing context for canvas ref "${ref}":`, e);
        return null;
      }
    },

    // Completely destroy chart and wait for cleanup
    async safeDestroyChart(chart, chartName = 'chart') {
      if (!chart) return;

      return new Promise((resolve) => {
        try {
          // Stop all animations immediately
          if (typeof chart.stop === 'function') {
            chart.stop();
          }

          // Mark canvas as being destroyed to prevent further operations
          if (chart.canvas) {
            chart.canvas.dataset.destroying = 'true';
          }

          // Get reference to canvas before destroying
          const canvas = chart.canvas;

          // Destroy the chart
          chart.destroy();
          console.log(`Destroyed ${chartName}.`);

          // Clear the canvas manually as an extra safeguard
          if (canvas && canvas.getContext) {
            try {
              const ctx = canvas.getContext('2d');
              if (ctx) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
              }
            } catch (e) {
              // Ignore errors during cleanup
            }
          }

          // Wait for next frame to ensure cleanup is complete
          requestAnimationFrame(() => {
            requestAnimationFrame(() => {
              resolve();
            });
          });
        } catch (e) {
          console.warn(`Error destroying ${chartName}:`, e);
          resolve(); // Resolve anyway to not block execution
        }
      });
    },

    async get_data() {
      const res = await this.request('get', { from_date: this.fromDate, to_date: this.toDate });
      if (res.body.status !== 'ok') {
        console.error('Failed to fetch data:', res.body);
        return;
      }

      const data = res.body.data || {};
      this.resData = data;
      console.log('Fetched data:', this.resData);

      const ctColors = {
        chartColors: [
          '#0A0F1A', '#E5E7EB', '#526075', '#2C5E7F', '#4781A8', 
          '#68A3D4', '#00796B', '#39AE9B', '#66D5C7', '#B78720', 
          '#E0B03C', '#FFD268', '#9C272C', '#C2454A', '#E66B70',
        ],
      };

      // Helper to build Pie/Doughnut data
      const buildChartData = (arr, labelKey, valueKey) =>
        Array.isArray(arr)
          ? {
              labels: arr.map((item) => item[labelKey]),
              datasets: [
                {
                  label: 'Leads',
                  data: arr.map((item) => Number(item[valueKey])),
                  backgroundColor: ctColors.chartColors,
                  borderColor: '#ffffff',
                  borderWidth: 2,
                  hoverOffset: 10,
                },
              ],
            }
          : { labels: [], datasets: [] };

      const pmSourcesData = buildChartData(data.pm_sources, 'source_name', 'total');
      const smSourcesData = buildChartData(data.sm_sources, 'source_name', 'total');
      const pmStatusData = buildChartData(data.pm_statuses, 'status_name', 'total');
      const smStatusData = buildChartData(data.sm_statuses, 'status_name', 'total');

      // Destroy old charts
      await this.destroyCharts();

      // Wait for DOM to be ready
      await this.$nextTick();
      await new Promise(resolve => setTimeout(resolve, 100));

      // Pie/Doughnut options with animations
      const pieOptions = {
        plugins: {
          legend: {
            position: 'bottom',
            labels: { color: '#6A7881' }
          }
        },
        animation: {
          animateRotate: true,
          animateScale: true,
          duration: 1000,
          easing: 'easeInOutQuart'
        }
      };

      // Create charts
      const pieCharts = [
        { ref: 'pmSourcesPie', data: pmSourcesData, type: 'pie' },
        { ref: 'smSourcesPie', data: smSourcesData, type: 'pie' },
        { ref: 'pmStatusDoughnut', data: pmStatusData, type: 'doughnut' },
        { ref: 'smStatusDoughnut', data: smStatusData, type: 'doughnut' },
      ];

      this.chartInstances = pieCharts
        .map(({ ref, data, type }) => {
          const context = this.getCanvasContext(ref);
          if (!context) {
            console.warn(`Skipping chart creation for "${ref}": Invalid context.`);
            return null;
          }
          return type === 'doughnut'
            ? createDoughnutChart(context, data, pieOptions)
            : createPieChart(context, data, pieOptions);
        })
        .filter((chart) => chart);

      // Initialize the evaluation tab
      await this.$nextTick();
      await this.switchtab('evaluation');
    },

    async switchtab(type) {
      if (this.isSwitchingTab) {
        console.log(`Tab switch to "${type}" ignored: Previous switch in progress.`);
        return;
      }

      this.isSwitchingTab = true;
      const previousTab = this.activeEvalTab;
      this.activeEvalTab = type;

      try {
        // Step 1: Safely destroy existing chart
        if (this.evalBarChart) {
          await this.safeDestroyChart(this.evalBarChart, 'evalBarChart');
          this.evalBarChart = null;
        }

        // Step 2: Force canvas recreation by changing key
        this.chartRenderKey++;

        // Step 3: Wait for Vue to recreate the canvas element
        await this.$nextTick();
        await new Promise(resolve => setTimeout(resolve, 200)); // Longer delay for complete cleanup

        // Step 4: Verify canvas is available
        const canvasEl = this.$refs.evalBarChart;
        if (!canvasEl) {
          console.warn('Canvas ref "evalBarChart" is not available in DOM.');
          return;
        }

        // Step 5: Build chart data
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
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
            { 
              label: 'Target', 
              data: targets, 
              backgroundColor: '#0A0F1A', 
              borderRadius: 6,
              borderWidth: 0
            },
            { 
              label: 'Achievements', 
              data: achieved, 
              backgroundColor: '#2C5E7F', 
              borderRadius: 6,
              borderWidth: 0
            },
          ],
        };

        const barOptions = {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { 
              position: 'bottom', 
              labels: { 
                color: '#6A7881',
                padding: 15,
                font: { size: 12 }
              } 
            },
            datalabels: { 
              anchor: 'end', 
              align: 'end', 
              color: '#000', 
              font: { weight: '600', size: 11 }, 
              formatter: (v) => v || '' 
            },
          },
          scales: { 
            x: { 
              ticks: { color: '#6A7881' },
              grid: { display: false }
            }, 
            y: { 
              display: false,
              beginAtZero: true
            } 
          },
          animation: {
            duration: 800,
            easing: 'easeInOutQuart',
            delay: (context) => {
              let delay = 0;
              if (context.type === 'data' && context.mode === 'default') {
                delay = context.dataIndex * 50;
              }
              return delay;
            }
          },
          transitions: {
            active: {
              animation: {
                duration: 300
              }
            }
          }
        };

        // Step 6: Create new bar chart
        const evalBarCtx = this.getCanvasContext('evalBarChart');
        if (evalBarCtx) {
          console.log('Creating evalBarChart with valid context.');
          this.evalBarChart = createBarChart(evalBarCtx, barData, barOptions);
          
          if (this.evalBarChart) {
            console.log('evalBarChart created successfully.');
          } else {
            console.warn('Failed to create evalBarChart: createBarChart returned null.');
          }
        } else {
          console.warn('Cannot create evalBarChart: Canvas context not available.');
        }
      } catch (e) {
        console.error('Error in switchtab:', e);
      } finally {
        this.isSwitchingTab = false;
      }
    },

    async destroyCharts() {
      // Cancel any pending animation frames
      this.pendingAnimationFrames.forEach(frameId => {
        cancelAnimationFrame(frameId);
      });
      this.pendingAnimationFrames.clear();

      // Destroy pie/doughnut charts
      for (let i = 0; i < this.chartInstances.length; i++) {
        const chart = this.chartInstances[i];
        if (chart) {
          await this.safeDestroyChart(chart, `chart-${i}`);
        }
      }

      // Destroy bar chart
      if (this.evalBarChart) {
        await this.safeDestroyChart(this.evalBarChart, 'evalBarChart');
        this.evalBarChart = null;
      }

      this.chartInstances = [];
    },
  },

  async beforeDestroy() {
    this.isDestroyed = true;
    await this.destroyCharts();
  },

  template: /*html*/ `
<div class="py-3 px-4 min-vh-100 bg-light">
  <div class="container">
    <h2 class="my-3">Dashboard Overview</h2>

    <!-- Metrics cards -->
    <div class="row g-4 mb-5">
      <div class="col-sm-6 col-md-3" v-for="card in 4" :key="card">
        <div class="card border-0 rounded-2 p-4 text-center shadow-sm">
          <small class="text-uppercase fw-bold text-secondary">Metric {{ card }}</small>
          <h3 class="fw-bold mt-2 mb-0 text-dark">{{ 1000 * card }}</h3>
        </div>
      </div>
    </div>

    <!-- Evaluation / Sales / Purchases Bar Chart -->
    <div class="col-12 mb-5">
      <div class="border-0 rounded-0 p-0 ">
        <div class="nav nav-pills w-100 d-flex mb-0 dashboard-tabs">
          <button 
            class="btn rounded-0 px-5 me-1"
            :class="{'btn-dark text-white': activeEvalTab==='evaluation', 'popup-head text-dark': activeEvalTab!=='evaluation'}"
            @click="switchtab('evaluation')"
            :disabled="isSwitchingTab">
            Evaluation
          </button>
          <button 
            class="btn rounded-0 px-5 me-1"
            :class="{'btn-dark text-white': activeEvalTab==='purchase', 'popup-head text-dark': activeEvalTab!=='purchase'}"
            @click="switchtab('purchase')"
            :disabled="isSwitchingTab">
            Purchase
          </button>
          <button 
            class="btn rounded-0 px-5 me-1"
            :class="{'btn-dark text-white': activeEvalTab==='sales', 'popup-head text-dark': activeEvalTab!=='sales'}"
            @click="switchtab('sales')"
            :disabled="isSwitchingTab">
            Sales
          </button>
        </div>
        <div class="card border-0 rounded-0 " style="height: 400px; overflow-x: auto;">
          <canvas ref="evalBarChart" :key="chartRenderKey" style="height: 100%; min-width: 700px;"></canvas>
        </div>
      </div>
    </div>

    <!-- Sources Pie & Status Doughnut Charts -->
    <div class="row g-4 mt-4">
      <div class="col-md-6 mb-5">
        <div class="card border-0 rounded-2 pb-4 text-center chart-card shadow-sm">
          <div class="card-header popup-head border-0 w-100 mb-3"><h2>Sources Wise Purchase</h2></div>
          <canvas ref="pmSourcesPie" style="max-height: 300px;"></canvas>
        </div>
      </div>
      <div class="col-md-6 mb-5">
        <div class="card border-0 rounded-2 pb-4 text-center chart-card shadow-sm">
          <div class="card-header popup-head border-0 w-100 mb-3"><h2>Sources Wise Sales</h2></div>
          <canvas ref="smSourcesPie" style="max-height: 300px;"></canvas>
        </div>
      </div>
      <div class="col-md-6 mb-5">
        <div class="card border-0 rounded-2 pb-4 text-center chart-card shadow-sm">
          <div class="card-header popup-head border-0 w-100 mb-3"><h2>Status Wise Purchase</h2></div>
          <canvas ref="pmStatusDoughnut" style="max-height: 300px;"></canvas>
        </div>
      </div>
      <div class="col-md-6 mb-5">
        <div class="card border-0 rounded-2 pb-4 text-center chart-card shadow-sm">
          <div class="card-header popup-head border-0 w-100 mb-3"><h2>Status Wise Sales</h2></div>
          <canvas ref="smStatusDoughnut" style="max-height: 300px;"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>
`,
};