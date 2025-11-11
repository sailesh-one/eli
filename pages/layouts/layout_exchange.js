const [{ AddExchVinBonus }] = await $importComponent([
  '/pages/views/exchange/add_exch_vin_bonus.js'
]);
const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);
export default {
  name: 'LayoutExchangeMaster',
  data() {
    return {
      currentComponent: null,
      isLoading: false,
      store: null,
      attachComponents: Vue.markRaw({
        add_exch_vin_bonus: AddExchVinBonus,
      }),
    };
  },
  async created() {
    await this.loadComponent();
    this.userStore = storeModule.useUserStore(); 
  },
  watch: {
    '$route'(to, from) {
      if (to.params.slug1 !== from.params.slug1) {
        this.loadComponent();
      }
    }
  },

  methods: {
    async loadComponent() {
      this.isLoading = true;
      try {
        const path = await $routeGetMeta('path');
        const [m] = await $importComponent([`/pages/stores/store_${path}.js`]);
        const mod = m.default || m;
        const fn = 'useStore' + path.replace(/(^|-)(\w)/g, (_, __, c) => c.toUpperCase());

        // ✅ always prepare store
        this.store = mod?.[fn]?.();

        const isDetailRoute = this.$route.params.slug1 === 'detail';

        if (isDetailRoute) {
          const [{ Detail }] = await $importComponent([
            '/pages/views/pm/detail.js'
          ]);
          this.currentComponent = Vue.markRaw(Detail);
        } else {
          const [{ Grid }] = await $importComponent([
            '/pages/components/common/common_grid.js'
          ]);
          this.currentComponent = Vue.markRaw(Grid);
        }
      } catch (error) {
        console.error('Error loading component:', error);
      } finally {
        this.isLoading = false;
      }
    }
  },
  template: /*html*/`
    <div class="home-layout">
      <div v-if="isLoading" class="loading">Loading...</div>
      <!-- Always pass store -->
      <component v-else-if="currentComponent" :is="currentComponent" :store="store" :attachComponents="attachComponents" />
    </div>
  `
};
