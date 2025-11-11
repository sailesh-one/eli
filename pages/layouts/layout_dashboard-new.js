const [{ DashboardHeader }] = await $importComponent([
    '/pages/components/common/common_dashbroad_headers.js'
]);
const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);
export default {
    name: 'LayoutDashboardNew',
    data() {
        return {
            activeTab: 'overview', // default tab
            currentComponent: null, // dynamically loaded component
            isLoading: false,
            store: null,
            DashboardHeader: null,
            dashboard_template:null,
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
            const match = path.match(/dashboard-([a-zA-Z0-9_-]+)/);
            this.activeTab = match && match[1] ? match[1] : 'overview';
        },
        
        // ✅ When user clicks header tab
        async onTabChange(tab) {
            const newPath = `/dashboard-new/dashboard-${tab.key}`;
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
                    `/pages/views/dashboard/dashboard-${tabKey}.js`
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
      <div class="dashboard-header sticky top-0 z-10 bg-white shadow-sm">
        <component
          :is="DashboardHeader"
          :initial="activeTab"
          @change="onTabChange"
        />
      </div>
    
      <!-- 🟢 Below content changes dynamically -->
            <div class="dashboard-content mt-4">
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
