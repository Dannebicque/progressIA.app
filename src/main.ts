import { createApp } from 'vue'
import './assets/main.css'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import { createPinia } from 'pinia'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)
app.use(router)

// route guard: protect routes with meta.requiresTeacher
router.beforeEach((to, from, next) => {
	const auth = useAuthStore(pinia)
	if (to.meta?.requiresTeacher && !auth.isTeacher()) {
		return next('/login')
	}
	next()
})

app.mount('#app')
