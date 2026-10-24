import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App).use(createPinia()).use(router)

// Restore the session (and cart) before the first render so route guards see the user.
useAuthStore().bootstrap().finally(() => app.mount('#app'))
