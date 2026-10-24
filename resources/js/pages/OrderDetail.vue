<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import { formatMoney } from '../utils/money'

const route = useRoute()
const order = ref(null)
const error = ref('')
let timer
let tries = 0

async function load() {
  try {
    order.value = (await api.get(`/orders/${route.params.id}`)).data
    // Card and PayPal payments are confirmed asynchronously by a webhook; poll briefly while pending.
    const online = order.value.payment_method !== 'cod'
    if (order.value.status === 'pending' && online && tries++ < 20) timer = setTimeout(load, 3000)
  } catch {
    error.value = 'This order could not be found.'
  }
}
onMounted(load)
onUnmounted(() => clearTimeout(timer))
</script>

<template>
  <p v-if="error" class="error">{{ error }}</p>
  <p v-else-if="!order" class="muted">Loading…</p>

  <section v-else class="stack">
    <h1>Order #{{ order.id }}</h1>
    <p>
      Status: <span class="pill" :class="order.status">{{ order.status }}</span>
      <span v-if="order.status === 'pending' && order.payment_method !== 'cod'" class="muted" role="status">
        Confirming your payment…
      </span>
      <span v-if="order.status === 'pending' && order.payment_method === 'cod'" class="muted">
        We'll confirm your order shortly. Pay in cash on delivery.
      </span>
    </p>

    <div class="tablewrap">
      <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
        <tbody>
          <tr v-for="i in order.items" :key="i.id">
            <td>{{ i.product_name }}</td><td>{{ i.quantity }}</td><td>{{ formatMoney(i.price, order.currency) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="price">Total: {{ formatMoney(order.total_amount, order.currency) }}</p>

    <div class="panel">
      <h2>Ship to</h2>
      <p style="margin: 0">
        {{ order.shipping_address.name }}, {{ order.shipping_address.phone }}<br />
        {{ order.shipping_address.line1 }} {{ order.shipping_address.line2 }}<br />
        {{ order.shipping_address.city }} {{ order.shipping_address.postal_code }}, {{ order.shipping_address.country }}
      </p>
    </div>

    <div class="panel">
      <h2>History</h2>
      <ol style="margin: 0; padding-left: 1.2rem">
        <li v-for="h in order.status_histories" :key="h.id">
          <strong>{{ h.status }}</strong> <span v-if="h.note">: {{ h.note }}</span>
          <span class="muted"> ({{ new Date(h.created_at).toLocaleString() }})</span>
        </li>
      </ol>
    </div>
  </section>
</template>
