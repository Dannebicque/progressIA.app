<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Mes statistiques</h1>
                <p class="text-sm text-muted-foreground">Votre progression et vos récompenses sur PedagoFlow.</p>
            </div>
            <Badge variant="secondary" class="gap-1"><IconUser class="size-3.5" /> {{ auth.user?.name }}</Badge>
        </div>

        <!-- KPI -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard :icon="IconStar" label="Points" :value="points" tint="amber" />
            <StatCard :icon="IconBook" label="Cours suivis" :value="courses.length" tint="indigo" />
            <StatCard :icon="IconCircleCheck" label="Séances validées" :value="totalDone" :sub="`/ ${totalSessions}`" tint="emerald" />
            <StatCard :icon="IconTrophy" label="Badges" :value="badges.length" tint="pink" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <!-- progress per course -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Progression par cours</CardTitle>
                    <CardDescription>Pourcentage de séances validées.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div v-for="c in courses" :key="c.id">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium">{{ c.title }}</span>
                            <span class="text-muted-foreground">{{ completed(c) }} / {{ c.sessions.length }} · {{ pct(c) }}%</span>
                        </div>
                        <Progress :model-value="pct(c)" />
                    </div>
                    <p v-if="!courses.length" class="text-sm text-muted-foreground">Aucun cours disponible.</p>
                </CardContent>
            </Card>

            <!-- badges -->
            <Card>
                <CardHeader>
                    <CardTitle>Mes badges</CardTitle>
                    <CardDescription>Récompenses débloquées.</CardDescription>
                </CardHeader>
                <CardContent class="flex flex-wrap gap-2">
                    <Badge v-for="b in badges" :key="b.id" variant="secondary" class="gap-1 py-1">
                        <IconAward class="size-3.5 text-amber-500" /> {{ b.title }}
                    </Badge>
                    <p v-if="!badges.length" class="text-sm text-muted-foreground">Aucun badge pour le moment. Terminez des séances pour en gagner&nbsp;!</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconStar, IconBook, IconCircleCheck, IconTrophy, IconAward, IconUser } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { useAuthStore } from '@/stores/auth'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const auth = useAuthStore()
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
const totalSessions = computed(() => courses.value.reduce((a, c) => a + c.sessions.length, 0))
const totalDone = computed(() => courses.value.reduce((a, c) => a + completed(c), 0))
</script>
