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

// Bootstrap: restore the profile if a token is present, and preload the
// catalogue, before mounting. The global navigation guard (in router/index.ts)
// enforces authentication for every non-public route.
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
