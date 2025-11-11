const [{ Executives }, { History }] = await $importComponent([
  '/pages/views/pm/executive.js',
  '/pages/views/pm/history.js'
]);
const [storeModule] = await $importComponent(['/pages/stores/userStore.js']);

export default {
  name: 'LayoutSalesMaster',
  data() {
    return {
      currentComponent: null,
      isLoading: false,
      store: null,
      attachComponents: Vue.markRaw({
        executive: Executives,
        history: History,
      }),
    };
  },
  async created() {
    await this.loadComponent();
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
