const [{ useUserStore }] = await $importComponent([
    '/pages/stores/userStore.js'
]);
export const DashboardHeader = {
    name: 'DashboardHeader',
    emits: ['change'],
    
    props: {
        tabs: {
            type: Array,
            default: () => [
                { key: 'overview', label: 'Overview' },
                { key: 'evaluations', label: 'Evaluations' },
                { key: 'stocks', label: 'Stocks' },
                { key: 'overall-sales', label: 'Overall Sales' },
                { key: 'executive-performance', label: 'Executive Performance' }         
            ]
        },
        // optional override to force which tab-set to use (e.g. 'colors')
        variant: { type: String, default: '' },
        initial: { type: String, default: 'overview' },
        tab:null,
    },
    
    setup() {
        const userStore = useUserStore();
        return { userStore };
    },
    
    data() {
        return {
            envLabel: (typeof g !== 'undefined' && g.$env_server) ? g.$env_server : '',
            activeKey: ''
        };
    },
    
    created() {
        this.activeKey = this.initial || (this.visibleTabs && this.visibleTabs[0]?.key) || '';
    },
    
    computed: {
        // allTabs chooses the base set depending on route or variant
        allTabs() {
            // define a colors-specific tab set
            const colorsTabs = [
                { key: 'interior', label: 'Interior' },
                { key: 'exterior', label: 'Exterior' }
            ];
            
            // get current path robustly
            // determine current path robustly: prefer router helper, then this.$route, then window.location
            let currentPath = '';
            if (typeof $routeGetPath === 'function') {
                try { currentPath = $routeGetPath() || ''; } catch (e) { currentPath = ''; }
            } else if (this.$route && (this.$route.path || this.$route.fullPath)) {
                currentPath = this.$route.path || this.$route.fullPath || '';
            } else if (typeof window !== 'undefined' && window.location && window.location.pathname) {
                currentPath = window.location.pathname || '';
            }
            
            // if variant explicitly requests colors OR the path contains '/admin/colors', use colorsTabs
            if (this.variant === 'colors' || currentPath.includes('/admin/colors')) {
                return colorsTabs;
            }
            
            // otherwise use the provided tabs prop (dashboard defaults)
            return this.tabs || [];
        },
        // whether the executive tab should be hidden (handles string/number role_type)
        isExecutiveHidden() {
            if (!this.userStore) return false;
            const rt = this.userStore.role_type;
            const num = typeof rt === 'number' ? rt : parseInt(String(rt || ''), 10);
            return Number.isInteger(num) && num === 0;
        },
        // safe visible tabs list (handles missing tabs prop and role-based hiding)
        visibleTabs() {
            const all = this.allTabs || [];
            // If role indicates executive should be hidden, filter it out
            if (this.isExecutiveHidden) {
                return all.filter(t => !(t && t.key === 'executive'));
            }

            // debug log with normalized role_type
            const roleType = this.userStore ? (typeof this.userStore.role_type === 'number' ? this.userStore.role_type : parseInt(String(this.userStore.role_type || ''), 10)) : null;
            //console.log('DashboardHeader: visibleTabs', all, roleType);
            return all;
        }
    },
    
    methods: {
        selectTab(tab) {
            if (!tab || !tab.key) return;
            this.activeKey = tab.key;
            this.$emit('change', tab);
        },
        
        syncActiveFromRoute() {
            try {
                const currentPath = (typeof $routeGetPath === 'function')
                ? $routeGetPath()
                : (this.$route?.path || this.$route?.fullPath || '');
                
                // check the same set that will be rendered
                const found = (this.allTabs || []).find(t =>
                    t.path ? currentPath.startsWith(t.path) : currentPath.includes(t.key)
                );
                
                if (found) {
                    this.activeKey = found.key;
                    this.$emit('change', found);
                }
            } catch (e) {
                console.warn('DashboardHeader: syncActiveFromRoute error', e);
            }
        }
    },
    
    template: /*html*/ `
    <div class="dashboard-header mt-3">
            <div class="nav-wrap">
            <div class="d-flex gap-2 mb-0">
            <button
            v-for="(tab, idx) in visibleTabs"
            :key="(tab && tab.key) || idx"
            class="btn rounded-0 px-5"
            :class="activeKey === ((tab && tab.key) || '') ? 'btn-dark text-white' : 'btn-light text-dark'"
            @click.prevent="selectTab(tab)"
            style="min-width:120px; border-radius:6px;"
            v-if="!(tab && tab.key === 'executive' && (userStore && userStore.role_type === 0))" 
            >
            {{ (tab && tab.label) || '' }}
            </button>
            </div>
            </div>
            </div>
  `
};

// sync with route
DashboardHeader.mounted = function () {
    this.syncActiveFromRoute?.();
    if (this.$watch) {
        this._unwatchRoute = this.$watch('$route', () => this.syncActiveFromRoute?.());
    }
};

DashboardHeader.beforeUnmount = function () {
    if (this._unwatchRoute) this._unwatchRoute();
};
