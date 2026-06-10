import { defineStore } from 'pinia'
import { api } from '../api/client'

// NOTE: The course/session/chapter tree is now backed by the Symfony API.
// Gamification (progress / points / badges / uploads / evaluations) is still
// kept in localStorage — it will move to the API in a later phase.

export const useCoursesStore = defineStore('courses', {
  state: () => ({
    courses: [] as any[],
    loaded: false,
    progress: JSON.parse(localStorage.getItem('pf:progress') || '{}') as Record<string, any>,
    points: JSON.parse(localStorage.getItem('pf:points') || '{}') as Record<string, number>,
    badges: JSON.parse(localStorage.getItem('pf:badges') || '{}') as Record<string, any[]>,
  }),
  actions: {
    // ---- Courses tree (API-backed) -------------------------------------

    // Load the full catalogue (courses -> sessions -> chapters) from the API.
    // Mutates the array in place so references captured by components stay reactive.
    async fetchCourses() {
      const data = await api.get<any[]>('/api/courses', { anonymous: true })
      this.courses.splice(0, this.courses.length, ...data)
      this.loaded = true
      return this.courses
    },

    getCourse(id: string | number) {
      return this.courses.find((c) => String(c.id) === String(id))
    },

    async createCourse(payload: any) {
      const { id: _omit, sessions: _omitSessions, ...body } = payload
      const course = await api.post<any>('/api/courses', { sessions: [], ...body })
      if (!course.sessions) course.sessions = []
      this.courses.push(course)
      return course
    },

    async updateCourse(id: string | number, patch: any) {
      const course = this.getCourse(id)
      if (!course) return null
      const updated = await api.patch<any>(`/api/courses/${course.id}`, patch)
      Object.assign(course, updated)
      return course
    },

    async deleteCourse(id: string | number) {
      const course = this.getCourse(id)
      if (!course) return
      await api.delete(`/api/courses/${course.id}`)
      const idx = this.courses.indexOf(course)
      if (idx !== -1) this.courses.splice(idx, 1)
    },

    async addSession(courseId: string | number, session: any) {
      const course = this.getCourse(courseId)
      if (!course) return null
      const body = {
        title: session.title || 'Nouvelle séance',
        pitch: session.pitch ?? null,
        renderConfig: session.renderConfig || { allowUpload: true, allowedTypes: ['file', 'image'], maxFiles: 1 },
        position: course.sessions.length,
        course: `/api/courses/${course.id}`,
      }
      const created = await api.post<any>('/api/sessions', body)
      if (!created.chapters) created.chapters = []
      course.sessions.push(created)
      return created
    },

    async updateSession(courseId: string | number, sessionId: string | number, patch: any) {
      const course = this.getCourse(courseId)
      const sess = course?.sessions.find((s: any) => String(s.id) === String(sessionId))
      if (!sess) return null
      const updated = await api.patch<any>(`/api/sessions/${sess.id}`, patch)
      Object.assign(sess, updated)
      return sess
    },

    async deleteSession(courseId: string | number, sessionId: string | number) {
      const course = this.getCourse(courseId)
      const sess = course?.sessions.find((s: any) => String(s.id) === String(sessionId))
      if (!sess) return
      await api.delete(`/api/sessions/${sess.id}`)
      const idx = course.sessions.indexOf(sess)
      if (idx !== -1) course.sessions.splice(idx, 1)
    },

    async addChapter(courseId: string | number, sessionId: string | number, chapter: any) {
      const course = this.getCourse(courseId)
      const sess = course?.sessions.find((s: any) => String(s.id) === String(sessionId))
      if (!sess) return null
      const body = {
        title: chapter.title || 'Nouveau chapitre',
        content: chapter.content || '',
        position: sess.chapters.length,
        session: `/api/sessions/${sess.id}`,
      }
      const created = await api.post<any>('/api/chapters', body)
      sess.chapters.push(created)
      return created
    },

    async updateChapter(courseId: string | number, sessionId: string | number, chapterId: string | number, patch: any) {
      const course = this.getCourse(courseId)
      const sess = course?.sessions.find((s: any) => String(s.id) === String(sessionId))
      const ch = sess?.chapters.find((c: any) => String(c.id) === String(chapterId))
      if (!ch) return null
      const updated = await api.patch<any>(`/api/chapters/${ch.id}`, patch)
      Object.assign(ch, updated)
      return ch
    },

    async deleteChapter(courseId: string | number, sessionId: string | number, chapterId: string | number) {
      const course = this.getCourse(courseId)
      const sess = course?.sessions.find((s: any) => String(s.id) === String(sessionId))
      const ch = sess?.chapters.find((c: any) => String(c.id) === String(chapterId))
      if (!ch) return
      await api.delete(`/api/chapters/${ch.id}`)
      const idx = sess.chapters.indexOf(ch)
      if (idx !== -1) sess.chapters.splice(idx, 1)
    },

    // ---- Gamification (localStorage — phase 2) -------------------------

    seedDemo(studentId = 'student1') {
      if (!localStorage.getItem('pf:progress')) {
        const now = Date.now()
        const demoProgress: Record<string, any> = {}
        demoProgress[studentId] = {}
        for (const c of this.courses) {
          demoProgress[studentId][c.id] = {}
          for (const s of c.sessions) {
            demoProgress[studentId][c.id][s.id] = { done: false }
          }
        }
        this.progress = demoProgress
        localStorage.setItem('pf:progress', JSON.stringify(this.progress))
      }
      if (!localStorage.getItem('pf:points')) {
        this.points = { [studentId]: 120 }
        localStorage.setItem('pf:points', JSON.stringify(this.points))
      }
      if (!localStorage.getItem('pf:badges')) {
        this.badges = { [studentId]: [] }
        localStorage.setItem('pf:badges', JSON.stringify(this.badges))
      }
    },

    getStudentsForCourse(courseId: string | number) {
      const out: string[] = []
      const all = JSON.parse(localStorage.getItem('pf:progress') || '{}') as Record<string, any>
      for (const sid of Object.keys(all)) {
        if (all[sid] && all[sid][courseId]) out.push(sid)
      }
      return out
    },

    getUploadsForSession(courseId: string | number, sessionId: string | number) {
      const uploads: Record<string, any[]> = {}
      try {
        for (let i = 0; i < localStorage.length; i++) {
          const k = localStorage.key(i) || ''
          if (k.startsWith(`pf:upload:${courseId}:${sessionId}:`)) {
            const student = k.split(':').pop() || ''
            const v = localStorage.getItem(k)
            try {
              uploads[student] = JSON.parse(v || 'null') || [v]
            } catch {
              uploads[student] = [v]
            }
          }
        }
      } catch {}
      return uploads
    },

    saveEvaluation(studentId: string, courseId: string | number, sessionId: string | number, evalObj: any) {
      const key = 'pf:evaluations'
      const all = JSON.parse(localStorage.getItem(key) || '{}') as Record<string, any>
      all[studentId] = all[studentId] || {}
      all[studentId][courseId] = all[studentId][courseId] || {}
      all[studentId][courseId][sessionId] = { ...evalObj, at: Date.now() }
      localStorage.setItem(key, JSON.stringify(all))
    },

    getEvaluations(studentId: string, courseId: string | number) {
      const all = JSON.parse(localStorage.getItem('pf:evaluations') || '{}') as Record<string, any>
      return (all[studentId] && all[studentId][courseId]) || {}
    },

    saveProgress(studentId: string, courseId: string | number, sessionId: string | number, value: any) {
      this.progress[studentId] = this.progress[studentId] || {}
      this.progress[studentId][courseId] = this.progress[studentId][courseId] || {}
      this.progress[studentId][courseId][sessionId] = value
      localStorage.setItem('pf:progress', JSON.stringify(this.progress))
      try {
        if (value && value.done) this.checkCourseCompletion(studentId, courseId)
      } catch {}
    },

    getProgress(studentId: string, courseId: string | number) {
      return this.progress[studentId]?.[courseId] || {}
    },

    awardPoints(studentId: string, pts: number) {
      this.points[studentId] = (this.points[studentId] || 0) + pts
      this.points = { ...this.points }
      localStorage.setItem('pf:points', JSON.stringify(this.points))
    },

    getPoints(studentId: string) {
      return this.points[studentId] || 0
    },

    awardBadge(studentId: string, badge: any) {
      this.badges[studentId] = this.badges[studentId] || []
      if (!this.badges[studentId].some((b: any) => b.id === badge.id)) {
        this.badges[studentId].push(badge)
        this.badges = { ...this.badges }
        localStorage.setItem('pf:badges', JSON.stringify(this.badges))
      }
    },

    getBadges(studentId: string) {
      return this.badges[studentId] || []
    },

    checkCourseCompletion(studentId: string, courseId: string | number) {
      const course = this.getCourse(courseId)
      if (!course) return false
      const p = this.getProgress(studentId, courseId)
      const allDone = course.sessions.every((s: any) => p[s.id]?.done)
      if (!allDone) return false
      const badgeId = `course-complete-${courseId}`
      const existing = (this.badges[studentId] || []).some((b: any) => b.id === badgeId)
      if (!existing) {
        this.awardBadge(studentId, { id: badgeId, title: `Cours complété — ${course.title}`, courseId })
        this.awardPoints(studentId, 50)
      }
      return true
    },
  },
})
