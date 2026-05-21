import { createApp } from 'vue';
import { createRouter, createWebHashHistory } from 'vue-router';
import App from './App.vue';
import Login from './Pages/Login.vue';
import CrearC from './Pages/CrearC.vue';
import Oficina from './Pages/Oficina.vue';
import Monitor from './Pages/Monitor.vue';
import './services/api';

const routes = [
  { path: '/', component: Login },
  { path: '/crear-cuenta', component: CrearC },
  { path: '/oficina', component: Oficina },
  { path: '/monitor', component: Monitor }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes,
});

createApp(App).use(router).mount('#app');
