<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Statistiques — Enseignant</h1>
                <p class="text-sm text-muted-foreground">Vue d'ensemble du contenu pédagogique.</p>
            </div>
            <RouterLink to="/backoffice"><Button variant="outline" size="sm"><IconPencil class="size-4" /> Éditer les cours</Button></RouterLink>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <StatCard :icon="IconBook" label="Cours" :value="totals.courses" tint="indigo" />
            <StatCard :icon="IconLayoutList" label="Séances" :value="totals.sessions" tint="violet" />
            <StatCard :icon="IconFileText" label="Pages" :value="totals.pages" tint="sky" />
            <StatCard :icon="IconClipboardCheck" label="Évaluations" :value="totals.evaluations" tint="emerald" />
            <StatCard :icon="IconHelpCircle" label="Questions" :value="totals.questions" tint="amber" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Pages par cours</CardTitle>
                    <CardDescription>Volume de contenu par formation.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="c in courses" :key="c.id" class="flex items-center gap-3">
                        <div class="w-40 shrink-0 truncate text-sm" :title="c.title">{{ c.title }}</div>
                        <div class="h-6 flex-1 rounded-md bg-muted">
                            <div class="grid h-6 place-items-end rounded-md bg-brand-gradient pr-2 text-xs font-medium text-white"
                                :style="{ width: barWidth(pagesOf(c)) }">{{ pagesOf(c) }}</div>
                        </div>
                    </div>
                    <p v-if="!courses.length" class="text-sm text-muted-foreground">Aucun cours.</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Détail par cours</CardTitle>
                    <CardDescription>Catégorie, séances, évaluations, points à gagner.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-for="c in courses" :key="c.id" class="flex items-center justify-between gap-3 border-b pb-2 last:border-0">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ c.title }}</div>
                            <div class="text-xs text-muted-foreground">{{ c.sessions.length }} séances · {{ evalsOf(c) }} évals</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge v-if="c.category && c.category !== 'other'" variant="secondary" class="uppercase">{{ c.category }}</Badge>
                            <Badge variant="outline">{{ pointsOf(c) }} pts</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconBook, IconLayoutList, IconFileText, IconClipboardCheck, IconHelpCircle, IconPencil } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const courses = computed(() => store.courses)

function pagesOf(c: any) { return (c.sessions || []).reduce((a: number, s: any) => a + (s.chapters || []).reduce((b: number, ch: any) => b + (ch.pages?.length || 0), 0), 0) }
function evalsOf(c: any) { return (c.sessions || []).reduce((a: number, s: any) => a + (s.chapters || []).reduce((b: number, ch: any) => b + (ch.evaluations?.length || 0), 0), 0) }
function questionsOf(c: any) { return (c.sessions || []).reduce((a: number, s: any) => a + (s.chapters || []).reduce((b: number, ch: any) => b + (ch.evaluations || []).reduce((q: number, ev: any) => q + (ev.questions?.length || 0), 0), 0), 0) }
function pointsOf(c: any) {
    let pts = 0
    for (const s of c.sessions || []) for (const ch of s.chapters || []) {
        for (const p of ch.pages || []) pts += p.points || 0
        for (const ev of ch.evaluations || []) pts += ev.pointsReward || 0
    }
    return pts
}

const totals = computed(() => ({
    courses: courses.value.length,
    sessions: courses.value.reduce((a, c) => a + c.sessions.length, 0),
    pages: courses.value.reduce((a, c) => a + pagesOf(c), 0),
    evaluations: courses.value.reduce((a, c) => a + evalsOf(c), 0),
    questions: courses.value.reduce((a, c) => a + questionsOf(c), 0),
}))

const maxPages = computed(() => Math.max(1, ...courses.value.map((c) => pagesOf(c))))
function barWidth(n: number) { return `${Math.max(8, Math.round((n / maxPages.value) * 100))}%` }
</script>
