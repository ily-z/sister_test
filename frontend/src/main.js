import { createApp } from 'vue'
import { createPinia } from 'pinia'

import 'material-symbols/outlined.css'
import '@fontsource/inter/400.css'
import '@fontsource/inter/500.css'
import '@fontsource/inter/600.css'
import '@fontsource/inter/700.css'
import '@fontsource/inter/800.css'
import './assets/main.css'
import './assets/invoice-print.css'

import App from './App.vue'
import router from './router'

/*
 * Urutan di bawah penting.
 *
 * createPinia() harus dipasang sebelum router dipakai, karena router guard
 * memanggil auth store. Kalau router di-mount lebih dulu, guard akan
 * menemukan store yang belum ada dan melempar error
 * "getActivePinia() was called but there was no active Pinia".
 */

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
