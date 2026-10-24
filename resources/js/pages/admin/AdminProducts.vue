<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import api, { apiError, fieldErrors } from '../../api'
import { formatMoney } from '../../utils/money'

const products = ref([])
const meta = ref({ current_page: 1, last_page: 1 })
const categories = ref([])
const loading = ref(true)
const message = ref('')
const error = ref('')
const errors = ref({})
const editingId = ref(null)
const blank = () => ({ category_id: '', name: '', description: '', price: '', stock: 0, image_url: '', is_active: true })
const form = reactive(blank())
const newCat = reactive({ name: '', parent_id: '' })

const flatCategories = computed(() =>
  categories.value.flatMap((c) => [{ id: c.id, name: c.name }, ...(c.children ?? []).map((s) => ({ id: s.id, name: `${c.name} / ${s.name}` }))]))

async function loadCategories() { categories.value = (await api.get('/categories')).data }

async function load(page = 1) {
  loading.value = true
  try {
    const { data } = await api.get('/admin/products', { params: { page } })
    products.value = data.data
    meta.value = { current_page: data.current_page, last_page: data.last_page }
  } finally {
    loading.value = false
  }
}

function edit(p) {
  editingId.value = p.id
  Object.assign(form, { category_id: p.category_id, name: p.name, description: p.description ?? '', price: p.price, stock: p.stock, image_url: p.image_url ?? '', is_active: p.is_active })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function reset() {
  editingId.value = null
  Object.assign(form, blank())
  errors.value = {}
}

async function save() {
  error.value = ''
  message.value = ''
  errors.value = {}
  const payload = { ...form, image_url: form.image_url || null }
  try {
    if (editingId.value) await api.put(`/admin/products/${editingId.value}`, payload)
    else await api.post('/admin/products', payload)
    message.value = editingId.value ? 'Product updated.' : 'Product created.'
    reset()
    await load(meta.value.current_page)
  } catch (e) {
    error.value = apiError(e, 'The product could not be saved.')
    errors.value = fieldErrors(e)
  }
}

async function retire(p) {
  if (!confirm(`Hide "${p.name}" from the shop?`)) return
  await api.delete(`/admin/products/${p.id}`)
  await load(meta.value.current_page)
}

async function addCategory() {
  error.value = ''
  try {
    await api.post('/admin/categories', { name: newCat.name, parent_id: newCat.parent_id || null })
    newCat.name = ''
    newCat.parent_id = ''
    await loadCategories()
    message.value = 'Category added.'
  } catch (e) {
    error.value = apiError(e, 'The category could not be added.')
  }
}

onMounted(async () => { await loadCategories(); await load() })
</script>

<template>
  <section class="stack">
    <nav class="admin-tabs"><RouterLink to="/admin/products">Products</RouterLink><RouterLink to="/admin/orders">Orders</RouterLink></nav>
    <h1>Products</h1>

    <form class="panel stack" @submit.prevent="save">
      <h2>{{ editingId ? 'Edit product' : 'New product' }}</h2>
      <label>Name <input v-model="form.name" required maxlength="255" /></label>
      <label>Category
        <select v-model="form.category_id" required>
          <option value="" disabled>Choose a category</option>
          <option v-for="c in flatCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </label>
      <div class="row">
        <label>Price <input v-model="form.price" type="number" step="0.01" min="0" required /></label>
        <label>Stock <input v-model.number="form.stock" type="number" min="0" required /></label>
      </div>
      <label>Image URL (optional) <input v-model="form.image_url" type="url" placeholder="https://" /></label>
      <label>Description <textarea v-model="form.description" /></label>
      <label class="inline"><input v-model="form.is_active" type="checkbox" /> Visible in the shop</label>
      <ul v-if="Object.keys(errors).length" class="error"><li v-for="(m, k) in errors" :key="k">{{ m[0] }}</li></ul>
      <p v-else-if="error" class="error" role="alert">{{ error }}</p>
      <p v-if="message" class="notice" role="status">{{ message }}</p>
      <div class="row">
        <button type="submit">{{ editingId ? 'Save changes' : 'Create product' }}</button>
        <button v-if="editingId" type="button" class="ghost" @click="reset">Cancel edit</button>
      </div>
    </form>

    <form class="panel row" @submit.prevent="addCategory">
      <h2 style="width: 100%">Add a category</h2>
      <label style="flex: 1">Name <input v-model="newCat.name" required maxlength="120" /></label>
      <label style="flex: 1">Parent (optional)
        <select v-model="newCat.parent_id">
          <option value="">Top level</option>
          <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </label>
      <button type="submit">Add category</button>
    </form>

    <p v-if="loading" class="muted">Loading…</p>
    <div v-else class="tablewrap">
      <table>
        <thead><tr><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Visible</th><th></th></tr></thead>
        <tbody>
          <tr v-for="p in products" :key="p.id">
            <td>{{ p.name }}</td><td>{{ p.category?.name }}</td><td>{{ formatMoney(p.price) }}</td>
            <td>{{ p.stock }}</td><td>{{ p.is_active ? 'Yes' : 'No' }}</td>
            <td class="row">
              <button class="ghost small" @click="edit(p)">Edit</button>
              <button v-if="p.is_active" class="danger small" @click="retire(p)">Hide</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <nav v-if="meta.last_page > 1" class="pager">
      <button class="ghost" :disabled="meta.current_page <= 1" @click="load(meta.current_page - 1)">Previous</button>
      <span>Page {{ meta.current_page }} of {{ meta.last_page }}</span>
      <button class="ghost" :disabled="meta.current_page >= meta.last_page" @click="load(meta.current_page + 1)">Next</button>
    </nav>
  </section>
</template>
