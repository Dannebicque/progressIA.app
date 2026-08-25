import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', component: () => import('../pages/Home.vue') },
    { path: '/courses', component: () => import('../pages/Courses.vue') },
    { path: '/course/:id', component: () => import('../pages/Course.vue') },
    { path: '/course/:id/session/:sid', component: () => import('../pages/Session.vue') },
    { path: '/backoffice', component: () => import('../pages/Backoffice.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/courses', component: () => import('../pages/BackofficeCourses.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/courses/:id/tracking', component: () => import('../pages/BackofficeCourseTracking.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/courses/:id/storytelling', component: () => import('../pages/BackofficeCourseStorytelling.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/users', component: () => import('../pages/BackofficeUsers.vue'), meta: { requiresSchoolAdmin: true } },
    { path: '/backoffice/students', component: () => import('../pages/BackofficeStudents.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/students/:id', component: () => import('../pages/BackofficeStudentSheet.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/courses/:courseId/students/:id', component: () => import('../pages/BackofficeStudentSheet.vue'), meta: { requiresTeacher: true } },
    { path: '/backoffice/semesters', component: () => import('../pages/BackofficeSemesters.vue'), meta: { requiresSchoolAdmin: true } },
    { path: '/backoffice/formations', component: () => import('../pages/BackofficeFormations.vue'), meta: { requiresSchoolAdmin: true } },
    { path: '/backoffice/institutions', component: () => import('../pages/BackofficeInstitutions.vue'), meta: { requiresSuperAdmin: true } },
    { path: '/backoffice/institutions/:id', component: () => import('../pages/BackofficeInstitutionDetail.vue'), meta: { requiresSchoolAdmin: true } },
    { path: '/backoffice/admin-courses', component: () => import('../pages/BackofficeAdminCourses.vue'), meta: { requiresSchoolAdmin: true } },
    { path: '/backoffice/site-settings', component: () => import('../pages/BackofficeSiteSettings.vue'), meta: { requiresSuperAdmin: true } },
    { path: '/login', component: () => import('../pages/Login.vue'), meta: { public: true } },
    { path: '/register', component: () => import('../pages/Register.vue'), meta: { public: true } },
    { path: '/account', component: () => import('../pages/Account.vue') },
    { path: '/dashboard/student', component: () => import('../pages/StudentDashboard.vue') },
    { path: '/stats/student', component: () => import('../pages/StudentStats.vue') },
    { path: '/stats/teacher', component: () => import('../pages/TeacherStats.vue'), meta: { requiresTeacher: true } },
    { path: '/:pathMatch(.*)*', component: () => import('../pages/NotFound.vue') },
  ],
})

// Global guard: the whole app requires authentication, except public routes.
router.beforeEach((to) => {
  const auth = useAuthStore()
  const isPublic = to.meta?.public === true

  if (!isPublic && !auth.isAuthenticated) {
    return { path: '/login', query: { redirect: to.fullPath } }
  }
  if (to.meta?.requiresSuperAdmin && !auth.isSuperAdmin()) {
    return { path: '/' }
  }
  if (to.meta?.requiresSchoolAdmin && !auth.isSchoolAdmin()) {
    return { path: '/' }
  }
  if (to.meta?.requiresTeacher && !auth.isTeacher()) {
    return { path: '/' }
  }
  // Already authenticated users shouldn't see login/register.
  if (isPublic && auth.isAuthenticated) {
    return { path: '/' }
  }
})

export default router
