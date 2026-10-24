<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError } from '../api'
import { safeRedirect } from '../utils/money'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')
const busy = ref(false)

async function submit() {
  busy.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push(safeRedirect(route.query.redirect))
  } catch (e) {
    error.value = apiError(e, 'Sign in failed. Try again.')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="narrow">
    <h1>Sign in</h1>
    <form class="stack" @submit.prevent="submit">
      <label>Email <input v-model="email" type="email" required autocomplete="email" /></label>
      <label>Password <input v-model="password" type="password" required autocomplete="current-password" /></label>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <button type="submit" :disabled="busy">{{ busy ? 'Signing in…' : 'Sign in' }}</button>
      <p class="muted">New here? <RouterLink :to="{ name: 'register', query: route.query }">Create an account</RouterLink></p>
    </form>
  </section>
</template>
