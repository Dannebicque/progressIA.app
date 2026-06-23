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
                        <Stat label="Séances" :value="visibleSessions.length" />
                        <Stat label="Pages" :value="totalPages" />
                        <Stat label="Pts à gagner" :value="totalPoints" />
                        <Stat label="Progression" :value="`${pct}%`" :accent="accent" />
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- sessions -->
                <div class="space-y-3 lg:col-span-2">
                    <!-- Narrative / Scenario Bubble -->
                    <div v-if="course.scenario" 
                        class="relative overflow-hidden rounded-2xl border p-5"
                        :style="{
                            borderColor: accent + '33',
                            background: `linear-gradient(to right, ${accent}14, ${accent}08, transparent)`
                        }">
                        <div class="absolute -right-6 -bottom-6 size-24 opacity-20 pointer-events-none" :style="{ color: accent }">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-full"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" /></svg>
                        </div>
                        <div class="flex items-start gap-4">
                            <div class="grid size-12 shrink-0 place-items-center rounded-xl" :style="{ backgroundColor: accent + '1a' }">
                                <IconSparkles class="size-6" :style="{ color: accent }" />
                            </div>
                            <div class="space-y-1">
                                <div class="text-xs font-semibold uppercase tracking-wider" :style="{ color: accent }">Le Contexte & L'Histoire</div>
                                <p class="text-sm italic text-muted-foreground leading-relaxed">
                                    « {{ course.scenario }} »
                                </p>
                            </div>
                        </div>
                    </div>

                    <h2 class="text-lg font-semibold tracking-tight">Séances</h2>
                    <Card v-for="(s, i) in visibleSessions" :key="s.id">
                        <CardContent class="flex items-center justify-between gap-4 py-4">
                            <div class="flex items-center gap-3">
                                <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-accent text-sm font-semibold text-accent-foreground">{{ Number(i) + 1 }}</div>
                                <div>
                                    <div class="font-medium">{{ s.title }}</div>
                                    <div class="text-sm text-muted-foreground">{{ pluralize(chapterCount(s), 'chapitre') }} · {{ pluralize(pageCount(s), 'page') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <!-- Progress Indicator -->
                                <div v-if="auth.isAuthenticated && !auth.isTeacher()" class="hidden sm:block">
                                    <span v-if="sessionPct(s) === 100" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                                        <svg class="size-3.5 fill-emerald-600" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.8-11.2a1 1 0 0 0-1.4-1.4L9 8.6 7.6 7.2a1 1 0 0 0-1.4 1.4l2.1 2.1a1 1 0 0 0 1.4 0l3.8-3.9Z" clip-rule="evenodd" /></svg>
                                        Complété
                                    </span>
                                    <span v-else-if="sessionDonePageCount(s) > 0" class="inline-flex items-center gap-1 text-xs font-semibold text-primary bg-primary/10 px-2.5 py-1 rounded-full border border-primary/20">
                                        {{ sessionDonePageCount(s) }} / {{ pageCount(s) }} pages
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1 text-xs font-medium text-muted-foreground bg-muted px-2.5 py-1 rounded-full">
                                        Pas commencé
                                    </span>
                                </div>
                                
                                <RouterLink :to="`/course/${course.id}/session/${s.id}`">
                                    <Button size="sm">
                                        <span v-if="auth.isAuthenticated && !auth.isTeacher()">
                                            {{ sessionPct(s) === 100 ? 'Revoir' : (sessionDonePageCount(s) > 0 ? 'Continuer' : 'Commencer') }}
                                        </span>
                                        <span v-else>Commencer</span>
                                        <IconArrowRight class="size-4 ml-1" />
                                    </Button>
                                </RouterLink>
                            </div>
                        </CardContent>
                    </Card>
                    <Card v-if="!visibleSessions.length" class="grid place-items-center py-10 text-center text-muted-foreground">
                        Aucune séance pour ce cours.
                    </Card>
                </div>

                <!-- aside -->
                <aside class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Ma progression</CardTitle>
                            <CardDescription>{{ donePages }} / {{ totalPages }} {{ totalPages >= 2 ? 'pages terminées' : 'page terminée' }}</CardDescription>
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
import { IconSchool, IconArrowRight, IconSparkles } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import Stat from '@/components/StatPill.vue'
import { pluralize } from '@/lib/format'
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
const visibleSessions = computed(() => {
    if (!course.value?.sessions) return []
    return course.value.sessions.filter((s: any) => s.visible !== false)
})
const accent = computed(() => course.value?.accentColor || '#7c3aed')
const badges = computed(() => auth.user?.badges || [])

function chapterCount(s: any) { return s.chapters?.length || 0 }
function pageCount(s: any) { return (s.chapters || []).reduce((a: number, ch: any) => a + (ch.pages?.length || 0), 0) }

function sessionDonePageCount(s: any) {
    return (s.chapters || []).reduce((a: number, ch: any) => {
        return a + (ch.pages || []).filter((p: any) => gam.isPageDone(p.id)).length
    }, 0)
}

function sessionPct(s: any) {
    const total = pageCount(s)
    if (!total) return 0
    return Math.round((sessionDonePageCount(s) / total) * 100)
}

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
