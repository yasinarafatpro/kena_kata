import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'
import { useAuthStore } from './auth'

const GUEST_KEY = 'guest_cart'

function readGuest() {
  try { return JSON.parse(localStorage.getItem(GUEST_KEY) || '[]') } catch { return [] }
}

// Signed in → the cart lives in MySQL. Guest → it lives in localStorage and is merged after sign-in.
export const useCartStore = defineStore('cart', () => {
  const auth = useAuthStore()
  const items = ref(readGuest())
  const saveGuest = () => localStorage.setItem(GUEST_KEY, JSON.stringify(items.value))

  const count = computed(() => items.value.reduce((n, i) => n + i.quantity, 0))
  const subtotal = computed(() => items.value.reduce((s, i) => s + Number(i.product.price) * i.quantity, 0))

  async function load() {
    if (auth.isLoggedIn) items.value = (await api.get('/cart')).data
  }

  async function add(product, quantity = 1) {
    if (auth.isLoggedIn) {
      await api.post('/cart', { product_id: product.id, quantity })
      return load()
    }
    const line = items.value.find((i) => i.product_id === product.id)
    if (line) {
      line.quantity = Math.min(line.quantity + quantity, product.stock)
    } else {
      items.value.push({
        product_id: product.id,
        quantity: Math.min(quantity, product.stock),
        product: { id: product.id, name: product.name, slug: product.slug, price: product.price, stock: product.stock, image_url: product.image_url },
      })
    }
    saveGuest()
  }

  async function setQty(item, quantity) {
    quantity = Math.max(1, Math.min(quantity || 1, item.product.stock))
    if (auth.isLoggedIn) await api.patch(`/cart/${item.id}`, { quantity })
    item.quantity = quantity
    if (!auth.isLoggedIn) saveGuest()
  }

  async function remove(item) {
    if (auth.isLoggedIn) await api.delete(`/cart/${item.id}`)
    items.value = items.value.filter((i) => i.product_id !== item.product_id)
    if (!auth.isLoggedIn) saveGuest()
  }

  async function mergeGuestCart() {
    const guest = readGuest()
    if (guest.length) {
      await api.post('/cart/merge', { items: guest.map(({ product_id, quantity }) => ({ product_id, quantity })) })
      localStorage.removeItem(GUEST_KEY)
    }
    await load()
  }

  return { items, count, subtotal, load, add, setQty, remove, mergeGuestCart }
})
