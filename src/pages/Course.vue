<template>
    <AppLayout>
        <div v-if="course" class="space-y-6">
            <!-- header -->
            <Card class="overflow-hidden pt-0">
                <div class="h-2 w-full" :style="{ background: accent }"></div>
                <CardContent class="flex flex-col gap-6 pt-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="grid size-16 shrink-0 place-items-center rounded-2xl text-white"
                            :style="{ background: `linear-gradient(135deg, ${accent}, ${accent}99)` }">
                            <IconSchool class="size-8" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-bold tracking-tight">{{ course.title }}</h1>
                                <Badge v-if="course.category && course.category !== 'other'" variant="secondary" class="uppercase">{{ course.category }}</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ course.theme }}<span v-if="course.context"> · {{ course.context }}</span></p>
                            <p class="mt-2 max-w-2xl text-sm">{{ course.scenario }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-auto">
                        <Stat label="Séances" :value="course.sessions.length" />
                        <Stat label="Pages" :value="totalPages" />
                        <Stat label="Pts à gagner" :value="totalPoints" />
                        <Stat label="Progression" :value="`${pct}%`" :accent="accent" />
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- sessions -->
                <div class="space-y-3 lg:col-span-2">
                    <h2 class="text-lg font-semibold tracking-tight">Séances</h2>
                    <Card v-for="(s, i) in course.sessions" :key="s.id">
                        <CardContent class="flex items-center justify-between gap-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-accent text-sm font-semibold text-accent-foreground">{{ Number(i) + 1 }}</div>
                                <div>
                                    <div class="font-medium">{{ s.title }}</div>
                                    <div class="text-sm text-muted-foreground">{{ chapterCount(s) }} chapitres · {{ pageCount(s) }} pages</div>
                                </div>
                            </div>
                            <RouterLink :to="`/course/${course.id}/session/${s.id}`">
                                <Button size="sm">Commencer <IconArrowRight class="size-4" /></Button>
                            </RouterLink>
                        </CardContent>
                    </Card>
                    <Card v-if="!course.sessions.length" class="grid place-items-center py-10 text-center text-muted-foreground">
                        Aucune séance pour ce cours.
                    </Card>
                </div>

                <!-- aside -->
                <aside class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Ma progression</CardTitle>
                            <CardDescription>{{ donePages }} / {{ totalPages }} pages terminées</CardDescription>
                        </CardHeader>
                        <CardContent><Progress :model-value="pct" /></CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle class="text-base">Mes badges</CardTitle></CardHeader>
                        <CardContent class="flex flex-wrap gap-2">
                            <Badge v-for="b in badges" :key="b.code" variant="secondary" class="gap-1">
                                <span>{{ b.icon }}</span>{{ b.label }}
                            </Badge>
                            <p v-if="!badges.length" class="text-sm text-muted-foreground">Aucun badge pour le moment.</p>
                        </CardContent>
                    </Card>
                </aside>
            </div>
        </div>

        <Card v-else class="grid place-items-center py-16 text-center text-muted-foreground">Cours introuvable.</Card>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { IconSchool, IconArrowRight } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import Stat from '@/components/StatPill.vue'
import { useCoursesStore } from '@/stores/courses'
import { useGamificationStore } from '@/stores/gamification'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const route = useRoute()
const store = useCoursesStore()
const gam = useGamificationStore()
const auth = useAuthStore()

const course = computed(() => store.getCourse(route.params.id as string))
const accent = computed(() => course.value?.accentColor || '#7c3aed')
const badges = computed(() => auth.user?.badges || [])

function chapterCount(s: any) { return s.chapters?.length || 0 }
function pageCount(s: any) { return (s.chapters || []).reduce((a: number, ch: any) => a + (ch.pages?.length || 0), 0) }

const totalPages = computed(() => (course.value ? gam.coursePageIds(course.value).length : 0))
const donePages = computed(() => (course.value ? gam.coursePageIds(course.value).filter((id) => gam.isPageDone(id)).length : 0))
const pct = computed(() => (course.value ? gam.coursePct(course.value) : 0))
const totalPoints = computed(() => {
    if (!course.value) return 0
    let pts = 0
    for (const s of course.value.sessions || [])
        for (const ch of s.chapters || []) {
            for (const p of ch.pages || []) pts += p.points || 0
            for (const ev of ch.evaluations || []) pts += ev.pointsReward || 0
        }
    return pts
})
</script>
