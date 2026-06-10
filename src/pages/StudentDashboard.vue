<template>
    <AppLayout>
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Tableau de bord — Étudiant</h2>
                <p class="mt-2 text-sm text-gray-600">Suivi de progression et rendus.</p>
            </div>
            <div class="text-right">
                <div class="text-sm text-gray-500">Points</div>
                <div class="text-2xl font-bold text-indigo-600">{{ points }}</div>
            </div>
        </div>

        <div class="mt-6 grid md:grid-cols-2 gap-6">
            <div>
                <h3 class="font-semibold mb-3">Mes cours</h3>
                <div v-for="c in courses" :key="c.id" class="bg-white p-4 rounded shadow mb-4">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold">{{ c.title }}</div>
                                    <div class="text-sm text-gray-600">{{ c.theme }} · {{ c.level }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Progression</div>
                                    <div class="text-lg font-semibold" :style="{ color: c.accentColor }">{{
                                        getCoursePct(c) }}%</div>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center gap-3">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div :style="{ width: getCoursePct(c) + '%', backgroundColor: c.accentColor }"
                                        class="h-2 rounded-full"></div>
                                </div>
                                <div class="text-sm text-gray-500">{{ getCompletedCount(c) }} / {{ c.sessions.length }}
                                </div>
                            </div>

                            <div class="mt-3 text-sm text-gray-700">
                                <div class="font-medium">Séances</div>
                                <ul class="mt-2 space-y-1">
                                    <li v-for="s in c.sessions" :key="s.id" class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <span :class="statusClass(c, s)"
                                                class="inline-flex items-center px-2 py-1 text-xs rounded">{{
                                                statusLabel(c,s) }}</span>
                                            <router-link :to="`/course/${c.id}/session/${s.id}`" class="text-sm">{{
                                                s.title }}</router-link>
                                        </div>
                                        <div class="text-xs text-gray-400">{{ lastActivityFor(c, s) }}</div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex-shrink-0 flex flex-col gap-2">
                            <router-link :to="`/course/${c.id}`" class="px-3 py-2 text-sm rounded"
                                :style="{ backgroundColor: c.accentColor, color: 'white' }">Ouvrir</router-link>
                            <div class="text-sm text-gray-500">Points: <span class="font-semibold">{{ points }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside>
                <h3 class="font-semibold mb-3">Mes badges</h3>
                <div class="bg-white rounded p-4 shadow">
                    <div v-if="badges.length === 0" class="text-sm text-gray-500">Aucun badge pour le moment</div>
                    <div v-else class="flex gap-2 flex-wrap">
                        <div v-for="b in badges" :key="b.id"
                            class="px-3 py-2 bg-indigo-50 text-indigo-700 rounded text-sm">
                            <div class="font-medium">{{ b.title }}</div>
                            <div class="text-xs text-gray-500" v-if="b.courseId">{{ b.courseId }}</div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '../components/AppLayout.vue'
import { computed } from 'vue'
import { useCoursesStore } from '../stores/courses'

const store = useCoursesStore()
const courses = store.courses
const studentId = 'student1'

// seed demo data if missing
store.seedDemo(studentId)

const points = computed(() => store.getPoints(studentId))
const badges = computed(() => store.getBadges(studentId))

function getCourseProgress(course: any) {
    return store.getProgress(studentId, course.id)
}

function getCompletedCount(course: any) {
    const p = getCourseProgress(course)
    let cnt = 0
    for (const s of course.sessions) if (p[s.id]?.done) cnt++
    return cnt
}

function getCoursePct(course: any) {
    if (!course || !course.sessions) return 0
    return Math.round((getCompletedCount(course) / course.sessions.length) * 100)
}

function statusLabel(course: any, session: any) {
    const p = getCourseProgress(course)
    const s = p[session.id] || {}
    if (s.done) return 'Validée'
    if (s.inProgress) return 'En cours'
    return 'À faire'
}

function statusClass(course: any, session: any) {
    const p = getCourseProgress(course)
    const s = p[session.id] || {}
    if (s.done) return 'bg-green-100 text-green-700'
    if (s.inProgress) return 'bg-yellow-100 text-yellow-700'
    return 'bg-gray-100 text-gray-600'
}

function lastActivityFor(course: any, session: any) {
    const p = getCourseProgress(course)
    const s = p[session.id] || {}
    if (s.at) {
        const d = new Date(s.at)
        return d.toLocaleString()
    }
    return ''
}
</script>

<style scoped></style>
