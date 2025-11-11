const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);
const [{ DashboardHeader }] = await $importComponent([
    '/pages/components/common/common_dashbroad_headers.js'
]);

export default {
    name: 'LayoutColors',
    data() {
        return {
            activeTab: 'interior', // default tab for colors
            currentComponent: null, // dynamically loaded component
            isLoading: false,
            store: null,
            DashboardHeader: null
        };
    },
    
    async created() {
        // Load store
        this.userStore = storeModule.useUserStore(); 
        
        // Set header
        this.DashboardHeader = DashboardHeader;
        
        // Sync current tab from route
        this.syncActiveFromRoute();
        
        // Load that tab’s component
        await this.loadTabComponent(this.activeTab);
    },
    
    watch: {
        '$route.path'() {
            this.syncActiveFromRoute();
            this.loadTabComponent(this.activeTab);
        }
    },
    
    methods: {
        // ✅ Sync active tab with route
        syncActiveFromRoute() {
            const path = this.$route.path || '';
            // Match /admin/colors or /admin/colors/:tab
            const match = path.match(/\/admin\/colors(?:\/([^\/]+))?/);
            if (match) {
                this.activeTab = match[1] || 'interior';
                return;
            }
            // fallback
            this.activeTab = 'interior';
        },
        
        // ✅ When user clicks header tab
        async onTabChange(tab) {
            const newPath = `/admin/colors/${tab.key}`;
            if (this.$route.path !== newPath) {
                await this.$router.push(newPath);
            }
            this.activeTab = tab.key;
            await this.loadTabComponent(tab.key);
        },
        
        // ✅ Load the selected tab component dynamically
        async loadTabComponent(tabKey) {
            this.isLoading = true;
            
            try {
                // Load dashboard store if needed
                // const [storeMod] = await $importComponent(['/pages/stores/store_dashboard-v1.js']);
                // const storeFn = storeMod.default?.useStoreDashboard || storeMod.useStoreDashboard;
                // this.store = storeFn ? storeFn() : null;
                
                const [comp] = await $importComponent([
                    `/pages/views/admin/colors/${tabKey}.js`
                ]);
                const TabComponent = comp.default || Object.values(comp)[0];
                
                if (TabComponent) {
                    this.currentComponent = Vue.markRaw(TabComponent);
                } else {
                    this.currentComponent = {
                        template: `<div class='p-4 text-muted'>⚠️ Component not found for "${tabKey}".</div>`
                    };
                }
            } catch (err) {
                console.error('Error loading component:', tabKey, err);
                this.currentComponent = {
                    template: `<div class='p-4 text-danger'>⚠️ Failed to load ${tabKey} tab.</div>`
                };
            } finally {
                this.isLoading = false;
            }
        }
    },
    
    template: /*html*/ `
    <div class="layout-dashboard">
      <!-- 🟢 Always visible Header -->
      <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center px-1 py-2 border-bottom">
        <h6 class="mb-0 fw-semibold text-dark d-flex align-items-center gap-1"><i class="bi bi-palette text-secondary"></i> Colors</h6>
    </div>

            <div class="dashboard-header sticky top-0 z-10 bg-white mb-0 px-2"> 
                <component
                    :is="DashboardHeader"
                    :initial="activeTab"
                    variant="colors"
                    @change="onTabChange"
                />
            </div>
    
      <!-- 🟢 Below content changes dynamically -->
            <div class="dashboard-content mt-0">
                <transition name="fade" mode="out-in">
                    <div v-if="isLoading" key="loading" class="p-4 text-center text-gray-500">
                        Loading {{ activeTab }}...
                    </div>
                    <component
                        v-else-if="currentComponent"
                        key="tab-content"
                        :is="currentComponent"
                        :store="store"
                    />
                    <div v-else key="no-content" class="p-4 text-muted">No content available for "{{ activeTab }}".</div>
                </transition>
            </div>
    </div>
  `
};
