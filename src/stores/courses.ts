import { defineStore } from 'pinia'
import { api } from '../api/client'

// Course → Session → Chapter → { Page | Evaluation → Question → Choice }
// Backed by the Symfony API. Write operations hit the API then refetch the tree
// so the local state always mirrors the server (simple and robust for a CRUD UI).

export const useCoursesStore = defineStore('courses', {
  state: () => ({
    courses: [] as any[],
    loaded: false,
  }),
  actions: {
    async fetchCourses() {
      const data = await api.get<any[]>('/api/courses', { anonymous: true })
      this.courses.splice(0, this.courses.length, ...data)
      this.loaded = true
      return this.courses
    },

    // ---- tree lookups ----
    getCourse(id: string | number) {
      return this.courses.find((c) => String(c.id) === String(id))
    },
    findSession(id: string | number): any {
      for (const c of this.courses) {
        const s = c.sessions?.find((s: any) => String(s.id) === String(id))
        if (s) return s
      }
      return null
    },
    findChapter(id: string | number): any {
      for (const c of this.courses)
        for (const s of c.sessions || []) {
          const ch = s.chapters?.find((ch: any) => String(ch.id) === String(id))
          if (ch) return ch
        }
      return null
    },

    // ---- Course ----
    async createCourse(payload: any) {
      const { id: _i, sessions: _s, ...body } = payload
      const course = await api.post<any>('/api/courses', { sessions: [], ...body })
      await this.fetchCourses()
      return this.getCourse(course.id) ?? course
    },
    async updateCourse(id: string | number, patch: any) {
      const r = await api.patch<any>(`/api/courses/${id}`, patch)
      await this.fetchCourses()
      return this.getCourse(id) ?? r
    },
    async deleteCourse(id: string | number) {
      await api.delete(`/api/courses/${id}`)
      await this.fetchCourses()
    },

    // ---- Session ----
    async addSession(courseId: string | number, session: any = {}) {
      const course = this.getCourse(courseId)
      const r = await api.post<any>('/api/sessions', {
        title: session.title || 'Nouvelle séance',
        pitch: session.pitch ?? null,
        renderConfig: session.renderConfig || { allowUpload: true, allowedTypes: ['file', 'image'], maxFiles: 1 },
        position: course?.sessions?.length ?? 0,
        course: `/api/courses/${courseId}`,
      })
      await this.fetchCourses()
      return this.findSession(r.id) ?? r
    },
    async updateSession(id: string | number, patch: any) {
      const r = await api.patch<any>(`/api/sessions/${id}`, patch)
      await this.fetchCourses()
      return this.findSession(id) ?? r
    },
    async deleteSession(id: string | number) {
      await api.delete(`/api/sessions/${id}`)
      await this.fetchCourses()
    },

    // ---- Chapter ----
    async addChapter(sessionId: string | number, chapter: any = {}) {
      const session = this.findSession(sessionId)
      const r = await api.post<any>('/api/chapters', {
        title: chapter.title || 'Nouveau chapitre',
        position: session?.chapters?.length ?? 0,
        session: `/api/sessions/${sessionId}`,
      })
      await this.fetchCourses()
      return this.findChapter(r.id) ?? r
    },
    async updateChapter(id: string | number, patch: any) {
      const r = await api.patch<any>(`/api/chapters/${id}`, patch)
      await this.fetchCourses()
      return this.findChapter(id) ?? r
    },
    async deleteChapter(id: string | number) {
      await api.delete(`/api/chapters/${id}`)
      await this.fetchCourses()
    },

    // ---- Page ----
    async addPage(chapterId: string | number, page: any = {}) {
      const chapter = this.findChapter(chapterId)
      const r = await api.post<any>('/api/pages', {
        title: page.title || 'Nouvelle page',
        content: page.content || '# Titre',
        points: page.points ?? 5,
        position: chapter?.pages?.length ?? 0,
        chapter: `/api/chapters/${chapterId}`,
      })
      await this.fetchCourses()
      return r
    },
    async updatePage(id: string | number, patch: any) {
      const r = await api.patch<any>(`/api/pages/${id}`, patch)
      await this.fetchCourses()
      return r
    },
    async deletePage(id: string | number) {
      await api.delete(`/api/pages/${id}`)
      await this.fetchCourses()
    },

    // ---- Evaluation (authoring) ----
    // Teacher-only read that includes the correct answers.
    async fetchEvaluationAdmin(id: string | number) {
      return api.get<any>(`/api/evaluations/${id}`)
    },
    async addEvaluation(chapterId: string | number, evaluation: any = {}) {
      const chapter = this.findChapter(chapterId)
      const r = await api.post<any>('/api/evaluations', {
        title: evaluation.title || 'Nouvelle évaluation',
        description: evaluation.description ?? null,
        pointsReward: evaluation.pointsReward ?? 20,
        position: (chapter?.evaluations?.length ?? 0),
        chapter: `/api/chapters/${chapterId}`,
      })
      await this.fetchCourses()
      return r
    },
    async updateEvaluation(id: string | number, patch: any) {
      const r = await api.patch<any>(`/api/evaluations/${id}`, patch)
      await this.fetchCourses()
      return r
    },
    async deleteEvaluation(id: string | number) {
      await api.delete(`/api/evaluations/${id}`)
      await this.fetchCourses()
    },

    // ---- Question / Choice ----
    async addQuestion(evaluationId: string | number, question: any = {}) {
      return api.post<any>('/api/questions', {
        type: question.type || 'qcm',
        statement: question.statement || 'Nouvelle question',
        points: question.points ?? 1,
        multiple: question.multiple ?? false,
        position: question.position ?? 0,
        evaluation: `/api/evaluations/${evaluationId}`,
      })
    },
    async updateQuestion(id: string | number, patch: any) {
      return api.patch<any>(`/api/questions/${id}`, patch)
    },
    async deleteQuestion(id: string | number) {
      return api.delete(`/api/questions/${id}`)
    },
    async addChoice(questionId: string | number, choice: any = {}) {
      return api.post<any>('/api/choices', {
        text: choice.text || 'Réponse',
        correct: choice.correct ?? false,
        position: choice.position ?? 0,
        question: `/api/questions/${questionId}`,
      })
    },
    async updateChoice(id: string | number, patch: any) {
      return api.patch<any>(`/api/choices/${id}`, patch)
    },
    async deleteChoice(id: string | number) {
      return api.delete(`/api/choices/${id}`)
    },
  },
})
