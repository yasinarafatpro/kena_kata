<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { apiError, fieldErrors } from '../api'
import { safeRedirect } from '../utils/money'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' })
const error = ref('')
const errors = ref({})
const busy = ref(false)

async function submit() {
  busy.value = true
  error.value = ''
  errors.value = {}
  try {
    await auth.register({ ...form })
    router.push(safeRedirect(route.query.redirect))
  } catch (e) {
    error.value = apiError(e, 'Could not create your account.')
    errors.value = fieldErrors(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section class="narrow">
    <h1>Create an account</h1>
    <form class="stack" @submit.prevent="submit">
      <label>Name <input v-model="form.name" required autocomplete="name" /></label>
      <label>Email <input v-model="form.email" type="email" required autocomplete="email" /></label>
      <label>Password (10+ characters, upper and lower case, a number)
        <input v-model="form.password" type="password" required minlength="10" autocomplete="new-password" />
      </label>
      <label>Confirm password
        <input v-model="form.password_confirmation" type="password" required autocomplete="new-password" />
      </label>
      <ul v-if="Object.keys(errors).length" class="error">
        <li v-for="(msgs, key) in errors" :key="key">{{ msgs[0] }}</li>
      </ul>
      <p v-else-if="error" class="error" role="alert">{{ error }}</p>
      <button type="submit" :disabled="busy">{{ busy ? 'Creating account…' : 'Create account' }}</button>
      <p class="muted">Already registered? <RouterLink :to="{ name: 'login', query: route.query }">Sign in</RouterLink></p>
    </form>
  </section>
</template>
