<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import api from '../api'
import { useCartStore } from '../stores/cart'
import { formatMoney } from '../utils/money'

const cart = useCartStore()
const products = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const categories = ref([])
const loading = ref(false)
const error = ref('')
const addedId = ref(null)
const filters = reactive({ search: '', category: '', min_price: '', max_price: '', in_stock: false, sort: 'newest', page: 1 })
let timer

async function fetchProducts() {
  loading.value = true
  error.value = ''
  try {
    const params = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== false))
    const { data } = await api.get('/products', { params })
    products.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page }
  } catch {
    error.value = 'Products could not be loaded. Refresh the page to try again.'
  } finally {
    loading.value = false
  }
}

async function addToCart(p) {
  try {
    await cart.add(p)
    addedId.value = p.id
    setTimeout(() => { if (addedId.value === p.id) addedId.value = null }, 1500)
  } catch (e) {
    error.value = e.response?.data?.errors?.product_id?.[0] ?? 'That item could not be added.'
  }
}

watch(() => [filters.search, filters.category, filters.min_price, filters.max_price, filters.in_stock, filters.sort], () => {
  clearTimeout(timer)
  timer = setTimeout(() => (filters.page === 1 ? fetchProducts() : (filters.page = 1)), 300)
})
watch(() => filters.page, fetchProducts)

onMounted(async () => {
  try { categories.value = (await api.get('/categories')).data } catch { /* filters still work without it */ }
  fetchProducts()
})
</script>

<template>
  <section class="catalog">
    <aside class="filters" aria-label="Filters">
      <h2>Filter</h2>
      <label>Search
        <input v-model="filters.search" type="search" placeholder="Product name" />
      </label>
      <label>Category
        <select v-model="filters.category">
          <option value="">All categories</option>
          <template v-for="c in categories" :key="c.id">
            <option :value="c.slug">{{ c.name }}</option>
            <option v-for="s in c.children" :key="s.id" :value="s.slug">&nbsp;&nbsp;{{ s.name }}</option>
          </template>
        </select>
      </label>
      <div class="row">
        <label>Min price <input v-model="filters.min_price" type="number" min="0" /></label>
        <label>Max price <input v-model="filters.max_price" type="number" min="0" /></label>
      </div>
      <label class="inline"><input v-model="filters.in_stock" type="checkbox" /> In stock only</label>
      <label>Sort by
        <select v-model="filters.sort">
          <option value="newest">Newest</option>
          <option value="price_asc">Price, low to high</option>
          <option value="price_desc">Price, high to low</option>
          <option value="name">Name</option>
        </select>
      </label>
    </aside>

    <div>
      <p v-if="error" class="error" role="alert">{{ error }}</p>
      <p v-else-if="loading" class="muted">Loading products…</p>
      <p v-else-if="!products.length">No products match these filters. Clear a filter to see more.</p>

      <div class="grid">
        <article v-for="p in products" :key="p.id" class="card">
          <RouterLink :to="{ name: 'product', params: { slug: p.slug } }">
            <img :src="p.image_url || '/placeholder.svg'" :alt="p.name" loading="lazy" />
            <h3>{{ p.name }}</h3>
          </RouterLink>
          <p class="muted" style="margin: 0">{{ p.category?.name }}</p>
          <p class="price" style="margin: 0">{{ formatMoney(p.price) }}</p>
          <button :disabled="p.stock < 1" @click="addToCart(p)">
            {{ p.stock < 1 ? 'Out of stock' : addedId === p.id ? 'Added' : 'Add to cart' }}
          </button>
        </article>
      </div>

      <nav v-if="meta.last_page > 1" class="pager" aria-label="Pages">
        <button class="ghost" :disabled="filters.page <= 1" @click="filters.page--">Previous</button>
        <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
        <button class="ghost" :disabled="filters.page >= meta.last_page" @click="filters.page++">Next</button>
      </nav>
    </div>
  </section>
</template>
