<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tableau de bord</h1>
                <p class="text-sm text-muted-foreground">Suivi de votre progression.</p>
            </div>
            <RouterLink to="/stats/student"><Button variant="outline" size="sm"><IconChartBar class="size-4" /> Mes statistiques</Button></RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <h2 class="text-lg font-semibold tracking-tight">Mes cours</h2>
                <Card v-for="c in courses" :key="c.id">
                    <CardContent class="py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <div class="font-semibold">{{ c.title }}</div>
                                <Badge v-if="c.category && c.category !== 'other'" variant="secondary" class="uppercase">{{ c.category }}</Badge>
                            </div>
                            <RouterLink :to="`/course/${c.id}`"><Button size="sm" variant="outline">Ouvrir</Button></RouterLink>
                        </div>
                        <div class="mt-4 flex items-center gap-3">
                            <Progress :model-value="gam.coursePct(c)" class="flex-1" />
                            <span class="shrink-0 text-sm text-muted-foreground">{{ gam.coursePct(c) }}%</span>
                        </div>
                    </CardContent>
                </Card>
                <Card v-if="!courses.length" class="grid place-items-center py-12 text-center text-muted-foreground">Aucun cours disponible.</Card>
            </div>

            <aside class="space-y-6">
                <StatCard :icon="IconStar" label="Points" :value="points" tint="amber" />
                <Card>
                    <CardHeader><CardTitle class="text-base">Mes badges</CardTitle></CardHeader>
                    <CardContent class="flex flex-wrap gap-2">
                        <Badge v-for="b in badges" :key="b.code" variant="secondary" class="gap-1"><span>{{ b.icon }}</span>{{ b.label }}</Badge>
                        <p v-if="!badges.length" class="text-sm text-muted-foreground">Aucun badge pour le moment.</p>
                    </CardContent>
                </Card>
            </aside>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconStar, IconChartBar } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { useGamificationStore } from '@/stores/gamification'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const gam = useGamificationStore()
const auth = useAuthStore()

const courses = computed(() => store.courses)
const points = computed(() => auth.user?.points ?? 0)
const badges = computed(() => auth.user?.badges || [])
</script>
