<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Mes statistiques</h1>
                <p class="text-sm text-muted-foreground">Votre progression et vos récompenses sur ProgressIA.</p>
            </div>
            <Badge variant="secondary" class="gap-1"><IconUser class="size-3.5" /> {{ auth.user?.name }}</Badge>
        </div>

        <!-- KPI -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard :icon="IconStar" label="Points" :value="points" tint="amber" />
            <StatCard :icon="IconBook" label="Cours" :value="courses.length" tint="indigo" />
            <StatCard :icon="IconCircleCheck" label="Pages validées" :value="donePages" :sub="`/ ${totalPages}`" tint="emerald" />
            <StatCard :icon="IconTrophy" label="Badges" :value="badges.length" tint="pink" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle>Progression par cours</CardTitle>
                    <CardDescription>Pourcentage de pages terminées.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <div v-for="c in courses" :key="c.id">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="font-medium">{{ c.title }}</span>
                            <span class="text-muted-foreground">{{ gam.coursePct(c) }}%</span>
                        </div>
                        <Progress :model-value="gam.coursePct(c)" />
                    </div>
                    <p v-if="!courses.length" class="text-sm text-muted-foreground">Aucun cours disponible.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Mes badges</CardTitle>
                    <CardDescription>Récompenses débloquées.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2">
                    <div v-for="b in badges" :key="b.code" class="flex items-center gap-3 rounded-lg border p-2">
                        <span class="text-2xl">{{ b.icon }}</span>
                        <div>
                            <div class="text-sm font-medium">{{ b.label }}</div>
                            <div v-if="b.description" class="text-xs text-muted-foreground">{{ b.description }}</div>
                        </div>
                    </div>
                    <p v-if="!badges.length" class="text-sm text-muted-foreground">Aucun badge. Terminez des pages et des quiz pour en gagner&nbsp;!</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconStar, IconBook, IconCircleCheck, IconTrophy, IconUser } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { useGamificationStore } from '@/stores/gamification'
import { useAuthStore } from '@/stores/auth'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const gam = useGamificationStore()
const auth = useAuthStore()

const courses = computed(() => store.courses)
const points = computed(() => auth.user?.points ?? 0)
const badges = computed(() => auth.user?.badges || [])
const totalPages = computed(() => courses.value.reduce((a, c) => a + gam.coursePageIds(c).length, 0))
const donePages = computed(() => gam.completedPageIds.length)
</script>
