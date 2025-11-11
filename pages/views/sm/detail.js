const [{ useUserStore }, { History }] = await $importComponent([
  '/pages/stores/userStore.js',
  '/pages/views/pm/history.js'
]);

export const Detail = {
  name: 'detail',
  props: {
    store: { type: Object, required: true }
  },
  setup() {
    const userStore = useUserStore();
    return { userStore };
  },

  components: { History }, 

  data() {
    return {
      components: {},
      envLabel: g?.$env_server || '',
      loading: false,
      selectedModule: null
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
    
    activeComponent() {
      const { slug2, slug3 } = this.$route.params;
      if (!slug2) return 'Add';
      
      const componentMap = {
        'edit': 'Add',
        'status': 'Status',
        'vehicles': 'Vehicles'
      };
      
      return componentMap[slug3] || 'Overview';
    },

    currentConfig() {
      const { slug3 } = this.$route.params;
      const componentName = slug3 === 'vahan' ? 'Vahan' : this.activeComponent;
      return this.store?.getConfigForComponent?.(componentName) || {};
    },
  },

  methods: {
    goback() {
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
        'EvaluationChecklist': '/pages/views/pm/evaluation_checklist.js',
        'Images': '/pages/views/pm/images.js',
        'DentMap': '/pages/views/pm/dent-map.js',
        'Status': '/pages/views/pm/status.js'
      };

      await this.loadComponent(this.activeComponent, paths[this.activeComponent]);
      this.loading = false;
    },

  },

  methods: {
    goback() {
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
        'Status': '/pages/views/pm/status.js',
        'Vehicles': '/pages/views/sm/vehicles.js',
      };

      await this.loadComponent(this.activeComponent, paths[this.activeComponent]);
      this.loading = false;
    },

    triggerHistory() {
      this.$refs.historyRef?.open?.();
    }
  },

  template: /*html*/`
   <div class="container-fluid py-1 bg-light min-vh-100 px-1">
      <div class="card shadow-sm border-0 rounded-0 overflow-hidden">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center p-2" v-if="userStore?.deviceInfo?.type === 'web'">
          <div class="d-flex align-items-center gap-2">
            <button @click="goback" class="btn btn-sm btn-secondary">
              <i class="bi bi-arrow-left"></i> Back
            </button>
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-1">
              <i class="bi bi-table text-secondary"></i>
              {{ menuConfig?.title }}{{ menuConfig?.subtitle ? ' - ' + menuConfig?.subtitle : '' }}
            </h6>
          </div>

          <!-- History Button -->
          <button class="btn btn-sm btn-outline-dark shadow-sm" @click="triggerHistory">
            <i class="bi bi-clock-history"></i> History
          </button>
        </div>

        <div class="d-flex">
          <!-- Sidebar -->
          <span v-if="userStore?.deviceInfo?.type === 'web'">
            <component v-if="components.Sidebar" :is="components.Sidebar" :store="store" />
          </span>

          <!-- Main Content -->
          <div :class="['flex-grow-1 p-0', { 'w-100': !sidebarConfig.showSidebar }]">
            <div class="card-body p-1">
              <div v-if="loading" class="text-center p-4">
                <div class="spinner-border text-primary">
                  <span class="visually-hidden">Loading...</span>
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
      
      <History
        ref="historyRef"
        :store="store"
        :row="store?.getDetails?.history || []"
        title="Status History"
      />
    </div>
  `
};
