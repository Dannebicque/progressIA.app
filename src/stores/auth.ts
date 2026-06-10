import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('pf:user') || 'null') as { name: string; role: string } | null,
  }),
  actions: {
    login(name: string, role: string) {
      this.user = { name, role }
      localStorage.setItem('pf:user', JSON.stringify(this.user))
    },
    logout() {
      this.user = null
      localStorage.removeItem('pf:user')
    },
    isTeacher() {
      return this.user?.role === 'teacher'
    }
  }
})
