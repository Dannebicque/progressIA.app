import { defineStore } from 'pinia'
import { api, setToken, getToken } from '../api/client'

export interface AuthUser {
  id: number
  email: string
  name: string
  roles: string[]
  points: number
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('pf:user') || 'null') as AuthUser | null,
    token: getToken(),
  }),
  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
  },
  actions: {
    // Authenticate against /api/login (JWT) then load the profile from /api/me.
    async login(email: string, password: string): Promise<AuthUser> {
      const { token } = await api.post<{ token: string }>('/api/login', { email, password }, { anonymous: true })
      setToken(token)
      this.token = token
      return this.fetchMe()
    },
    async fetchMe(): Promise<AuthUser> {
      const user = await api.get<AuthUser>('/api/me')
      this.user = user
      localStorage.setItem('pf:user', JSON.stringify(user))
      return user
    },
    // Create an account then sign in automatically.
    async register(name: string, email: string, password: string): Promise<AuthUser> {
      await api.post('/api/register', { name, email, password }, { anonymous: true })
      return this.login(email, password)
    },
    // Update the authenticated user's own profile (name / email / password).
    async updateAccount(payload: { name?: string; email?: string; password?: string }): Promise<AuthUser> {
      const user = await api.patch<AuthUser>('/api/account', payload)
      this.user = user
      localStorage.setItem('pf:user', JSON.stringify(user))
      return user
    },
    logout() {
      this.user = null
      this.token = null
      setToken(null)
      localStorage.removeItem('pf:user')
    },
    isTeacher() {
      return this.user?.roles?.includes('ROLE_TEACHER') ?? false
    },
  },
})
