import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', component: () => import('../pages/Home.vue') },
    { path: '/courses', component: () => import('../pages/Courses.vue') },
    { path: '/course/:id', component: () => import('../pages/Course.vue') },
    { path: '/course/:id/session/:sid', component: () => import('../pages/Session.vue') },
    { path: '/backoffice', component: () => import('../pages/Backoffice.vue'), meta: { requiresTeacher: true } },
    { path: '/login', component: () => import('../pages/Login.vue') },
    { path: '/dashboard/student', component: () => import('../pages/StudentDashboard.vue') },
    { path: '/dashboard/teacher', component: () => import('../pages/TeacherDashboard.vue') },
    { path: '/:pathMatch(.*)*', component: () => import('../pages/NotFound.vue') },
  ],
})

export default router
