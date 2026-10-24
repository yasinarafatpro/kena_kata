<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import api, { apiError } from '../api'
import { useCartStore } from '../stores/cart'
import { formatMoney } from '../utils/money'

const route = useRoute()
const cart = useCartStore()
const product = ref(null)
const quantity = ref(1)
const loading = ref(true)
const error = ref('')
const added = ref(false)

watch(() => route.params.slug, async (slug) => {
  loading.value = true
  error.value = ''
  added.value = false
  product.value = null
  try {
    product.value = (await api.get(`/products/${slug}`)).data
    quantity.value = 1
  } catch {
    error.value = 'This product is no longer available.'
  } finally {
    loading.value = false
  }
}, { immediate: true })

async function addToCart() {
  error.value = ''
  try {
    await cart.add(product.value, Math.max(1, quantity.value || 1))
    added.value = true
  } catch (e) {
    error.value = e.response?.data?.errors?.product_id?.[0] ?? apiError(e, 'Could not add this item to your cart.')
  }
}
</script>

<template>
  <p v-if="loading" class="muted">Loading…</p>
  <p v-else-if="!product" class="error">{{ error }} <RouterLink to="/">Back to the shop</RouterLink></p>

  <article v-else class="detail">
    <img :src="product.image_url || '/placeholder.svg'" :alt="product.name" />
    <div class="stack">
      <p class="muted" style="margin: 0">{{ product.category?.name }}</p>
      <h1>{{ product.name }}</h1>
      <p class="price" style="font-size: 1.5rem; margin: 0">{{ formatMoney(product.price) }}</p>
      <!-- Rendered as text (never v-html) so product copy can't inject script. -->
      <p class="desc">{{ product.description }}</p>

      <p v-if="product.stock < 1" class="error">Out of stock</p>
      <template v-else>
        <p v-if="product.stock <= 5" class="muted">Only {{ product.stock }} left</p>
        <div class="row">
          <label style="width: 6rem">Quantity
            <input v-model.number="quantity" type="number" min="1" :max="product.stock" />
          </label>
          <button @click="addToCart">Add to cart</button>
        </div>
      </template>

      <p v-if="added" class="notice" role="status">Added to your cart. <RouterLink :to="{ name: 'cart' }">View cart</RouterLink></p>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
    </div>
  </article>
</template>
