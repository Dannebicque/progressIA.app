<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tableau de bord</h1>
                <p class="text-sm text-muted-foreground">Suivi de votre progression et de vos rendus.</p>
            </div>
            <RouterLink to="/stats/student"><Button variant="outline" size="sm"><IconChartBar class="size-4" /> Mes statistiques</Button></RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <h2 class="text-lg font-semibold tracking-tight">Mes cours</h2>
                <Card v-for="c in courses" :key="c.id">
                    <CardContent class="py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-semibold">{{ c.title }}</div>
                                <div class="text-sm text-muted-foreground">{{ c.theme }}<span v-if="c.level"> · {{ c.level }}</span></div>
                            </div>
                            <RouterLink :to="`/course/${c.id}`"><Button size="sm" variant="outline">Ouvrir</Button></RouterLink>
                        </div>

                        <div class="mt-4 flex items-center gap-3">
                            <Progress :model-value="pct(c)" class="flex-1" />
                            <span class="shrink-0 text-sm text-muted-foreground">{{ completed(c) }} / {{ c.sessions.length }}</span>
                        </div>

                        <ul class="mt-4 space-y-1.5">
                            <li v-for="s in c.sessions" :key="s.id" class="flex items-center justify-between text-sm">
                                <RouterLink :to="`/course/${c.id}/session/${s.id}`" class="flex items-center gap-2 hover:text-primary">
                                    <Badge :variant="statusVariant(c, s)" class="w-20 justify-center">{{ statusLabel(c, s) }}</Badge>
                                    {{ s.title }}
                                </RouterLink>
                                <span class="text-xs text-muted-foreground">{{ lastActivity(c, s) }}</span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
                <Card v-if="!courses.length" class="grid place-items-center py-12 text-center text-muted-foreground">Aucun cours disponible.</Card>
            </div>

            <aside class="space-y-6">
                <StatCard :icon="IconStar" label="Points" :value="points" tint="amber" />
                <Card>
                    <CardHeader><CardTitle class="text-base">Mes badges</CardTitle></CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <Badge v-for="b in badges" :key="b.id" variant="secondary" class="gap-1"><IconAward class="size-3.5 text-amber-500" />{{ b.title }}</Badge>
                        <p v-if="!badges.length" class="text-sm text-muted-foreground">Aucun badge pour le moment.</p>
                    </CardContent>
                </Card>
            </aside>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconStar, IconAward, IconChartBar } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const studentId = 'student1'
store.seedDemo(studentId)

const courses = computed(() => store.courses)
const points = computed(() => store.getPoints(studentId))
const badges = computed(() => store.getBadges(studentId))

function completed(c: any) {
    const p = store.getProgress(studentId, c.id)
    return c.sessions.filter((s: any) => p[s.id]?.done).length
}
function pct(c: any) {
    return c.sessions.length ? Math.round((completed(c) / c.sessions.length) * 100) : 0
}
function statusLabel(c: any, s: any) {
    const st = store.getProgress(studentId, c.id)[s.id] || {}
    return st.done ? 'Validée' : st.inProgress ? 'En cours' : 'À faire'
}
function statusVariant(c: any, s: any): 'default' | 'secondary' | 'outline' {
    const st = store.getProgress(studentId, c.id)[s.id] || {}
    return st.done ? 'default' : st.inProgress ? 'secondary' : 'outline'
}
function lastActivity(c: any, s: any) {
    const st = store.getProgress(studentId, c.id)[s.id] || {}
    return st.at ? new Date(st.at).toLocaleDateString() : ''
}
</script>
