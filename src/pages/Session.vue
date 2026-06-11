<template>
    <AppLayout>
        <div v-if="course && session" class="grid gap-6 lg:grid-cols-3">
            <main class="space-y-4 lg:col-span-2">
                <!-- header -->
                <Card class="overflow-hidden pt-0">
                    <div class="h-1.5 w-full" :style="{ background: accent }"></div>
                    <CardContent class="pt-5">
                        <RouterLink :to="`/course/${course.id}`" class="text-xs text-muted-foreground hover:text-primary">← {{ course.title }}</RouterLink>
                        <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ session.title }}</h1>
                        <p v-if="currentStep" class="mt-1 text-sm text-muted-foreground">
                            Étape {{ stepIndex + 1 }} / {{ steps.length }} · {{ currentStep.chapter }}
                        </p>
                    </CardContent>
                </Card>

                <!-- current step -->
                <template v-if="currentStep">
                    <!-- page -->
                    <Card v-if="currentStep.type === 'page'">
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <CardTitle>{{ currentStep.data.title }}</CardTitle>
                            <Badge v-if="gam.isPageDone(currentStep.id)" variant="default" class="gap-1"><IconCheck class="size-3.5" /> Terminé</Badge>
                        </CardHeader>
                        <CardContent>
                            <MarkdownViewer :source="currentStep.data.content" />
                            <div class="mt-4">
                                <Button v-if="!gam.isPageDone(currentStep.id)" @click="complete(currentStep.data)">
                                    <IconCircleCheck class="size-4" /> Marquer comme terminé (+{{ currentStep.data.points }} pts)
                                </Button>
                                <span v-else class="text-sm text-emerald-600">Page validée ✓</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- evaluation -->
                    <EvaluationPlayer v-else :evaluation="currentStep.data" />
                </template>

                <!-- step navigation -->
                <div class="flex items-center justify-between">
                    <Button variant="outline" :disabled="stepIndex === 0" @click="go(stepIndex - 1)">
                        <IconArrowLeft class="size-4" /> Précédent
                    </Button>
                    <Button v-if="stepIndex < steps.length - 1" @click="go(stepIndex + 1)">
                        Suivant <IconArrowRight class="size-4" />
                    </Button>
                    <RouterLink v-else-if="nextSession" :to="`/course/${course.id}/session/${nextSession.id}`">
                        <Button>Séance suivante <IconArrowRight class="size-4" /></Button>
                    </RouterLink>
                    <span v-else class="text-sm text-muted-foreground">Fin de la séance 🎉</span>
                </div>
            </main>

            <!-- sidebar: stepper -->
            <aside class="lg:col-span-1">
                <Card class="sticky top-24">
                    <CardHeader>
                        <CardTitle class="text-base">Sommaire</CardTitle>
                        <CardDescription>{{ donePages }} / {{ totalPages }} pages terminées</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <Progress :model-value="sessionPct" />

                        <nav class="space-y-3">
                            <div v-for="grp in grouped" :key="grp.chapter">
                                <div class="px-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ grp.chapter }}</div>
                                <button v-for="st in grp.items" :key="st.key" @click="go(st.index)"
                                    class="mt-1 flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition"
                                    :class="st.index === stepIndex ? 'bg-accent font-medium text-accent-foreground' : 'hover:bg-muted'">
                                    <component :is="stepIcon(st)" class="size-4 shrink-0" :class="stepIconClass(st)" />
                                    <span class="flex-1 truncate">{{ st.data.title }}</span>
                                    <span v-if="st.type === 'eval' && gam.evalResult(st.id)" class="text-xs text-muted-foreground">
                                        {{ gam.evalResult(st.id)?.score }}/{{ gam.evalResult(st.id)?.maxScore }}
                                    </span>
                                </button>
                            </div>
                        </nav>

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
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { IconArrowLeft, IconArrowRight, IconCheck, IconCircleCheck, IconCircle, IconClipboardCheck } from '@tabler/icons-vue'
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

interface Step { key: string; index: number; type: 'page' | 'eval'; id: number; chapter: string; data: any }

const route = useRoute()
const store = useCoursesStore()
const gam = useGamificationStore()

const course = computed(() => store.getCourse(route.params.id as string))
const session = computed(() => course.value?.sessions.find((s: any) => String(s.id) === String(route.params.sid)))
const accent = computed(() => course.value?.accentColor || '#7c3aed')

// flatten chapters → ordered steps (pages then evaluations per chapter)
const steps = computed<Step[]>(() => {
    const out: Step[] = []
    let i = 0
    for (const ch of session.value?.chapters || []) {
        for (const p of ch.pages || []) out.push({ key: `p${p.id}`, index: i++, type: 'page', id: Number(p.id), chapter: ch.title, data: p })
        for (const ev of ch.evaluations || []) out.push({ key: `e${ev.id}`, index: i++, type: 'eval', id: Number(ev.id), chapter: ch.title, data: ev })
    }
    return out
})
const grouped = computed(() => {
    const groups: { chapter: string; items: Step[] }[] = []
    for (const st of steps.value) {
        let g = groups[groups.length - 1]
        if (!g || g.chapter !== st.chapter) { g = { chapter: st.chapter, items: [] }; groups.push(g) }
        g.items.push(st)
    }
    return groups
})

const stepIndex = ref(0)
const currentStep = computed(() => steps.value[stepIndex.value] || null)

function go(i: number) {
    stepIndex.value = Math.max(0, Math.min(i, steps.value.length - 1))
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

// on session change, jump to the first incomplete page (else first step)
watch([() => route.params.sid, steps], () => {
    const firstTodo = steps.value.find((s) => s.type === 'page' && !gam.isPageDone(s.id))
    stepIndex.value = firstTodo ? firstTodo.index : 0
}, { immediate: true })

const nextSession = computed(() => {
    const idx = course.value?.sessions.findIndex((s: any) => s.id === session.value?.id) ?? -1
    return idx >= 0 && idx < (course.value?.sessions.length ?? 0) - 1 ? course.value!.sessions[idx + 1] : null
})

const pageIds = computed<number[]>(() => steps.value.filter((s) => s.type === 'page').map((s) => s.id))
const totalPages = computed(() => pageIds.value.length)
const donePages = computed(() => pageIds.value.filter((id) => gam.isPageDone(id)).length)
const sessionPct = computed(() => (totalPages.value ? Math.round((donePages.value / totalPages.value) * 100) : 0))

function stepIcon(st: Step) {
    if (st.type === 'eval') return IconClipboardCheck
    return gam.isPageDone(st.id) ? IconCircleCheck : IconCircle
}
function stepIconClass(st: Step) {
    if (st.type === 'eval') return gam.evalResult(st.id)?.passed ? 'text-emerald-600' : 'text-primary'
    return gam.isPageDone(st.id) ? 'text-emerald-600' : 'text-muted-foreground'
}

async function complete(page: any) {
    try {
        const res = await gam.completePage(page.id)
        if (!res.alreadyDone) {
            showToast(`Page terminée · +${res.pointsEarned} pts`)
            for (const b of res.newBadges) showToast(`${b.icon} Badge débloqué : ${b.label}`, 'success', 5000)
        }
        // auto-advance to the next step
        if (stepIndex.value < steps.value.length - 1) go(stepIndex.value + 1)
    } catch {
        showToast('Action impossible', 'error')
    }
}
</script>
