<script setup>
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'
import { formatMoney } from '../utils/money'

const cart = useCartStore()
const auth = useAuthStore()
const router = useRouter()

const proceed = () =>
  router.push(auth.isLoggedIn ? { name: 'checkout' } : { name: 'login', query: { redirect: '/checkout' } })
</script>

<template>
  <section>
    <h1>Your cart</h1>
    <p v-if="!cart.items.length">Your cart is empty. <RouterLink to="/">Browse the shop</RouterLink></p>

    <template v-else>
      <ul class="lines">
        <li v-for="item in cart.items" :key="item.product_id">
          <img :src="item.product.image_url || '/placeholder.svg'" :alt="item.product.name" />
          <RouterLink :to="{ name: 'product', params: { slug: item.product.slug } }">{{ item.product.name }}</RouterLink>
          <span>{{ formatMoney(item.product.price) }}</span>
          <input type="number" min="1" :max="item.product.stock" :value="item.quantity"
                 :aria-label="`Quantity for ${item.product.name}`"
                 @change="cart.setQty(item, Number($event.target.value))" />
          <strong>{{ formatMoney(item.product.price * item.quantity) }}</strong>
          <button class="ghost small" :aria-label="`Remove ${item.product.name}`" @click="cart.remove(item)">Remove</button>
        </li>
      </ul>

      <div class="summary">
        <p style="margin: 0">Subtotal: <strong>{{ formatMoney(cart.subtotal) }}</strong></p>
        <small class="muted">The final total is confirmed from current prices at checkout.</small>
        <button @click="proceed">Proceed to checkout</button>
      </div>
    </template>
  </section>
</template>
