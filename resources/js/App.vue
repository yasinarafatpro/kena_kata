<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'
import { useCartStore } from './stores/cart'

const auth = useAuthStore()
const cart = useCartStore()
const router = useRouter()
const appName = import.meta.env.VITE_APP_NAME || 'Shop'

async function signOut() {
  await auth.logout()
  router.push('/')
}
</script>

<template>
  <header class="top">
    <RouterLink to="/" class="brand">{{ appName }}</RouterLink>
    <nav aria-label="Main">
      <RouterLink to="/">Shop</RouterLink>
      <RouterLink to="/cart">Cart<span v-if="cart.count" class="badge">{{ cart.count }}</span></RouterLink>
      <template v-if="auth.isLoggedIn">
        <RouterLink to="/orders">My orders</RouterLink>
        <RouterLink v-if="auth.user?.role === 'admin'" to="/admin/products">Admin</RouterLink>
        <button class="link" @click="signOut">Sign out</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Sign in</RouterLink>
        <RouterLink to="/register">Create account</RouterLink>
      </template>
    </nav>
  </header>

  <main><RouterView /></main>

  <footer>© {{ new Date().getFullYear() }} {{ appName }}</footer>
</template>
