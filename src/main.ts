import { createApp } from 'vue'
import './assets/main.css'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import { useCoursesStore } from './stores/courses'
import { createPinia } from 'pinia'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)
app.use(router)

// route guard: protect routes with meta.requiresTeacher
router.beforeEach((to, _from, next) => {
  const auth = useAuthStore(pinia)
  if (to.meta?.requiresTeacher && !auth.isTeacher()) {
    return next('/login')
  }
  next()
})

// Bootstrap: load the catalogue from the API (and refresh the profile if a
// token is present) before mounting, so synchronous getCourse() calls resolve.
async function bootstrap() {
  const courses = useCoursesStore(pinia)
  const auth = useAuthStore(pinia)

  const tasks: Promise<unknown>[] = [
    courses.fetchCourses().catch((e) => console.error('Échec du chargement des cours', e)),
  ]
  if (auth.token) {
    tasks.push(auth.fetchMe().catch(() => auth.logout()))
  }
  await Promise.all(tasks)

  app.mount('#app')
}

bootstrap()
