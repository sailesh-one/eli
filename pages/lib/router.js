const { createRouter, createWebHistory } = VueRouter;

// ===== Helpers =====
const getRole = path => path.startsWith('/admin') ? 'admin' : 'user';
const getDefaultRoute = role => role === 'admin' ? '/admin/home' : '/';
const getAuthPath = role => role === 'admin' ? '/admin/login' : '/';

// ===== Lazy Component Loader =====
const loadComp = layout => async () => {
  const path = `/pages/layouts/${layout}.js`;  
  try {
    const [comp] = await $importComponent([path]);
    if (!comp) throw new Error(`Component not found: ${layout}`);
    return comp;
  } catch (err) {
    $log(`Failed to load: ${layout}`, err);
    try {
      const [fallback] = await $importComponent(['/pages/layouts/layout_404.js']);
      return fallback || { template: `<div style="color:red;">404: ${layout} not found</div>` };
    } catch {
      return { template: `<div style="color:red;">Error loading ${layout}.</div>` };
    }
  }
};

// ===== Route History =====
const $routeHistory = (() => {
  const list = [];
  return {
  push(to) {
      if (to?.meta?.type === 'dynamic') {
        const basePath = '/' + (to.meta.path || '').replace(/\/+$/, '');
        const exists = list.some(i => i.path === basePath);
        if (!exists) {
          list.push({
            path: basePath,
            fullPath: basePath,
            name: to.name || null,
            time: Date.now()
          });
          list.sort((a, b) => a.time - b.time);
          $log('[Route History]', list);
        }
      }
    },
    get: () => list
  };
})();

// ===== Static Routes =====
const routes = [
  { path: '/', name: 'Intro', component: loadComp('layout_intro'), meta: { requiresAuth: false } },

  { path: '/admin/login', name: 'Admin-Login', component: loadComp('admin/layout_login'), meta: { requiresAuth: false, role: 'admin' } },

  { path: '/admin/home', name: 'Admin-Home', component: loadComp('admin/layout_home'), meta: { requiresAuth: true, role: 'admin' } },

  { path: '/admin', redirect: '/admin/home' },

  // Catch-all → show 404 page instead of redirecting to `/`
  { path: '/:catchAll(.*)', name: 'NotFound', component: loadComp('layout_404') }
];


const router = createRouter({
  history: createWebHistory(),
  routes
});

// ===== Global Guard =====
router.beforeEach(async (to, from, next) => {
  const role = await $isLoggedIn(); // 'admin' or null
  const isAdminRoute = to.path.startsWith('/admin');
  const isAdminLogin = to.path === '/admin/login';

  // Public route → allow anyone
  if (!isAdminRoute) return next();

  // Admin route → not logged in
  if (!role && isAdminRoute && !isAdminLogin) return next('/admin/login');

  // Logged in admin → prevent access to login
  if (role === 'admin' && isAdminLogin) return next('/admin/home');

  next();
});



// ===== Dynamic Route Adder =====
const dynamicRouteNames = new Set();

const $routeAdd = (type, modules) => {
  if (type !== 'admin') return; // ignore non-admin routes

  const prefix = '/admin';
  const dynamicRouteNames = new Set();

  Object.keys(modules).forEach(key => {
    const routeName = key.charAt(0).toUpperCase() + key.slice(1);
    const basePath = `${prefix}/${key}`;
    router.addRoute({
      path: `${basePath}/:slug1?/:slug2?/:slug3?`,
      name: routeName,
      component: loadComp(`admin/layout_${key}`),
      meta: { requiresAuth: true, role: 'admin', type: 'dynamic', path: key }
    });
    dynamicRouteNames.add(routeName);
  });
};



export { router, $routeAdd, $routeHistory };
