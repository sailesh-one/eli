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
      localActiveKey: null, 
      isReadOnly: null
    };
  },

  async created() {
    await this.loadSidebar();
    await this.ensureDefaultComponent(); 
    await this.loadActiveComponent();
  },

  computed: {
    isProcessing: vm => vm.store.isProcessing,
    endpoint: vm => vm.store.endpoint,
    menuConfig: vm => vm.store.getMenuConfig || {},
    sidebarConfig: vm => vm.store.getSidebarConfig || {},

    detailMenu() {
      const cfg = this.store.getDetailMenuConfig || {};
      // clone ensures Vue tracks changes in case store gives plain object
      return { ...cfg };
    },

    activeComponent() {
      const { slug3 } = this.$route.params;
      const entries = Object.values(this.detailMenu).filter(m => m?.isEnabled && !m?.isHidden);
      if (!entries.length) return null;

      const key = slug3 || this.localActiveKey;
      const match = entries.find(m => m.fieldKey === key) || entries[0];
      return this._normalizeName(match.component);
    }
  },

  watch: {
    detailMenu: {
      handler(newVal, oldVal) {
        if (JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
          this.ensureDefaultComponent();
          this.loadActiveComponent();
        }
      },
      deep: true
    },

    activeComponent: 'loadActiveComponent'
  },

  methods: {
    goback() {
      return $routeTo(`/${this.endpoint}`);
    },

    /** Normalize backend component path → PascalCase name */
    _normalizeName(componentPath) {
      if (!componentPath) return null;
      return componentPath
        .split('/')
        .pop()
        .replace(/\.[jt]s$/, '')
        .replace(/[-_](\w)/g, (_, c) => c.toUpperCase())
        .replace(/^\w/, c => c.toUpperCase());
    },

    /** Lazy import + cache component modules */
    async loadComponent(name, path) {
      if (!name || !path) return;
      if (this.components[name]) return;

      try {
        const [module] = await $importComponent([path]);
        this.components[name] = Vue.markRaw(module[name] || module.default || module);
      } catch (err) {
        console.error(`Error loading "${name}" from "${path}"`, err);
      }
    },

    /**  Load the current active component dynamically */
    async loadActiveComponent() {
      this.loading = true;
      try {
        const { slug3 } = this.$route.params;
        const entries = Object.values(this.detailMenu).filter(m => m?.isEnabled && !m?.isHidden);
        if (!entries.length) return;

        const key = slug3 || this.localActiveKey;
        const match = entries.find(m => m.fieldKey === key) || entries[0];
        this.isReadOnly = !!match?.isReadOnly;
        const componentPath = `/pages/${match.component}.js`;
        const componentName = this._normalizeName(match.component);

        await this.loadComponent(componentName, componentPath);
      } catch (err) {
        console.error('Error loading dynamic component:', err);
      } finally {
        this.loading = false;
      }
    },

    /**  Determine default component (no routing redirect) */
    async ensureDefaultComponent() {
      const { slug3 } = this.$route.params;
      if (slug3) return;

      const entries = Object.values(this.detailMenu).filter(m => m?.isEnabled && !m?.isHidden);
      if (!entries.length) return;

      // store first valid tab as local fallback
      this.localActiveKey = entries[0].fieldKey;
    },

    /** Load Sidebar once */
    async loadSidebar() {
      await this.loadComponent('Sidebar', '/pages/components/common/common_sidebar.js');
    },

    /** Open History modal */
    triggerHistory() {
      this.$refs.historyRef?.open?.();
    },

    reloadData(){
      window.location.reload();
    },
  },

  /** Template */
  template: /*html*/`
    <div class="container-fluid py-1 bg-light min-vh-100 px-1">

      <div class="card shadow-sm border-0 rounded-0 overflow-hidden">
        
        <!-- Header -->
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center p-2"
             v-if="userStore?.deviceInfo?.type === 'web'">
          <div class="d-flex align-items-center gap-2">
            <button @click="goback" class="btn btn-sm btn-secondary">
              <i class="bi bi-arrow-left"></i> Back
            </button>
            <h6 class="mb-0 fw-semibold d-flex align-items-center gap-1">
              <i class="bi bi-table text-secondary"></i>
              {{ menuConfig?.title }}{{ menuConfig?.subtitle ? ' - ' + menuConfig?.subtitle : '' }}
            </h6>

        <!-- Reload button -->
        <button
          @click="reloadData"
          class="btn btn-sm btn-light border rounded-pill ms-2 px-2 py-0"
          title="Reload"
        >
          <i class="bi bi-arrow-clockwise"></i>
        </button>

          </div>

          <button class="btn btn-sm btn-outline-dark shadow-sm" @click="triggerHistory">
            <i class="bi bi-clock-history"></i> History
          </button>
        </div>

        <!-- Layout -->
        <div class="d-flex">
          
          <!-- Sidebar -->
          <span v-if="userStore?.deviceInfo?.type === 'web'">
            <component v-if="components.Sidebar"
                       :is="components.Sidebar"
                       :store="store" />
          </span>

          <!-- Main content -->
          <div :class="['flex-grow-1 p-0', { 'w-100': !sidebarConfig.showSidebar }]">
            <div class="card-body p-1">
              
              <div v-if="loading" class="text-center p-4">
                <div class="spinner-border text-secondary">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>

              <!-- Dynamic Component -->
              <component
                v-else-if="activeComponent && components[activeComponent]"
                :is="components[activeComponent]"
                :store="store"
                :isReadOnly="isReadOnly"
              />

              <div v-else class="text-center text-muted py-4">
                <i class="bi bi-car-front"></i>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- History Modal -->
      <History
        ref="historyRef"
        :store="store"
        :row="store?.getDetails?.history || []"
        title="Status History"
      />
    </div>
  `
};
