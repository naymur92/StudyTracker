import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import router from '@/router'
import App from '@/App.vue'
import { resolveApiBaseUrl } from '@/config/urls'
import '../css/app.css'
import 'sweetalert2/dist/sweetalert2.min.css'
import '@fortawesome/fontawesome-free/css/all.min.css'

const app = createApp(App)

const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)

app.use(pinia)
app.use(router)

app.config.globalProperties.$api = resolveApiBaseUrl()

app.mount('#app')
