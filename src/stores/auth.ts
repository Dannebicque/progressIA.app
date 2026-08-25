import { defineStore } from 'pinia'
import { api, setToken, getToken } from '../api/client'

export interface Badge {
  id?: number
  code: string
  label: string
  icon: string
  description?: string | null
  awardedAt?: string
}

export interface AuthUser {
  id: number
  email: string
  name: string
  roles: string[]
  points: number
  badges: Badge[]
  avatar?: string | null
  studentGroup?: string | null
  studentYear?: string | null
  studentInstitution?: string | null
  institution?: { id: number; name: string; subscriptionFee?: string; costPerStudent?: string } | null
  institutions?: Array<{ id: number; name: string }> | null
  studentSemester?: { id: number; name: string } | null
  studentFormation?: { id: number; name: string } | null
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
    async register(name: string, email: string, password: string, invitationCode?: string): Promise<AuthUser> {
      await api.post('/api/register', { name, email, password, invitationCode: invitationCode || undefined }, { anonymous: true })
      return this.login(email, password)
    },
    // Update the authenticated user's own profile (name / email / password).
    async updateAccount(payload: { name?: string; email?: string; password?: string }): Promise<AuthUser> {
      const user = await api.patch<AuthUser>('/api/account', payload)
      this.user = user
      localStorage.setItem('pf:user', JSON.stringify(user))
      return user
    },
    // Apply rewards returned by the gamification endpoints (points + new badges).
    applyRewards(totalPoints: number, newBadges: Badge[] = []) {
      if (!this.user) return
      this.user.points = totalPoints
      const existing = new Set((this.user.badges || []).map((b) => b.code))
      for (const b of newBadges) {
        if (!existing.has(b.code)) this.user.badges = [...(this.user.badges || []), b]
      }
      localStorage.setItem('pf:user', JSON.stringify(this.user))
    },
    logout() {
      this.user = null
      this.token = null
      setToken(null)
      localStorage.removeItem('pf:user')
    },
    isTeacher() {
      return this.user?.roles?.some((r) => ['ROLE_TEACHER', 'ROLE_SCHOOL_ADMIN', 'ROLE_SUPER_ADMIN'].includes(r)) ?? false
    },
    isSchoolAdmin() {
      return this.user?.roles?.some((r) => ['ROLE_SCHOOL_ADMIN', 'ROLE_SUPER_ADMIN'].includes(r)) ?? false
    },
    isSuperAdmin() {
      return this.user?.roles?.includes('ROLE_SUPER_ADMIN') ?? false
    },
  },
})
