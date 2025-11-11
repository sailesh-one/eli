
export const Sidebar = {
  name: 'Sidebar',
  props: { store: { type: Object, required: true } },
  data() {
    return {
      isCollapsed: false,
      openMenus: []
    };
  },
  
  created() {
    const saved = localStorage.getItem('sidebar_collapsed');
    if (saved) this.isCollapsed = JSON.parse(saved);
  },

  mounted() {
    this.$nextTick(() => {
      this.ensureActiveSubmenus();
      this.$emit('collapse-state-changed', this.isCollapsed);
    });
  },

  computed: {
    items() {
      return this.store?.getSidebarConfig?.sidebarItems || [];
    },
    collapsedClass() {
      return { 'collapsed-sidebar': this.isCollapsed };
    },
    isDisabled() {
      return !this.store?.getSidebarConfig?.showSidebar || false;
    },
  },

  watch: {
    isCollapsed(val) {
      localStorage.setItem('sidebar_collapsed', JSON.stringify(val));
      this.$emit('collapse-state-changed', val);
      if (val) this.openMenus = [];
    },

    $route: {
      immediate: true,
      handler() {
        this.ensureActiveSubmenus();
      }
    },

    items: {
      deep: true,
      handler() {
        this.ensureActiveSubmenus();
      }
    }
  },

  methods: {
    toggleSubMenu(key) {
      if (this.isDisabled) return;
      this.openMenus = this.isSubMenuOpen(key)
        ? this.openMenus.filter(k => k !== key)
        : [...this.openMenus, key];
    },
    isSubMenuOpen(key) {
      return this.openMenus.includes(key);
    },
    hasActiveChild(item) {
      return item.sub?.some(child => this.isActive(child.key, child));
    },
    getAbbreviation(label) {
      return label?.slice(0, 2).toUpperCase() || 'NA';
    },
    isActive(key, item = null) {
      if (item && typeof item.isActive === 'boolean') return item.isActive;
      return this.$routeGetPath().includes(key);
    },
    ensureActiveSubmenus() {
      this.openMenus = [];
      this.items.forEach(item => {
        if (this.isActive(item.key, item) || this.hasActiveChild(item)) {
          this.openMenus.push(item.key);
        }
      });
    },
    handleItemClick(event, item) {
      $log("apple item :", item);
      if (this.isDisabled || item.disabled) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1) return;
      
      // Always navigate if href exists (even if has sub-buckets)
      if (item.href) {
        event.preventDefault();
        $routeTo(item.href);
        // After navigation, toggle submenu if it exists and sidebar is expanded
        if (item.sub?.length && !this.isCollapsed) {
          this.toggleSubMenu(item.key);
        }
      } else if (item.sub?.length && !this.isCollapsed) {
        // If no href but has sub-buckets, just toggle
        event.preventDefault();
        this.toggleSubMenu(item.key);
      }
    },
    handleChildClick(event, child) {
      if (this.isDisabled || child.disabled) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1) return;
      if (child.href) {
        event.preventDefault();
        $routeTo(child.href);
      }
    }
  },

  template: /*html*/ `
<div 
  :class="[
    'text-white vh-100 position-relative rounded-1 mt-1 shadow-lg sidebar-container',
    collapsedClass,
    { 'opacity-75 pe-none': isDisabled }
  ]"
>
  <!-- If items exist -->
  <ul v-if="items.length" 
      id="sidebar-menu-list" 
      class="nav nav-pills flex-column mb-auto px-1 py-3 overflow-auto"
      style="max-height: calc(100% - 40px);">
    <li v-for="item in items" :key="item.key" class="nav-item mb-1">
      <!-- main links -->
      <a 
        :href="item.href || '#'"
        class="nav-link d-flex align-items-center rounded-3 hover-lift"
        :class="[ 'text-white',
          { 
            'justify-content-center p-2': isCollapsed,
            'justify-content-between px-3 py-2': !isCollapsed,
            'bg-secondary text-light fw-bold shadow-sm': isActive(item.key, item) || (!isCollapsed && hasActiveChild(item)),
            'disabled pointer-events-none opacity-50': isDisabled || item.disabled
          }
        ]"
        :title="item.label"
        @click="handleItemClick($event, item)"
        :aria-expanded="item.sub?.length && !isCollapsed ? isSubMenuOpen(item.key) : null"
        :aria-controls="item.sub?.length ? 'submenu-' + item.key : null"
      >
        <div class="d-flex align-items-center">
          <span v-if="isCollapsed" class="fw-bold">{{ getAbbreviation(item.label) }}</span>
          <span v-else class="text-truncate">{{ item.label || 'Untitled' }}</span>
        </div>

        <div v-if="!isCollapsed" class="d-flex align-items-center ms-auto">
          <span v-if="item.count" class="badge bg-dark text-white rounded-pill me-2 count-item fw-normal">{{ item.count }}</span>
          <i v-if="item.sub?.length" :class="['bi', isSubMenuOpen(item.key) ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
        </div>
      </a>

      <!-- child menu -->
      <ul v-if="!isCollapsed && item.sub?.length && isSubMenuOpen(item.key)" 
          :id="'submenu-' + item.key"
          class="nav flex-column ps-2 pt-1 pb-1 submenu-list small">
        <li v-for="child in item.sub" :key="child.key" class="nav-item">
          <a 
            :href="child.href || '#'"
            class="nav-link py-2 d-flex justify-content-start align-items-center rounded-2 hover-lift-sm"
            :class="{
              'text-light': !isActive(child.key, child) && !(isDisabled || child.disabled),
              'bg-secondary text-white fw-bold shadow-sm': isActive(child.key, child),
              'disabled pointer-events-none opacity-50': isDisabled || child.disabled
            }"
            @click="handleChildClick($event, child)"
            :title="child.label">
            <i class="bi bi-chevron-right"></i> <span class="text-truncate">{{ child.label || 'Unnamed' }}</span>
            <span v-if="child.count" class="badge bg-dark text-white rounded-pill ms-2 count-item fw-normal">{{ child.count }}</span>
          </a>
        </li>
      </ul>
    </li>
  </ul>

  <!-- If NO items, show small placeholder -->
  <div v-else class="d-flex flex-column align-items-center justify-content-center h-100 small text-muted p-2">
    <i class="bi bi-layout-sidebar-inset fs-3 mb-2"></i>
    <span v-if="!isCollapsed"></span>
  </div>
</div>
`
};
