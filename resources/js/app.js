import './bootstrap'

import { createApp } from 'vue'
import { createPinia } from 'pinia'

import Vue3Toastify from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'

import router from './router/index.js'
import App from './App.vue'

const app = createApp(App)

app.use(router)
app.use(createPinia())
app.use(Vue3Toastify, {
    autoClose: 3000,
    position: 'top-right',
})

app.mount('#app')
