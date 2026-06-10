<template>
    <AppLayout>
        <div v-if="course && session" class="grid gap-6 lg:grid-cols-3">
            <main class="space-y-6 lg:col-span-2">
                <!-- header -->
                <Card class="overflow-hidden pt-0">
                    <div class="h-1.5 w-full" :style="{ background: accent }"></div>
                    <CardContent class="flex flex-col gap-4 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <RouterLink :to="`/course/${course.id}`" class="text-xs text-muted-foreground hover:text-primary">← {{ course.title }}</RouterLink>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ session.title }}</h1>
                            <p v-if="session.pitch" class="mt-1 text-sm text-muted-foreground">{{ session.pitch }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <RouterLink v-if="prevSession" :to="`/course/${course.id}/session/${prevSession.id}`">
                                <Button variant="outline" size="sm"><IconArrowLeft class="size-4" /> Préc.</Button>
                            </RouterLink>
                            <RouterLink v-if="nextSession" :to="`/course/${course.id}/session/${nextSession.id}`">
                                <Button size="sm">Suivant <IconArrowRight class="size-4" /></Button>
                            </RouterLink>
                        </div>
                    </CardContent>
                </Card>

                <!-- chapters → pages + evaluations -->
                <template v-for="ch in session.chapters" :key="ch.id">
                    <h2 class="pt-2 text-lg font-semibold tracking-tight">{{ ch.title }}</h2>

                    <Card v-for="page in ch.pages" :id="`page-${page.id}`" :key="`p${page.id}`">
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-base">{{ page.title }}</CardTitle>
                            <Badge v-if="gam.isPageDone(page.id)" variant="default" class="gap-1">
                                <IconCheck class="size-3.5" /> Terminé
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <MarkdownViewer :source="page.content" />
                            <div class="mt-4">
                                <Button v-if="!gam.isPageDone(page.id)" size="sm" variant="outline" @click="complete(page)">
                                    <IconCircleCheck class="size-4" /> Marquer comme terminé (+{{ page.points }} pts)
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    <EvaluationPlayer v-for="ev in ch.evaluations" :key="`e${ev.id}`" :evaluation="ev" />
                </template>
            </main>

            <!-- sidebar -->
            <aside class="lg:col-span-1">
                <Card class="sticky top-24">
                    <CardHeader>
                        <CardTitle class="text-base">Progression de la séance</CardTitle>
                        <CardDescription>{{ donePages }} / {{ totalPages }} pages terminées</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Progress :model-value="sessionPct" />
                        <Separator />
                        <div>
                            <div class="mb-2 text-sm font-medium">Séances du cours</div>
                            <RouterLink v-for="s in course.sessions" :key="s.id" :to="`/course/${course.id}/session/${s.id}`"
                                class="block rounded-md px-2 py-1.5 text-sm transition"
                                :class="s.id === session.id ? 'bg-accent font-medium text-accent-foreground' : 'text-muted-foreground hover:bg-muted'">
                                {{ s.title }}
                            </RouterLink>
                        </div>
                    </CardContent>
                </Card>
            </aside>
        </div>

        <Card v-else class="grid place-items-center py-16 text-center text-muted-foreground">Séance introuvable.</Card>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { IconArrowLeft, IconArrowRight, IconCheck, IconCircleCheck } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import MarkdownViewer from '@/components/MarkdownViewer.vue'
import EvaluationPlayer from '@/components/EvaluationPlayer.vue'
import { useCoursesStore } from '@/stores/courses'
import { useGamificationStore } from '@/stores/gamification'
import { showToast } from '@/composables/useToast'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Separator } from '@/components/ui/separator'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const route = useRoute()
const store = useCoursesStore()
const gam = useGamificationStore()

const course = computed(() => store.getCourse(route.params.id as string))
const session = computed(() => course.value?.sessions.find((s: any) => String(s.id) === String(route.params.sid)))
const accent = computed(() => course.value?.accentColor || '#7c3aed')

const sessionIndex = computed(() => course.value?.sessions.findIndex((s: any) => s.id === session.value?.id) ?? -1)
const prevSession = computed(() => (sessionIndex.value > 0 ? course.value!.sessions[sessionIndex.value - 1] : null))
const nextSession = computed(() => (sessionIndex.value >= 0 && sessionIndex.value < (course.value?.sessions.length ?? 0) - 1 ? course.value!.sessions[sessionIndex.value + 1] : null))

const pageIds = computed<number[]>(() => {
    const ids: number[] = []
    for (const ch of session.value?.chapters || []) for (const p of ch.pages || []) ids.push(Number(p.id))
    return ids
})
const totalPages = computed(() => pageIds.value.length)
const donePages = computed(() => pageIds.value.filter((id) => gam.isPageDone(id)).length)
const sessionPct = computed(() => (totalPages.value ? Math.round((donePages.value / totalPages.value) * 100) : 0))

async function complete(page: any) {
    try {
        const res = await gam.completePage(page.id)
        if (!res.alreadyDone) {
            showToast(`Page terminée · +${res.pointsEarned} pts`)
            for (const b of res.newBadges) showToast(`${b.icon} Badge débloqué : ${b.label}`, 'success', 5000)
        }
    } catch {
        showToast('Action impossible', 'error')
    }
}
</script>
