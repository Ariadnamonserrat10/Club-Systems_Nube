import { createApp } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import App from './App.vue';
import Login from './Pages/Login.vue';
import CrearC from './Pages/CrearC.vue';
import Oficina from './Pages/Oficina.vue';
import Monitor from './Pages/Monitor.vue';
import { authService } from './services/auth';

const routes = [
  { path: '/', component: Login, meta: { public: true } },
  { path: '/crear-cuenta', component: CrearC, meta: { public: true } },
  { path: '/oficina', component: Oficina, meta: { requiresAuth: true, role: 'OFICINA' } },
  { path: '/monitor', component: Monitor, meta: { requiresAuth: true, role: 'MONITOR' } }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

router.beforeEach(async (to, _from, next) => {
  const isAuthenticated = authService.isAuthenticated();
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth);
  const isPublic = to.matched.some(record => record.meta.public);
  const requiredRole = to.meta.role;

  if (requiresAuth && !isAuthenticated) {
    next({ path: '/', query: { redirect: to.fullPath } });
    return;
  }

  if (isAuthenticated && requiredRole) {
    const userData = authService.getUserData();
    
    if (userData?.tipo) {
      const userType = userData.tipo.toUpperCase();
      const requiredType = requiredRole.toUpperCase();
      
      if (userType !== requiredType) {
        if (userType === 'OFICINA') {
          next({ path: '/oficina' });
        } else if (userType === 'MONITOR') {
          next({ path: '/monitor' });
        } else {
          next({ path: '/' });
        }
        return;
      }
    } else {
      try {
        const freshUser = await authService.getCurrentUser();
        if (freshUser?.tipo) {
          const userType = freshUser.tipo.toUpperCase();
          const requiredType = requiredRole.toUpperCase();
          
          if (userType !== requiredType) {
            if (userType === 'OFICINA') {
              next({ path: '/oficina' });
            } else if (userType === 'MONITOR') {
              next({ path: '/monitor' });
            } else {
              next({ path: '/' });
            }
            return;
          }
        } else {
          authService.clearAuth();
          next({ path: '/' });
          return;
        }
      } catch (e) {
        authService.clearAuth();
        next({ path: '/' });
        return;
      }
    }
  }

  if (isPublic && isAuthenticated && to.path === '/') {
    const userData = authService.getUserData();
    if (userData?.tipo) {
      const userType = userData.tipo.toUpperCase();
      if (userType === 'OFICINA') {
        next({ path: '/oficina' });
        return;
      } else if (userType === 'MONITOR') {
        next({ path: '/monitor' });
        return;
      }
    }
  }

  next();
});

createApp(App).use(router).mount('#app');
