const [{ useUserStore }] = await $importComponent([
  '/pages/stores/userStore.js',
]);

export default {
  components: {  },
  data() {
    return {
      userStore: null,
    };
  },
  async created() {
    this.userStore = useUserStore();
  },
  methods: {
    goTo(page) {
        const prefix = this.userStore?.user?.route || ''; // e.g., "admin" or ""
        const path = `/${[prefix, page].filter(Boolean).join('/')}`; // Ensures no double slashes
        this.$routeTo(path);
    },
    cardImage(key) {
      return `/assets/images/logo-black.svg`;
    },
    cardIcon(icon) {
      if (icon) {
        return `bi bi-${icon}`;
      }
      return "bi bi-grid";
    },
  },

  template: /*html*/ `
    <div class="home-layout">

      <div class="container mt-5 d-flex flex-wrap justify-content-center gap-4">

      <template v-if="userStore?.userProcessing">
        <div class="w-100 d-flex flex-wrap justify-content-center gap-4">
          <div 
            v-for="n in 5" 
            :key="'skeleton-' + n" 
            class="card text-center module-card placeholder-glow skeleton-card"
          >
            <div class="card-body d-flex flex-column justify-content-center align-items-center gap-3">
              <div class="placeholder rounded-circle skeleton-icon"></div>
            </div>
          </div>
        </div>
      </template>


        <template v-else-if="userStore?.userModules && Object.keys(userStore.userModules).length">
         <template 
            v-for="(module, key) in userStore.userModules" 
            :key="key"
          >
            <div v-if="module?.visible == '1'"  class="card text-center module-card" @click.prevent="goTo(key)">
            <div  class="card-body d-flex flex-column justify-content-center align-items-center gap-3">
              <i :class="[cardIcon(module.icon), 'display-6', 'text-dark']"></i>
              <h6 class="text-dark m-0">{{ module.name || key }}</h6>
            </div>
            </div>
          </template>
        </template>

        <!-- Empty state with Bootstrap icon -->
        <div v-else class="text-center mt-5 p-4 w-100">
          <i class="bi bi-grid display-3 text-secondary mb-3"></i>
          <h5 class="text-muted">No modules available</h5>
          <p class="text-secondary">Please contact your administrator or try refreshing your access.</p>
        </div>
      </div>
    </div>
  `,
};
