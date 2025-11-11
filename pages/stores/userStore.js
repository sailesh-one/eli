// pages/stores/userStore.js
const { defineStore } = Pinia;

export const useUserStore = defineStore('user', {
  state: () => ({
    user: null,
    userModules: {},
    deviceInfo: {},
    userProcessing: true,
    role_type: ''
  }),

  actions: {
    async getUser() {
      let isLoggedIn = await $isLoggedIn();
      this.userProcessing = true;
      try {
        if (isLoggedIn) {
          const res = await $http('POST', `${g.$base_url_api}/auth`, { action: 'getuser' }, {}, { auth: true });
          if (res?.status === 200) {
            this.user = res?.body?.data?.user_details;
            this.userModules = res?.body?.data?.modules;
            this.role_type = this.user?.route || '';
            this.deviceInfo = res?.body?.data?.device_info;
            $log(this.user);
            $log(this.userModules);
            await $routeAdd(this.user?.route, this.userModules);
          }
        }
      } catch (e) {
        $log('User fetch failed:', e);
      } finally {
        this.userProcessing = false;
      }
    },

    getDefaultRoute() {
      for (const [key, mod] of Object.entries(this.userModules || {})) {
        if (mod.default === 'y' || mod.default === true) {
          return '/' + key;
        }
      }
      return '/home';
    },

    
    async logout() {
      try {
        const role = this.role_type || '';
        const authPath = role === 'admin' ? '/admin/login' : '/login';

        // API logout
        const res = await $http('POST', `${g.$base_url_api}/auth`, { action: 'logout' }, {}, { auth: true });
        if (res?.status === 200) {
          await $toast('success', 'Logged Out!');
        }

        // clear store + secure storage
        this.user = null;
        this.userModules = {};
        this.role_type = '';
        await $secureStorage('remove', 'logged');
        await $secureStorage('remove', 'auth');

        // 🔹 redirect using router directly
        const router = globalThis.router; // available globally from your main app init
        if (router) {
          $log('[Logout] Redirecting to:', authPath);
          router.replace(authPath);
        } else {
          // fallback if router is not ready
          window.location.href = authPath;
        }
      } catch (e) {
        $log('Logout failed:', e);
      }
    }
  },

  getters: {
    username: (state) => state.user?.name || 'Guest'
  }
});
