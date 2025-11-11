const { defineStore } = Pinia;
export const useStoreDashboard = defineStore('dashboard', {
    state: () => ({
        isProcessing: false,
        _initialized: false,
        endpoint: null,
        errors: { gridConfig: {}, detailAddConfig: {}, statusConfig: {} },
        menuConfig: { title: 'Dash Board', subtitle: '' },
        branches:{},
        dashboard_template:{}
    
    }),
    actions: {
        // ---------------- INIT & CONFIG ----------------
        async init() {
            if (this._initialized) return;
                this._initialized = true;
                this.endpoint = this.endpoint || $routeGetMeta?.('path') || 'Dashboard-Overview';
                await this.fetchConfig();
                await this.loadInitialMasterData();
                //this._watchRoute();
        },
        async fetchConfig() {
            this.isProcessing = true;
            try {
                const res = await $http('POST', `${g.$base_url_api}/${this.endpoint}`, {
                    action: 'getconfig'
                });
                const cfg = res?.body?.data?.config || {};
                this.dashboard_template=cfg;
                
            } catch (err) {
                console.error('Error fetching config:', err);
            } finally {
                this.isProcessing = false;
            }
        },
        async loadInitialMasterData() {
            try {
                this.getYears(['mfg_year']);
                this.getMonths(['mfg_month']);

                // ---------------- MASTER CONFIG ----------------
                const masters = {
                getMakes: ['make', 'rs_make'],
                getSources: ['source'],
                getColors: ['color']
                };

                // ---------------- COMMON MASTERS ----------------
                const commonMasters = {
                getExecutivesByBranch: ['branchesWithExecutives']
                };

                // Combine both for one API call
                const collections = [
                ...Object.keys(masters),
                ...Object.keys(commonMasters)
                ].join(",");

                const res = await $http("POST", `${g.$base_url_api}/master-data`, {
                action: "getCollections",
                collections
                });

                if (res?.body?.status && res.body.data) {
                // ---------- NORMAL MASTERS ----------
                for (const [apiKey, fieldNames] of Object.entries(masters)) {
                    const list = res.body.data[apiKey]?.list || [];
                    if (Array.isArray(list)) {
                    const opts = list.map((x) => ({
                        value: String(x.value ?? x.id),
                        label: x.label ?? x.name ?? String(x.value ?? x.id),
                        group: x.group ?? null,
                    }));

                    // Assign options to all mapped fields
                    fieldNames.forEach((fieldName) => {
                        this.updateFieldOptions(fieldName, opts);
                    });
                    }
                }

                // ---------- COMMON MASTERS ----------
                for (const [apiKey, fieldNames] of Object.entries(commonMasters)) {
                    const list = res.body.data[apiKey]?.list || [];
                    fieldNames.forEach((fieldName) => {
                    this.commonMasterLists[fieldName] = list;
                    });
                }
                // Process executives by branch (after storage)
                this.processBranchesWithExecutives('branchesWithExecutives', ['branch']);
                }
            } catch (err) {
                // Error loading master data
            }
        },
        // ---------------- ROUTE HANDLING ----------------
        _watchRoute() {
            router.afterEach((to) => {
            if (to.path.includes(this.endpoint)) this.updateFromRoute(to);
            });
            const current = router.currentRoute.value;
            if (current.path.includes(this.endpoint)) this.updateFromRoute(current);
            },
        }
});
const store = useStoreDashboard();
store.init();