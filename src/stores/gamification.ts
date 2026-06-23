import { defineStore } from 'pinia'
import { api } from '../api/client'
import { useAuthStore } from './auth'
import type { Badge } from './auth'

export interface EvalProgress {
  evaluation: number
  score: number
  maxScore: number
  passed: boolean
  feedbackStudent?: string | null
  answers?: Array<Record<string, unknown>>
}

interface CompleteResult {
  alreadyDone: boolean
  pointsEarned: number
  totalPoints: number
  newBadges: Badge[]
}

interface SubmitResult {
  score: number
  maxScore: number
  passed: boolean
  pointsEarned: number
  totalPoints: number
  results: { question: number; correct: boolean | null; awarded: number; maxPoints: number }[]
  newBadges: Badge[]
}

// Backend-driven gamification: page completion, evaluation submissions, points & badges.
export const useGamificationStore = defineStore('gamification', {
  state: () => ({
    completedPageIds: [] as number[],
    evaluations: [] as EvalProgress[],
    loaded: false,
  }),
  getters: {
    isPageDone: (state) => (id: number | string) => state.completedPageIds.includes(Number(id)),
    evalResult: (state) => (id: number | string) =>
      state.evaluations.find((e) => Number(e.evaluation) === Number(id)) || null,
  },
  actions: {
    async fetchProgress() {
      try {
        const data = await api.get<{ completedPageIds: number[]; evaluations: EvalProgress[] }>('/api/me/progress')
        this.completedPageIds = data.completedPageIds || []
        this.evaluations = data.evaluations || []
        this.loaded = true
      } catch {
        // not authenticated yet — ignore
      }
    },
    async completePage(pageId: number | string): Promise<CompleteResult> {
      const res = await api.post<CompleteResult>(`/api/pages/${pageId}/complete`)
      if (!this.completedPageIds.includes(Number(pageId))) this.completedPageIds.push(Number(pageId))
      useAuthStore().applyRewards(res.totalPoints, res.newBadges)
      return res
    },
    async submitEvaluation(evaluationId: number | string, answers: any[]): Promise<SubmitResult> {
      const res = await api.post<SubmitResult>(`/api/evaluations/${evaluationId}/submit`, { answers })
      const idx = this.evaluations.findIndex((e) => Number(e.evaluation) === Number(evaluationId))
      const entry: EvalProgress = { evaluation: Number(evaluationId), score: res.score, maxScore: res.maxScore, passed: res.passed, answers }
      const current = idx === -1 ? undefined : this.evaluations[idx]
      if (!current) this.evaluations.push(entry)
      else if (res.score > current.score) this.evaluations[idx] = entry
      useAuthStore().applyRewards(res.totalPoints, res.newBadges)
      return res
    },
    // course completion % from completed pages
    coursePct(course: any): number {
      const ids = this.coursePageIds(course)
      if (!ids.length) return 0
      const done = ids.filter((id) => this.completedPageIds.includes(id)).length
      return Math.round((done / ids.length) * 100)
    },
    coursePageIds(course: any): number[] {
      const ids: number[] = []
      for (const s of course.sessions || [])
        for (const ch of s.chapters || [])
          for (const p of ch.pages || []) ids.push(Number(p.id))
      return ids
    },
  },
})
