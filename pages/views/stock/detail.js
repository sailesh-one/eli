const [{ History }] = await $importComponent([
  '/pages/views/pm/history.js',
]);

export const Detail = {
  name: 'detail',
  props: {
    store: { type: Object, required: true }
  },

  components: { History }, 

  data() {
    return {
      components: {}, // only for dynamic ones (Sidebar, Add, Images…)
      loading: false,
      selectedModule: null,
    }
  },

  async created() {
    await this.loadActiveComponent();
    await this.loadSidebar();
  },

  watch: {
    activeComponent: 'loadActiveComponent'
  },

  computed: {
    isProcessing: vm => vm.store.isProcessing,
    endpoint: vm => vm.store.endpoint,

    menuConfig: vm => vm.store.getMenuConfig || {},
    sidebarConfig: vm => vm.store.getSidebarConfig || {},
    
    isJLRVehicle() {
      return this.store.detail?.is_certifiable === 'y';
    },
    
    currentStatus() {
      return parseInt(this.store.detail?.status) || 1;
    },
    
    activeComponent() {
      const { slug2, slug3 } = this.$route.params;
      if (!slug2) return 'Add';
      
      const componentMap = {
        'stock-detail': 'Add',
        'images': 'Images', 
        'status': 'Status',
        'refurb': 'EvaluationChecklist',
        'certification': 'Certification',
        'approval': 'CertificationApproval',
        'vahan': 'Vahan'
      };
      
      const currentStatus = parseInt(this.store.detail?.status) || 0;
      const requestedComponent = componentMap[slug3] || 'Overview';
      
      // All components are now accessible regardless of status
      // Each component will handle status-based messaging internally
      
      // If user tries to access certification but make is not certifiable, redirect to overview
      if (slug3 === 'certification' && this.store.detail?.is_certifiable !== 'y') {
        setTimeout(() => {
          this.$routeTo('');
        }, 1000);
        return 'StatusRestricted';
      }
      
      // If user tries to access approval but vehicle is not certifiable, redirect to overview
      // REMOVED: Status-based restriction - let component handle status messaging internally
      if (slug3 === 'approval' && this.store.detail?.is_certifiable !== 'y') {
        setTimeout(() => {
          this.$routeTo('');
        }, 1000);
        return 'StatusRestricted';
      }
      
      return requestedComponent;
    },

    // Get config based on current tab
    currentConfig() {
      const { slug3 } = this.$route.params;
      // When vahan tab is active, pass 'Vahan' to get vahanConfig
      const componentName = slug3 =='vahan' ? 'Vahan' : this.activeComponent;
      return this.store?.getConfigForComponent?.(componentName) || {};
    },
    
    statusMessages() {
      const currentStatus = parseInt(this.store.detail?.status) || 0;
      const statusNames = {
        1: 'refurbishment details',
        2: 'certification process', 
        3: 'certification approval',
        4: 'ready for sale process'
      };
      return {
        1: 'Please complete refurbishment details first',
        2: 'Please complete certification process first',
        3: 'Please complete certification approval first',
        4: `Currently in ${statusNames[currentStatus] || 'unknown status'}`
      };
    }
  },

  methods: {
    goback(){
      return $routeTo(`/${this.endpoint}`);
    },
    async loadSidebar() {
      await this.loadComponent('Sidebar', '/pages/components/common/common_sidebar.js');
    },

    async loadComponent(name, path) {
      if (this.components[name]) return;

      try {
        const [module] = await $importComponent([path]);
        this.components[name] = Vue.markRaw(module[name] || module.default || module);
      } catch (error) {
        console.error(`Error loading ${name}:`, error);
      }
    },

    async loadActiveComponent() {
      this.loading = true;
      
      const paths = {
        'Overview': '/pages/components/common/common_overview.js',
        'Add': '/pages/views/pm/add.js',
        'Images': '/pages/views/pm/images.js',
        'EvaluationChecklist': '/pages/views/pm/evaluation_checklist.js',
        'Certification': '/pages/views/stock/certification.js',
        'CertificationApproval': '/pages/views/stock/certification-approval.js',
        'StatusRestricted': null, 
        'Status': '/pages/views/pm/status.js',
        'Vahan' : '/pages/components/common/common_overview.js'
      };

      if (this.activeComponent === 'StatusRestricted') {
        this.loading = false;
        return;
      }

      await this.loadComponent(this.activeComponent, paths[this.activeComponent]);
      this.loading = false;
    }
  },

  template: /*html*/`
    <div class="container-fluid py-1 bg-light min-vh-100">
      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center p-2">
          <div class="d-flex align-items-center gap-2">
            <button @click="goback" class="btn btn-sm btn-secondary">
              <i class="bi bi-arrow-left"></i> Back
            </button>
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-1">
              <i class="bi bi-table text-secondary"></i>
              {{ menuConfig?.title }}{{ menuConfig?.subtitle ? ' - ' + menuConfig?.subtitle : '' }}
            </h6>
          </div>
          <!-- 👉 History Button + Offcanvas -->
          <History :store="store" />
        </div>

        <div class="d-flex">
          <!-- Sidebar -->
          <component v-if="components.Sidebar" :is="components.Sidebar" :store="store" />
          <!-- Main Content -->
          <div :class="['flex-grow-1 p-0', { 'w-100': !sidebarConfig.showSidebar }]">
            <div class="card-body p-1">
            
              <div v-if="loading" class="text-center p-4">
                <div class="spinner-border text-primary">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
              
              <!-- Status Restricted Message - Changed to Loading Indicator -->
              <div v-else-if="activeComponent === 'StatusRestricted'" class="container-fluid p-4">
                <div class="text-center py-5">
                  <div class="spinner-border text-secondary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                  <h5 class="text-secondary">Content Loading...</h5>
                </div>
              </div>
              
              <component 
                v-else-if="components[activeComponent]" 
                :is="components[activeComponent]" 
                :store="store"
                :config="currentConfig"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  `
};
