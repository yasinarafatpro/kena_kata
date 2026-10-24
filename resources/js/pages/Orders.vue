<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import { formatMoney } from '../utils/money'

const orders = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const page = ref(1)
const loading = ref(true)
const error = ref('')

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/orders', { params: { page: page.value } })
    orders.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page }
  } catch {
    error.value = 'Your orders could not be loaded.'
  } finally {
    loading.value = false
  }
}
const go = (n) => { page.value = n; load() }
onMounted(load)
</script>

<template>
  <section>
    <h1>My orders</h1>
    <p v-if="loading" class="muted">Loading…</p>
    <p v-else-if="error" class="error">{{ error }}</p>
    <p v-else-if="!orders.length">You haven't placed an order yet. <RouterLink to="/">Browse the shop</RouterLink></p>

    <div v-else class="tablewrap">
      <table>
        <thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td><RouterLink :to="{ name: 'order', params: { id: o.id } }">#{{ o.id }}</RouterLink></td>
            <td>{{ new Date(o.created_at).toLocaleDateString() }}</td>
            <td>{{ o.items.reduce((n, i) => n + i.quantity, 0) }}</td>
            <td>{{ formatMoney(o.total_amount, o.currency) }}</td>
            <td><span class="pill" :class="o.status">{{ o.status }}</span></td>
          </tr>
        </tbody>
      </table>
    </div>

    <nav v-if="meta.last_page > 1" class="pager">
      <button class="ghost" :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)">Previous</button>
      <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button class="ghost" :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)">Next</button>
    </nav>
  </section>
</template>
