const [{ useUserStore }] = await $importComponent([
  '/pages/stores/userStore.js'
]);

export const Header = {
  name: 'Header',

  setup() {
    const userStore = useUserStore();
    return { userStore };
  },

  data() {
    return {
      envLabel: g?.$env_server || '',
    };
  },

  computed: {
    groupedCategories() {
      const modules = this.userStore?.userModules || {};
      const grouped = {};
      const currentPath = this.$routeGetPath?.() || '';

      for (const [key, mod] of Object.entries(modules)) {
        const cat = mod.category || 'Others';
        if (!grouped[cat]) {
          grouped[cat] = { modules: [], isActive: false };
        }

        grouped[cat].modules.push({ key, ...mod });

        if (new RegExp(`(^|/)${key}(/|$)`).test(currentPath)) {
          grouped[cat].isActive = true;
        }
      }
      return grouped;
    },

    envLabelLower() {
      return this.envLabel?.toLowerCase?.() || '';
    },

    isDev() {
      return this.envLabelLower === 'dev';
    },

    isUat() {
      return this.envLabelLower === 'uat';
    },

    showEnvBadge() {
      return ['dev', 'uat'].includes(this.envLabelLower);
    },

    routeHistoryList() {
      return $routeHistory?.get?.() || [];
    },

    info() {
      return this.userStore?.user?.info || {
        name: '',
        location: ''
      };
    }
  },

  methods: {
    goTo(page) {
      const prefix = this.userStore?.user?.route || '';
      const path = `/${[prefix, page].filter(Boolean).join('/')}`;
      $routeTo?.(path);

      const offcanvasElement = document.getElementById('navbarOffcanvas');
      if (offcanvasElement?.classList.contains('show')) {
        const bsOffcanvas = globalThis.bootstrap?.Offcanvas?.getInstance(offcanvasElement);
        bsOffcanvas?.hide?.();
      }
    },

    isActive(key) {
      const currentPath = this.$routeGetPath?.() || '';
      return new RegExp(`(^|/)${key}(/|$)`).test(currentPath);
    },

    logout() {
      try {
        this.userStore?.logout?.();
        $log?.("Logging out...");
      } catch (e) {
        console.error("Logout failed:", e);
      }
    },

    getCategoryId(category) {
      return `collapse-mobile-${(category || 'other').replace(/\s+/g, '-')}`;
    }
  },

  template: /*html*/ `
    <!-- Environment Badge -->
    <span v-if="showEnvBadge" :class="['env-badge', isDev ? 'bg-success text-white' : 'bg-warning text-dark']">
      <span class="env-badge-text">{{ envLabel }}</span>
    </span>

    <!-- Main Header -->
    <header class="bg-white border-bottom shadow-sm p-1 sticky-top amek-parent-header" v-if="userStore?.deviceInfo?.type === 'web'">
      <nav class="navbar navbar-expand-lg container-fluid" role="navigation" aria-label="Main Navigation">
        <a class="navbar-brand" href="#" @click.prevent="goTo('home')" aria-label="Go to Home">
          ELI
        </a>

        <!-- Desktop Menu -->
        <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarMenuDesktop">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-lg-2">
            <li class="nav-item">
              <a href="#" class="nav-link menu-item" :class="{ active: isActive('home') }" @click.prevent="goTo('home')">
                <i class="bi bi-house"></i> Home
              </a>
            </li>

            <template v-if="!userStore.userProcessing">
              <template v-for="(categoryData, category) in groupedCategories" :key="'cat_' + category">
                <li class="nav-item dropdown">
                  <a class="nav-link dropdown-toggle" :class="{ 'active': categoryData.isActive }"
                     href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{ category }}
                  </a>
                  <ul class="dropdown-menu scrollable-dropdown-menu">
                    <a v-for="module in categoryData.modules" :key="'mod_' + module.key"
                       :href="'/' + [userStore.user?.route, module.key].filter(Boolean).join('/')"
                       class="dropdown-item"
                       :class="{ active: isActive(module.key) }"
                       @click.prevent="goTo(module.key)">
                      {{ module.name || module.key }}
                    </a>
                  </ul>
                </li>
              </template>
            </template>
          </ul>

          <!-- User & Dealer Info -->
          <div class="d-flex align-items-center gap-4 ms-auto">
            <template v-if="userStore.userProcessing">
              <span class="placeholder-glow">
                <span class="placeholder col-4 rounded skeleton-blur d-inline-block"></span>
              </span>
            </template>
            <template v-else>
              <div class="d-flex align-items-center gap-3">
                <!-- Dealer Info -->
                <div class="d-flex flex-column align-items-end text-dark fw-semibold">
                  <span v-if="info.name" class="info-name small fw-bold text-secondary">
                    {{ info.name }}
                  </span>
                  <span v-if="info.code || info.location" class="info-meta small text-muted">
                    {{ info.code }} <span v-if="info.location">• {{ info.location }}</span>
                  </span>
                </div>

                <!-- Separator -->
                <div class="vr"></div>

                <!-- User Info -->
                <div class="d-flex flex-column align-items-end text-dark fw-semibold">
                  <span class="d-flex align-items-center">
                    <i class="bi bi-person me-2"></i>
                    <span class="text-truncate user-name">{{ userStore.user?.name || 'User' }}</span>
                  </span>
                  <span v-if="userStore.user?.role_name" class="user-role text-muted small">
                    {{ userStore.user?.role_name }}
                  </span>
                </div>
              </div>
            </template>

            <button class="btn btn-outline-secondary d-flex align-items-center rounded-circle py-1 px-2" @click="logout" title="Logout">
              <i class="bi bi-lock"></i>
            </button>
          </div>
        </div>

        <!-- Mobile Menu Toggler -->
        <button class="navbar-toggler d-lg-none ms-auto" type="button"
                data-bs-toggle="offcanvas" data-bs-target="#navbarOffcanvas" aria-controls="navbarOffcanvas">
          <span class="navbar-toggler-icon"></span>
        </button>
      </nav>
    </header>

    <!-- Route History Tabs -->
    <div v-if="1==2 && routeHistoryList.length" class="route-history-tabs-wrapper d-none d-lg-block border-top bg-light">
      <div class="route-history-tabs-container">
        <ul class="nav nav-tabs nav-tabs-sm mb-0 flex-nowrap">
          <li v-for="(item, index) in routeHistoryList" :key="index" class="nav-item">
            <a href="#"
               class="nav-link py-1 px-2 text-truncate d-flex align-items-center gap-1"
               :class="{ active: isActive(item.path) }"
               @click.prevent="goTo(item.path)"
               :title="item.name || 'Route'">
              <span class="text-truncate">{{ item.name || 'Unnamed' }}</span>
            </a>
          </li>
        </ul>
      </div>
    </div>


    <template v-if="userStore?.deviceInfo?.type === 'web'">
    <!-- Mobile Offcanvas Menu -->
    <div class="offcanvas offcanvas-end bg-light" tabindex="-1" id="navbarOffcanvas" aria-labelledby="navbarOffcanvasLabel">
      <div class="offcanvas-header bg-white shadow-sm border-bottom">
        <h5 class="offcanvas-title fw-bold" id="navbarOffcanvasLabel">Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body d-flex flex-column">
        <ul class="navbar-nav flex-grow-1">
          <li class="nav-item mb-2">
            <a href="#" class="nav-link menu-item fs-6 text-black"
               :class="{ 'active bg-dark text-white rounded': isActive('home') }"
               @click.prevent="goTo('home')">
              <i class="bi bi-house me-2"></i> Home
            </a>
          </li>

          <template v-if="!userStore.userProcessing">
            <template v-for="(categoryData, category) in groupedCategories" :key="'cat_mobile_' + category">
              <li class="nav-item mb-2">
                <a class="nav-link d-flex justify-content-between align-items-center fs-6 text-black"
                   :class="{ 'bg-dark text-white rounded': categoryData.isActive, 'collapsed': !categoryData.isActive }"
                   :href="'#' + getCategoryId(category)"
                   data-bs-toggle="collapse"
                   role="button"
                   aria-expanded="false"
                   :aria-controls="getCategoryId(category)">
                  <span>{{ category }}</span>
                  <i class="bi bi-chevron-down ms-2"></i>
                </a>
                <div class="collapse" :class="{ 'show': categoryData.isActive }" :id="getCategoryId(category)">
                  <ul class="nav flex-column ps-3 py-2 border-start border-secondary">
                    <li v-for="module in categoryData.modules" :key="'mod_mobile_' + module.key">
                      <a :href="'/' + [userStore.user?.route, module.key].filter(Boolean).join('/')"
                         class="nav-link fs-6 text-black"
                         :class="{ 'active bg-dark text-white rounded': isActive(module.key) }"
                         @click.prevent="goTo(module.key)">
                        {{ module.name || module.key }}
                      </a>
                    </li>
                  </ul>
                </div>
              </li>
            </template>
          </template>
        </ul>

        <!-- Mobile Footer -->
        <div class="mt-auto pt-3 border-top">
          <template v-if="userStore.userProcessing">
            <span class="placeholder-glow d-block mb-2">
              <span class="placeholder col-6 rounded skeleton-blur"></span>
            </span>
          </template>
          <template v-else>
            <div class="d-flex flex-column mb-2">
              <span class="d-flex align-items-center text-dark fw-semibold">
                <i class="bi bi-person me-2"></i>
                <span class="text-truncate user-name">{{ userStore.user?.name || 'User' }}</span>
              </span>
              <span v-if="userStore.user?.role_name" class="user-role ms-4 text-muted small">
                {{ userStore.user?.role_name }}
              </span>

              <span v-if="info.name" class="info-name ms-4 small fw-bold text-primary">
                {{ info.name }}
              </span>
              <span v-if="info.code || info.location" class="info-meta ms-4 small text-muted">
                {{ info.code }} <span v-if="info.location">• {{ info.location }}</span>
              </span>
            </div>
          </template>
          <button class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" @click="logout">
            <i class="bi bi-lock me-2"></i> Logout
          </button>
        </div>
      </div>
    </div>
    </template>
  `,
};
