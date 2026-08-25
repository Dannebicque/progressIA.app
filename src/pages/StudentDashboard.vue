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
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <h2 class="text-lg font-semibold tracking-tight">Mes cours</h2>
                </div>

                <!-- Search and Filters -->
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <IconSearch class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="q" placeholder="Rechercher un cours..." class="pl-9" />
                    </div>
                    <Select v-model="category">
                        <SelectTrigger class="sm:w-44"><SelectValue placeholder="Catégorie" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Toutes catégories</SelectItem>
                            <SelectItem value="back">Backend</SelectItem>
                            <SelectItem value="front">Frontend</SelectItem>
                            <SelectItem value="fullstack">Fullstack</SelectItem>
                            <SelectItem value="other">Autre</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="level">
                        <SelectTrigger class="sm:w-40"><SelectValue placeholder="Niveau" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous niveaux</SelectItem>
                            <SelectItem value="Débutant">Débutant</SelectItem>
                            <SelectItem value="Intermédiaire">Intermédiaire</SelectItem>
                            <SelectItem value="Avancé">Avancé</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Course list -->
                <div v-if="paginatedCourses.length" class="space-y-4">
                    <Card v-for="c in paginatedCourses" :key="c.id" class="border-l-4" :style="{ borderLeftColor: c.accentColor || '#7c3aed' }">
                        <CardContent class="py-5">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <div class="font-semibold">{{ c.title }}</div>
                                    <Badge v-if="c.category && c.category !== 'other'" variant="secondary" class="uppercase">{{ c.category }}</Badge>
                                </div>
                                <RouterLink :to="`/course/${c.id}`"><Button size="sm" variant="outline">Ouvrir</Button></RouterLink>
                            </div>
                            <p v-if="c.scenario" class="mt-2 text-sm text-muted-foreground line-clamp-2">{{ c.scenario }}</p>
                            <div class="mt-4 flex items-center gap-3">
                                <Progress :model-value="gam.coursePct(c)" class="flex-1" />
                                <span class="shrink-0 text-sm text-muted-foreground">{{ gam.coursePct(c) }}%</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Empty State (No match) -->
                <Card v-else-if="store.courses.length" class="grid place-items-center gap-2 py-16 text-center">
                    <IconSearchOff class="size-10 text-muted-foreground" />
                    <p class="font-medium text-lg">Aucun cours ne correspond</p>
                    <p class="text-sm text-muted-foreground max-w-sm">Essayez de modifier vos critères de recherche ou réinitialisez les filtres.</p>
                    <Button variant="outline" size="sm" class="mt-3" @click="resetFilters">Réinitialiser</Button>
                </Card>

                <!-- Empty State (No courses at all) -->
                <Card v-else class="grid place-items-center py-12 text-center text-muted-foreground">
                    Aucun cours disponible.
                </Card>

                <!-- Pagination Controls -->
                <div v-if="totalPages > 1" class="flex items-center justify-between border-t border-border/40 pt-4 mt-2">
                    <p class="text-sm text-muted-foreground">
                        Page {{ currentPage }} sur {{ totalPages }} ({{ filteredCourses.length }} cours)
                    </p>
                    <div class="flex gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="currentPage === 1"
                            @click="currentPage--"
                        >
                            Précédent
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="currentPage === totalPages"
                            @click="currentPage++"
                        >
                            Suivant
                        </Button>
                    </div>
                </div>
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

                <!-- Leaderboard Widget -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base flex items-center gap-1.5">
                            <span>🏆</span>
                            <span>Classement de l'école</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0 border-t">
                        <div v-if="loadingLeaderboard" class="p-4 text-center text-xs text-muted-foreground">
                            Chargement...
                        </div>
                        <div v-else class="divide-y text-xs">
                            <div
                                v-for="item in leaderboard"
                                :key="item.rank"
                                :class="[
                                    'flex items-center justify-between p-2.5 px-4',
                                    item.isCurrentUser ? 'bg-indigo-500/10 font-bold border-y border-indigo-500/20' : ''
                                ]"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="w-6 font-mono text-center text-muted-foreground">
                                        <span v-if="item.rank === 1">🥇</span>
                                        <span v-else-if="item.rank === 2">🥈</span>
                                        <span v-else-if="item.rank === 3">🥉</span>
                                        <span v-else>#{{ item.rank }}</span>
                                    </span>
                                    <span class="truncate max-w-[120px]" :class="item.isCurrentUser ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                        {{ item.name }}
                                    </span>
                                </div>
                                <span class="font-semibold text-muted-foreground shrink-0">{{ item.points }} pts</span>
                            </div>
                            <p v-if="!leaderboard.length" class="p-4 text-center text-xs text-muted-foreground italic">
                                Aucun participant.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- AI Feedback Widget -->
                <Card v-if="evalsWithFeedback.length">
                    <CardHeader>
                        <CardTitle class="text-sm font-semibold flex items-center gap-1.5 text-violet-600 dark:text-violet-400">
                            <IconSparkles class="size-4 animate-pulse" />
                            <span>Derniers retours IA</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="ev in evalsWithFeedback" :key="ev.evaluation" class="p-3 border rounded-xl bg-violet-50/5 hover:bg-violet-50/10 cursor-pointer transition flex flex-col gap-1" @click="openFeedback(ev)">
                            <div class="font-semibold text-xs text-foreground line-clamp-1">{{ getEvalTitle(ev.evaluation) }}</div>
                            <div class="text-[10px] text-muted-foreground flex justify-between items-center mt-1">
                                <span>Score : {{ ev.score }} / {{ ev.maxScore }}</span>
                                <span class="text-violet-600 dark:text-violet-400 font-bold hover:underline">Lire l'avis →</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </aside>
        </div>

        <!-- AI Feedback Dialog -->
        <Dialog :open="isAIModalOpen" @update:open="(v: any) => (isAIModalOpen = v)">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                        <IconBrain class="size-5" />
                        <span>Retour IA — {{ selectedEval?.title }}</span>
                    </DialogTitle>
                    <DialogDescription>
                        Avis rédigé par l'intelligence artificielle sur votre tentative.
                    </DialogDescription>
                </DialogHeader>
                
                <div v-if="selectedEval" class="py-2">
                    <EvaluationPlayer :evaluation="selectedEval" />
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { IconStar, IconChartBar, IconSearch, IconSearchOff, IconSparkles, IconBrain } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import StatCard from '@/components/StatCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { useGamificationStore, type EvalProgress } from '@/stores/gamification'
import { useAuthStore } from '@/stores/auth'
import { api } from '@/api/client'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import EvaluationPlayer from '@/components/EvaluationPlayer.vue'

const store = useCoursesStore()
const gam = useGamificationStore()
const auth = useAuthStore()

const points = computed(() => auth.user?.points ?? 0)
const badges = computed(() => auth.user?.badges || [])

interface LeaderboardItem {
    rank: number
    name: string
    points: number
    isCurrentUser: boolean
}

const leaderboard = ref<LeaderboardItem[]>([])
const loadingLeaderboard = ref(false)

async function loadLeaderboard() {
    loadingLeaderboard.value = true
    try {
        leaderboard.value = await api.get<LeaderboardItem[]>('/api/me/leaderboard')
    } catch (e) {
        console.error('Failed to load leaderboard', e)
    } finally {
        loadingLeaderboard.value = false
    }
}

onMounted(() => {
    loadLeaderboard()
})

// AI Feedback Dialog state
const isAIModalOpen = ref(false)
const selectedEval = ref<Record<string, unknown> | null>(null)

const evalsWithFeedback = computed(() => {
    return gam.evaluations.filter((e) => !!e.feedbackStudent)
})

function findEvaluation(evalId: number) {
    for (const c of store.courses) {
        for (const s of c.sessions || []) {
            for (const ch of s.chapters || []) {
                for (const p of ch.pages || []) {
                    if (p.type === 'evaluation' && p.data && Number(p.data.id) === Number(evalId)) {
                        return p.data
                    }
                }
            }
        }
    }
    return null
}

function getEvalTitle(evalId: number): string {
    const evalObj = findEvaluation(evalId)
    return evalObj?.title || `Évaluation #${evalId}`
}

function openFeedback(progressEntry: EvalProgress) {
    const evalObj = findEvaluation(progressEntry.evaluation)
    if (evalObj) {
        selectedEval.value = evalObj as Record<string, unknown>
        isAIModalOpen.value = true
    }
}

// Filter and Pagination State
const q = ref('')
const category = ref('all')
const level = ref('all')
const currentPage = ref(1)
const itemsPerPage = 5

// Reset page when search or filters change
watch([q, category, level], () => {
    currentPage.value = 1
})

const filteredCourses = computed(() => {
    const term = q.value.trim().toLowerCase()
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    return store.courses.filter((c: any) => {
        if (c.visible === false) return false
        if (category.value !== 'all' && (c.category || 'other') !== category.value) return false
        if (level.value !== 'all' && (c.level || '') !== level.value) return false
        if (term && !`${c.title} ${c.theme} ${c.scenario}`.toLowerCase().includes(term)) return false
        return true
    })
})

const paginatedCourses = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage
    return filteredCourses.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => Math.ceil(filteredCourses.value.length / itemsPerPage))

function resetFilters() {
    q.value = ''
    category.value = 'all'
    level.value = 'all'
}
</script>
