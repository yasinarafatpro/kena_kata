<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import api, { apiError } from '../../api'
import { formatMoney } from '../../utils/money'

const orders = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const status = ref('')
const loading = ref(true)
const error = ref('')
const drafts = reactive({})   // per-order { next, note }

async function load(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const { data } = await api.get('/admin/orders', { params: { page, status: status.value || undefined } })
    orders.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page }
    data.data.forEach((o) => { drafts[o.id] = { next: o.allowed_next[0] ?? '', note: '' } })
  } catch (e) {
    error.value = apiError(e, 'Orders could not be loaded.')
  } finally {
    loading.value = false
  }
}

async function update(o) {
  const d = drafts[o.id]
  if (!d.next) return
  if (d.next === 'cancelled' && !confirm(`Cancel order #${o.id} and return its stock?`)) return
  error.value = ''
  try {
    await api.patch(`/admin/orders/${o.id}/status`, { status: d.next, note: d.note || null })
    await load(meta.value.current_page)
  } catch (e) {
    error.value = apiError(e, 'The status could not be updated.')
  }
}

watch(status, () => load(1))
onMounted(() => load())
</script>

<template>
  <section class="stack">
    <nav class="admin-tabs"><RouterLink to="/admin/products">Products</RouterLink><RouterLink to="/admin/orders">Orders</RouterLink></nav>
    <h1>Orders</h1>
    <label style="max-width: 14rem">Filter by status
      <select v-model="status">
        <option value="">All</option>
        <option v-for="s in ['pending', 'paid', 'shipped', 'delivered', 'cancelled']" :key="s" :value="s">{{ s }}</option>
      </select>
    </label>
    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="loading" class="muted">Loading…</p>

    <div v-else class="tablewrap">
      <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Update</th></tr></thead>
        <tbody>
          <tr v-for="o in orders" :key="o.id">
            <td>#{{ o.id }}<br /><small class="muted">{{ new Date(o.created_at).toLocaleDateString() }}</small></td>
            <td>{{ o.user?.name }}<br /><small class="muted">{{ o.user?.email }}</small><br />
              <small>{{ o.shipping_address.city }}, {{ o.shipping_address.country }}, {{ o.shipping_address.phone }}</small></td>
            <td><div v-for="i in o.items" :key="i.id">{{ i.quantity }} × {{ i.product_name }}</div></td>
            <td>{{ formatMoney(o.total_amount, o.currency) }}</td>
            <td>{{ o.payment_method }}</td>
            <td><span class="pill" :class="o.status">{{ o.status }}</span></td>
            <td>
              <div v-if="o.allowed_next.length" class="stack" style="min-width: 11rem">
                <select v-model="drafts[o.id].next" :aria-label="`New status for order ${o.id}`">
                  <option v-for="s in o.allowed_next" :key="s" :value="s">{{ s }}</option>
                </select>
                <input v-model="drafts[o.id].note" placeholder="Note (optional)" maxlength="255" />
                <button class="small" @click="update(o)">Update status</button>
              </div>
              <span v-else class="muted">Final</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="!loading && !orders.length" class="muted">No orders match this filter.</p>

    <nav v-if="meta.last_page > 1" class="pager">
      <button class="ghost" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button class="ghost" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </nav>
  </section>
</template>
