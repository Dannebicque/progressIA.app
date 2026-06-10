<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Statistiques — Enseignant</h1>
                <p class="text-sm text-muted-foreground">Vue d'ensemble de vos cours et de l'activité des étudiants.</p>
            </div>
            <RouterLink to="/backoffice"><Button variant="outline" size="sm"><IconPencil class="size-4" /> Éditer les cours</Button></RouterLink>
        </div>

        <!-- KPI -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard :icon="IconBook" label="Cours" :value="totalCourses" tint="indigo" />
            <StatCard :icon="IconLayoutList" label="Séances" :value="totalSessions" tint="violet" />
            <StatCard :icon="IconFileText" label="Chapitres" :value="totalChapters" tint="sky" />
            <StatCard :icon="IconUsers" label="Étudiants actifs" :value="activeStudents" tint="emerald" />
            <StatCard :icon="IconStar" label="Points distribués" :value="pointsDistributed" tint="amber" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- sessions per course (bar chart) -->
            <Card>
                <CardHeader>
                    <CardTitle>Séances par cours</CardTitle>
                    <CardDescription>Volume de contenu par formation.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="c in courses" :key="c.id" class="flex items-center gap-3">
                        <div class="w-40 shrink-0 truncate text-sm" :title="c.title">{{ c.title }}</div>
                        <div class="h-6 flex-1 rounded-md bg-muted">
                            <div class="grid h-6 place-items-end rounded-md bg-brand-gradient pr-2 text-xs font-medium text-white"
                                :style="{ width: barWidth(c.sessions.length) }">
                                {{ c.sessions.length }}
                            </div>
                        </div>
                    </div>
                    <p v-if="!courses.length" class="text-sm text-muted-foreground">Aucun cours.</p>
                </CardContent>
            </Card>

            <!-- completion per course -->
            <Card>
                <CardHeader>
                    <CardTitle>Complétion moyenne</CardTitle>
                    <CardDescription>Progression moyenne des étudiants par cours.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div v-for="c in courses" :key="c.id">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium">{{ c.title }}</span>
                            <span class="text-muted-foreground">{{ studentsOf(c).length }} étudiant(s) · {{ avgCompletion(c) }}%</span>
                        </div>
                        <Progress :model-value="avgCompletion(c)" />
                    </div>
                    <p v-if="!courses.length" class="text-sm text-muted-foreground">Aucun cours.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconBook, IconLayoutList, IconFileText, IconUsers, IconStar, IconPencil } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const courses = computed(() => store.courses)

const totalCourses = computed(() => courses.value.length)
const totalSessions = computed(() => courses.value.reduce((a, c) => a + c.sessions.length, 0))
const totalChapters = computed(() => courses.value.reduce((a, c) => a + c.sessions.reduce((b: number, s: any) => b + (s.chapters?.length || 0), 0), 0))
const activeStudents = computed(() => Object.keys(store.progress || {}).length)
const pointsDistributed = computed(() => Object.values(store.points || {}).reduce((a: number, b: any) => a + (b || 0), 0))

const maxSessions = computed(() => Math.max(1, ...courses.value.map((c) => c.sessions.length)))
function barWidth(n: number) {
    return `${Math.max(8, Math.round((n / maxSessions.value) * 100))}%`
}

function studentsOf(c: any) {
    return store.getStudentsForCourse(c.id)
}
function avgCompletion(c: any) {
    const students = studentsOf(c)
    if (!students.length || !c.sessions.length) return 0
    const sum = students.reduce((acc, sid) => {
        const p = store.getProgress(sid, c.id)
        const done = c.sessions.filter((s: any) => p[s.id]?.done).length
        return acc + done / c.sessions.length
    }, 0)
    return Math.round((sum / students.length) * 100)
}
</script>
