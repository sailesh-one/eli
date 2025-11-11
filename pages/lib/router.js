const { createRouter, createWebHistory } = VueRouter;

// ===== Helpers =====
const getRole = path => path.startsWith('/admin') ? 'admin' : 'dealer';
const getDefaultRoute = role => role === 'admin' ? '/admin/home' : '/home';
const getAuthPath = role => role === 'admin' ? '/admin/login' : '/login';

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
  {
    path: '/',
    name: 'Root',
    beforeEnter: async (_, __, next) => {
      const role = await $isLoggedIn();
      next(role ? getDefaultRoute(role) : '/login');
    }
  },

  { path: '/login', name: 'Login', component: loadComp('layout_login'), meta: { requiresAuth: false, role: '' } },
  { path: '/admin/login', name: 'Admin-Login', component: loadComp('admin/layout_login'), meta: { requiresAuth: false, role: 'admin' } },
  { path: '/home', name: 'Dealer-Home', component: loadComp('layout_home'), meta: { requiresAuth: true, role: '' } },
  { path: '/admin/home', name: 'Admin-Home', component: loadComp('admin/layout_home'), meta: { requiresAuth: true, role: 'admin' } },
  { path: '/admin', redirect: '/admin/home', meta: { requiresAuth: true, role: 'admin' } },
  { path: '/:catchAll(.*)', name: 'NotFound', component: loadComp('layout_404'), meta: { requiresAuth: false, role: '' } }
];


const router = createRouter({
  history: createWebHistory(),
  routes
});

// ===== Global Guard =====
router.beforeEach(async (to, from, next) => {
  const role = await $isLoggedIn(); // 'admin', 'dealer', or null
  const currentRole = getRole(to.path);
  const isLoginPage = ['/login', '/admin/login'].includes(to.path);

  //alert(`[DEBUG]\nrole: ${role}\ncurrentRole: ${currentRole}\nto.path: ${to.path}`);

  // If not logged in → always go to correct login page
  if (!role) {
    if (!isLoginPage) {
      const redirectPath = getAuthPath(currentRole);
      //alert(`[AuthGuard] Not logged in → redirecting to ${redirectPath}`);
      $log(`[AuthGuard] Not logged in → redirecting to ${redirectPath}`);
      return next(redirectPath);
    }
    //alert('[AuthGuard] On login page, continue');
    return next(); 
  }

  // already logged in
  if (isLoginPage) {
    const path = getDefaultRoute(role);
    //alert(`[AuthGuard] Already logged in (${role}) → redirecting to ${path}`);
    return next(path);
  }

  if (currentRole && currentRole !== role) {
    const path = getDefaultRoute(role);
    //alert(`[AuthGuard] Role mismatch (${role} vs ${currentRole}) → redirecting to ${path}`);
    return next(path);
  }

 // alert('[AuthGuard] Passed → continuing to route');
  next();
});


// ===== Dynamic Route Adder =====
const dynamicRouteNames = new Set();

const $routeAdd = (type, modules) => {
  const prefix = type ? `/${type}` : '';
  const role = type || '';
  // Step 1: Remove previously added dynamic routes
  Array.from(dynamicRouteNames).forEach(name => {
    if (router.hasRoute(name)) {
      try {
        router.removeRoute(name);
        $log(`[Router] Removed dynamic route: ${name}`);
      } catch (err) {
        $log(`[Router] Failed to remove dynamic route: ${name}`, err);
      }
    }
  });
  dynamicRouteNames.clear();

  // Step 2: Add new dynamic routes
  Object.keys(modules).forEach(key => {
    const routeName = key.charAt(0).toUpperCase() + key.slice(1);
    const basePath = key === type ? prefix : `${prefix}/${key}`;

    try {
      router.addRoute({
        path: `${basePath}/:slug1?/:slug2?/:slug3?`,
        name: routeName,
        component: loadComp(`${role ? role + '/' : ''}layout_${key}`),
        meta: { 
          requiresAuth: true, 
          role, 
          type: 'dynamic', 
          path: key, 
          keepAlive: true 
        }
      });
      dynamicRouteNames.add(routeName);
      $log(`[Router] Added dynamic route: ${basePath}`);
    } catch (err) {
      $log(`[Router] Failed to add dynamic route: ${basePath}`, err);
    }
  });

  $log('[Router] Dynamic modules registered — no auto navigation triggered');
};


export { router, $routeAdd, $routeHistory };
