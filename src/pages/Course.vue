<template>
    <AppLayout>
        <div v-if="course">
            <section class="rounded-xl mb-6 overflow-hidden">
                <div :style="{ background: `linear-gradient(90deg, ${course.accentColor}22, ${course.accentColor}55)` }"
                    class="p-6">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        <div class="flex items-start gap-4">
                            <div class="w-24 h-24 rounded-lg flex items-center justify-center"
                                :style="{ background: `linear-gradient(135deg, ${course.accentColor}33, ${course.accentColor}66)` }">
                                <svg class="w-10 h-10 text-indigo-700" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M12 20l9-5-9-5-9 5 9 5z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold">{{ course.title }}</h1>
                                <p class="text-sm text-gray-600 mt-1">{{ course.theme }} · {{ course.context }}</p>
                                <p class="text-sm text-gray-700 mt-3">{{ course.scenario }}</p>
                            </div>
                        </div>

                        <div class="flex gap-4 w-full lg:w-auto">
                            <div class="bg-white px-4 py-3 rounded-lg text-center shadow-sm">
                                <div class="text-sm text-gray-500">Séances</div>
                                <div class="font-semibold text-lg">{{ course.sessions.length }}</div>
                            </div>
                            <div class="bg-white px-4 py-3 rounded-lg text-center shadow-sm">
                                <div class="text-sm text-gray-500">Durée</div>
                                <div class="font-semibold text-lg">{{ totalDuration }} min</div>
                            </div>
                            <div :style="{ background: course.accentColor }"
                                class="px-4 py-3 rounded-lg text-center text-white shadow-sm">
                                <div class="text-sm">Points</div>
                                <div class="font-semibold text-lg">{{ totalPoints }}</div>
                            </div>
                            <div class="bg-white px-4 py-3 rounded-lg text-center shadow-sm">
                                <div class="text-sm text-gray-500">Progression</div>
                                <div class="font-semibold text-lg">{{ pct }}%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl p-6 shadow">
                        <h2 class="text-2xl font-bold">{{ course.title }}</h2>
                        <p class="text-sm text-gray-600">{{ course.theme }} — {{ course.context }}</p>
                        <p class="mt-2 text-gray-700">{{ course.scenario }}</p>
                    </div>

                    <div class="mt-6 space-y-3">
                        <h3 class="text-lg font-semibold">Séances</h3>
                        <div v-for="s in course.sessions" :key="s.id"
                            :class="['rounded-lg p-4 shadow flex items-center justify-between', s.id === route.params.sid ? 'border-l-4' : '']"
                            :style="s.id === route.params.sid ? { borderColor: course.accentColor } : {}">
                            <div>
                                <div class="font-medium">{{ s.title }}</div>
                                <div class="text-sm text-gray-500">{{ s.chapters.length }} chapitres</div>
                            </div>
                            <div class="flex items-center gap-3">
                                <router-link :to="`/course/${course.id}/session/${s.id}`"
                                    :style="{ background: `linear-gradient(90deg, ${course.accentColor} 0%, ${course.accentColor}bb 100%)`, borderColor: course.accentColor }"
                                    class="px-4 py-2 text-white rounded-full font-medium">Commencer</router-link>
                            </div>
                        </div>
                    </div>
                </div>

                <aside class="bg-white rounded-xl p-4 shadow">
                    <h4 class="font-semibold">Progression</h4>
                    <p class="text-sm text-gray-600 mt-2">Progression estimée pour l'utilisateur.</p>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600"><span>Sessions complétées</span><span>{{
                            completed }} / {{ course.sessions.length }}</span></div>
                        <div class="w-full bg-gray-200 rounded-full h-3 mt-2">
                            <div :style="{ width: pct + '%', background: course.accentColor }" class="h-3 rounded-full">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <h5 class="font-semibold">Badges</h5>
                        <div class="mt-2 flex gap-2 flex-wrap">
                            <div v-for="b in badges" :key="b.id"
                                class="text-center text-xs bg-indigo-50 text-indigo-700 px-3 py-1 rounded">{{ b.title }}
                            </div>
                            <div v-if="badges.length === 0" class="text-sm text-gray-500">Aucun badge pour le moment
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
        <div v-else>
            <p>Cours introuvable</p>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { useRoute } from 'vue-router'
import { computed } from 'vue'
import AppLayout from '../components/AppLayout.vue'
import { useCoursesStore } from '../stores/courses'

const route = useRoute()
const store = useCoursesStore()
const course = store.getCourse(route.params.id as string)
const studentId = 'student1'

const completed = computed(() => {
    if (!course) return 0
    const p = store.getProgress(studentId, course.id)
    let cnt = 0
    for (const s of course.sessions) {
        if (p[s.id]?.done) cnt++
    }
    return cnt
})

const pct = computed(() => {
    if (!course) return 0
    return Math.round((completed.value / course.sessions.length) * 100)
})

const badges = computed(() => store.getBadges(studentId))

const totalDuration = computed(() => {
    if (!course) return 0
    // placeholder: assume 30 min per session
    return course.sessions.length * 30
})

const totalPoints = computed(() => {
    if (!course) return 0
    return course.sessions.length * 20
})
</script>

<style scoped></style>
