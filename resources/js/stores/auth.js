import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'
import { useCartStore } from './cart'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token'))
  const user = ref(null)
  const isLoggedIn = computed(() => !!token.value)

  function setSession(data) {
    token.value = data.token
    user.value = data.user
    localStorage.setItem('token', data.token)
  }

  async function bootstrap() {
    if (!token.value) return
    try {
      user.value = (await api.get('/auth/me')).data
      await useCartStore().load()
    } catch {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
    }
  }

  async function login(email, password) {
    setSession((await api.post('/auth/login', { email, password })).data)
    await useCartStore().mergeGuestCart()
  }

  async function register(form) {
    setSession((await api.post('/auth/register', form)).data)
    await useCartStore().mergeGuestCart()
  }

  async function logout() {
    try { await api.post('/auth/logout') } catch { /* token may already be invalid */ }
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    useCartStore().items = []
  }

  return { token, user, isLoggedIn, bootstrap, login, register, logout }
})
