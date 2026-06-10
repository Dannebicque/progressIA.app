import { defineStore } from 'pinia'
import mock from '../data/mock-courses.json'

export const useCoursesStore = defineStore('courses', {
  state: () => ({
    courses: JSON.parse(localStorage.getItem('pf:courses') || 'null') || mock.courses as any[],
    // store progress per student in localStorage keyed by 'pf:progress'
    progress: JSON.parse(localStorage.getItem('pf:progress') || '{}') as Record<string, any>,
    points: JSON.parse(localStorage.getItem('pf:points') || '{}') as Record<string, number>,
    badges: JSON.parse(localStorage.getItem('pf:badges') || '{}') as Record<string, any[]>,
  }),
  actions: {
    // seed demo data for a sample student if storage is empty
    seedDemo(studentId = 'student1') {
      if (!localStorage.getItem('pf:progress')) {
        const now = Date.now()
        const demoProgress: Record<string, any> = {}
        demoProgress[studentId] = {}
        for (const c of this.courses) {
          demoProgress[studentId][c.id] = {}
          for (const s of c.sessions) {
            // default states: mark first session as done for some courses,
            // mark second session as in-progress for php example
            if (c.id === 'php-oop-example' && s.id === 's1') {
              demoProgress[studentId][c.id][s.id] = { done: true, at: now - 86400000 }
            } else if (c.id === 'php-oop-example' && s.id === 's2') {
              demoProgress[studentId][c.id][s.id] = { inProgress: true, at: now - 3600000 }
            } else if (c.id === 'c1' && s.id === 's1') {
              demoProgress[studentId][c.id][s.id] = { done: true, at: now - 172800000 }
            } else if (c.id === 'c2' && s.id === 's1') {
              demoProgress[studentId][c.id][s.id] = { inProgress: true, at: now - 7200000 }
            } else {
              demoProgress[studentId][c.id][s.id] = { done: false }
            }
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
        this.badges = {
          [studentId]: [
            { id: 'php-s1', title: 'Classes maîtrisées', courseId: 'php-oop-example' },
            { id: 'engaged', title: 'Participation active' }
          ]
        }
        localStorage.setItem('pf:badges', JSON.stringify(this.badges))
      }
    },
    // persist courses to localStorage
    saveCourses() {
      try {
        localStorage.setItem('pf:courses', JSON.stringify(this.courses))
      } catch (e) {}
    },
    createCourse(payload: any) {
      const id = payload.id || `c${Date.now()}`
      const course = { id, sessions: [], ...payload }
      this.courses.push(course)
      this.courses = [...this.courses]
      this.saveCourses()
      return course
    },
    updateCourse(id: string, patch: any) {
      const idx = this.courses.findIndex((c) => c.id === id)
      if (idx === -1) return null
      this.courses[idx] = { ...this.courses[idx], ...patch }
      this.courses = [...this.courses]
      this.saveCourses()
      return this.courses[idx]
    },
    deleteCourse(id: string) {
      this.courses = this.courses.filter((c) => c.id !== id)
      this.saveCourses()
    },
    addSession(courseId: string, session: any) {
      const c = this.getCourse(courseId)
      if (!c) return null
      const defaultRender = { allowUpload: true, allowedTypes: ['file', 'image'], maxFiles: 1 }
      const s = { id: session.id || `s${Date.now()}`, chapters: session.chapters || [], title: session.title || 'Nouvelle séance', renderConfig: { ...(defaultRender), ...(session.renderConfig || {}) } }
      c.sessions.push(s)
      this.courses = [...this.courses]
      this.saveCourses()
      return s
    },
    updateSession(courseId: string, sessionId: string, patch: any) {
      const c = this.getCourse(courseId)
      if (!c) return null
      const idx = c.sessions.findIndex((ss: any) => ss.id === sessionId)
      if (idx === -1) return null
      c.sessions[idx] = { ...c.sessions[idx], ...patch }
      this.courses = [...this.courses]
      this.saveCourses()
      return c.sessions[idx]
    },
    deleteSession(courseId: string, sessionId: string) {
      const c = this.getCourse(courseId)
      if (!c) return
      c.sessions = c.sessions.filter((s: any) => s.id !== sessionId)
      this.courses = [...this.courses]
      this.saveCourses()
    },
    addChapter(courseId: string, sessionId: string, chapter: any) {
      const s = this.getCourse(courseId)?.sessions.find((ss: any) => ss.id === sessionId)
      if (!s) return null
      const ch = { id: chapter.id || `ch${Date.now()}`, title: chapter.title || 'Nouveau chapitre', content: chapter.content || '' }
      s.chapters.push(ch)
      this.courses = [...this.courses]
      this.saveCourses()
      return ch
    },
    updateChapter(courseId: string, sessionId: string, chapterId: string, patch: any) {
      const s = this.getCourse(courseId)?.sessions.find((ss: any) => ss.id === sessionId)
      if (!s) return null
      const idx = s.chapters.findIndex((ch: any) => ch.id === chapterId)
      if (idx === -1) return null
      s.chapters[idx] = { ...s.chapters[idx], ...patch }
      this.courses = [...this.courses]
      this.saveCourses()
      return s.chapters[idx]
    },
    deleteChapter(courseId: string, sessionId: string, chapterId: string) {
      const s = this.getCourse(courseId)?.sessions.find((ss: any) => ss.id === sessionId)
      if (!s) return
      s.chapters = s.chapters.filter((ch: any) => ch.id !== chapterId)
      this.courses = [...this.courses]
      this.saveCourses()
    },
    // list students who have any progress recorded
    getStudentsForCourse(courseId: string) {
      const out: string[] = []
      const all = JSON.parse(localStorage.getItem('pf:progress') || '{}') as Record<string, any>
      for (const sid of Object.keys(all)) {
        if (all[sid] && all[sid][courseId]) out.push(sid)
      }
      return out
    },
    // scan localStorage for uploads for a given course/session (key pattern pf:upload:courseId:sessionId:studentId)
    getUploadsForSession(courseId: string, sessionId: string) {
      const uploads: Record<string, any[]> = {}
      try {
        for (let i = 0; i < localStorage.length; i++) {
          const k = localStorage.key(i) || ''
          if (k.startsWith(`pf:upload:${courseId}:${sessionId}:`)) {
            const student = k.split(':').pop() || ''
            const v = localStorage.getItem(k)
            try {
              uploads[student] = JSON.parse(v || 'null') || [v]
            } catch (e) {
              uploads[student] = [v]
            }
          }
        }
      } catch (e) {}
      return uploads
    },
    // evaluations stored under pf:evaluations as mapping student->course->session -> evaluation
    saveEvaluation(studentId: string, courseId: string, sessionId: string, evalObj: any) {
      const key = 'pf:evaluations'
      const all = JSON.parse(localStorage.getItem(key) || '{}') as Record<string, any>
      all[studentId] = all[studentId] || {}
      all[studentId][courseId] = all[studentId][courseId] || {}
      all[studentId][courseId][sessionId] = { ...evalObj, at: Date.now() }
      localStorage.setItem(key, JSON.stringify(all))
    },
    getEvaluations(studentId: string, courseId: string) {
      const key = 'pf:evaluations'
      const all = JSON.parse(localStorage.getItem(key) || '{}') as Record<string, any>
      return (all[studentId] && all[studentId][courseId]) || {}
    },
    getCourse(id: string) {
      return this.courses.find((c) => c.id === id)
    },
    saveProgress(studentId: string, courseId: string, sessionId: string, value: any) {
      this.progress[studentId] = this.progress[studentId] || {}
      this.progress[studentId][courseId] = this.progress[studentId][courseId] || {}
      this.progress[studentId][courseId][sessionId] = value
      localStorage.setItem('pf:progress', JSON.stringify(this.progress))
      // if this session is marked done, check course completion
      try {
        if (value && value.done) this.checkCourseCompletion(studentId, courseId)
      } catch (e) {
        /* ignore */
      }
    },
    getProgress(studentId: string, courseId: string) {
      return this.progress[studentId]?.[courseId] || {}
    }
    ,
    // Points & badges (simple prototype)
    awardPoints(studentId: string, pts: number) {
      this.points[studentId] = (this.points[studentId] || 0) + pts
      // force state replacement to ensure reactivity in nested objects
      this.points = { ...this.points }
      localStorage.setItem('pf:points', JSON.stringify(this.points))
    },
    getPoints(studentId: string) {
      return this.points[studentId] || 0
    },
    awardBadge(studentId: string, badge: any) {
      this.badges[studentId] = this.badges[studentId] || []
      // avoid duplicates by id if provided
      if (!this.badges[studentId].some((b: any) => b.id === badge.id)) {
        this.badges[studentId].push(badge)
        // force replacement for reactivity
        this.badges = { ...this.badges }
        localStorage.setItem('pf:badges', JSON.stringify(this.badges))
      }
    },
    getBadges(studentId: string) {
      return this.badges[studentId] || []
    }
    ,
    checkCourseCompletion(studentId: string, courseId: string) {
      const course = this.courses.find((c) => c.id === courseId)
      if (!course) return false
      const p = this.getProgress(studentId, courseId)
      // all sessions must be done
      const allDone = course.sessions.every((s: any) => p[s.id]?.done)
      if (!allDone) return false
      const badgeId = `course-complete-${courseId}`
      // award badge if not already present
      const existing = (this.badges[studentId] || []).some((b: any) => b.id === badgeId)
      if (!existing) {
        this.awardBadge(studentId, { id: badgeId, title: `Cours complété — ${course.title}`, courseId })
        // award bonus points
        this.awardPoints(studentId, 50)
      }
      return true
    }
  }
})
