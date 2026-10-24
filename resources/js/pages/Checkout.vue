<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { loadStripe } from '@stripe/stripe-js'
import api, { apiError, fieldErrors } from '../api'
import { useCartStore } from '../stores/cart'
import { formatMoney } from '../utils/money'

const cart = useCartStore()
const router = useRouter()

// idle → creating → awaiting_payment (card) → confirming
const state = ref('idle')
const methods = ref([])
const method = ref('')
const error = ref('')
const errors = ref({})
const orderId = ref(null)
const orderTotal = ref(0)
const orderCurrency = ref(undefined)
const paymentEl = ref(null)
const form = reactive({ name: '', phone: '', line1: '', line2: '', city: '', postal_code: '', country: import.meta.env.VITE_DEFAULT_COUNTRY || '' })

const labels = { cod: 'Cash on delivery', stripe: 'Card (Stripe)', paypal: 'PayPal' }
const submitLabel = () =>
  state.value === 'creating' ? 'Preparing…' : method.value === 'paypal' ? 'Continue to PayPal' : method.value === 'stripe' ? 'Continue to card payment' : 'Place order'

let stripe, elements

onMounted(async () => {
  await cart.load()
  if (!cart.items.length) return router.replace({ name: 'cart' })
  try {
    const { data } = await api.get('/payment-methods')
    methods.value = data.methods
    method.value = data.methods[0] ?? ''
  } catch {
    error.value = 'Payment methods could not be loaded. Refresh the page to try again.'
  }
})

async function startCheckout() {
  error.value = ''
  errors.value = {}
  state.value = 'creating'
  try {
    const { data } = await api.post('/checkout', { payment_method: method.value, shipping: { ...form } })
    orderId.value = data.order_id
    orderTotal.value = data.total
    orderCurrency.value = data.currency
    await cart.load()   // the server emptied the cart when the order was created

    if (method.value === 'cod') return router.push({ name: 'order', params: { id: data.order_id } })
    if (method.value === 'paypal') return window.location.assign(data.payment.redirect_url)

    stripe = await loadStripe(import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY)
    elements = stripe.elements({ clientSecret: data.payment.client_secret })
    elements.create('payment').mount(paymentEl.value)
    state.value = 'awaiting_payment'
  } catch (e) {
    state.value = 'idle'
    error.value = apiError(e, 'Checkout failed. Please try again.')
    errors.value = fieldErrors(e)
  }
}

async function confirmCard() {
  state.value = 'confirming'
  error.value = ''
  const { error: err } = await stripe.confirmPayment({
    elements,
    confirmParams: { return_url: `${window.location.origin}/orders/${orderId.value}` },
  })
  // Only reached on an immediate failure; on success Stripe redirects to the order page.
  if (err) {
    error.value = err.message
    state.value = 'awaiting_payment'
  }
}
</script>

<template>
  <section class="narrow">
    <h1>Checkout</h1>

    <form v-show="state === 'idle' || state === 'creating'" class="stack" @submit.prevent="startCheckout">
      <h2>Shipping address</h2>
      <label>Full name <input v-model="form.name" required autocomplete="name" /></label>
      <label>Phone <input v-model="form.phone" required autocomplete="tel" /></label>
      <label>Address <input v-model="form.line1" required autocomplete="address-line1" /></label>
      <label>Address line 2 (optional) <input v-model="form.line2" autocomplete="address-line2" /></label>
      <label>City <input v-model="form.city" required autocomplete="address-level2" /></label>
      <label>Postal code <input v-model="form.postal_code" required autocomplete="postal-code" /></label>
      <label>Country code (2 letters, e.g. US)
        <input v-model="form.country" required minlength="2" maxlength="2" style="text-transform: uppercase" autocomplete="country" />
      </label>

      <h2>Payment method</h2>
      <label v-for="m in methods" :key="m" class="inline">
        <input v-model="method" type="radio" name="method" :value="m" /> {{ labels[m] ?? m }}
      </label>

      <ul v-if="Object.keys(errors).length" class="error">
        <li v-for="(msgs, key) in errors" :key="key">{{ msgs[0] }}</li>
      </ul>

      <p class="price">Items: {{ formatMoney(cart.subtotal) }}</p>
      <button type="submit" :disabled="state === 'creating' || !method">{{ submitLabel() }}</button>
    </form>

    <div v-show="state === 'awaiting_payment' || state === 'confirming'" class="stack">
      <h2>Pay securely</h2>
      <div ref="paymentEl" />
      <button :disabled="state === 'confirming'" @click="confirmCard">
        {{ state === 'confirming' ? 'Processing payment…' : `Pay ${formatMoney(orderTotal, orderCurrency)}` }}
      </button>
    </div>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
  </section>
</template>
