import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from './stores/auth'

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/', name: 'catalog', component: () => import('./pages/ProductCatalog.vue') },
    { path: '/products/:slug', name: 'product', component: () => import('./pages/ProductDetail.vue') },
    { path: '/cart', name: 'cart', component: () => import('./pages/Cart.vue') },
    { path: '/checkout', name: 'checkout', component: () => import('./pages/Checkout.vue'), meta: { auth: true } },
    { path: '/orders', name: 'orders', component: () => import('./pages/Orders.vue'), meta: { auth: true } },
    { path: '/orders/:id', name: 'order', component: () => import('./pages/OrderDetail.vue'), meta: { auth: true } },
    { path: '/login', name: 'login', component: () => import('./pages/Login.vue') },
    { path: '/register', name: 'register', component: () => import('./pages/Register.vue') },
    { path: '/admin/products', name: 'admin-products', component: () => import('./pages/admin/AdminProducts.vue'), meta: { auth: true, admin: true } },
    { path: '/admin/orders', name: 'admin-orders', component: () => import('./pages/admin/AdminOrders.vue'), meta: { auth: true, admin: true } },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.auth && !auth.isLoggedIn) return { name: 'login', query: { redirect: to.fullPath } }
  if (to.meta.admin && auth.user?.role !== 'admin') return { name: 'catalog' }
})

export default router
