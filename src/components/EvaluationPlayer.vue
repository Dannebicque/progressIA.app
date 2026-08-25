22<template>
    <Card>
        <CardHeader>
            <div class="flex items-center gap-2">
                <IconClipboardCheck class="size-5 text-primary" />
                <CardTitle class="text-base">{{ evaluation.title }}</CardTitle>
                <Badge v-if="evaluation.type === 'tree' && !result" variant="outline" class="ml-auto">
                    Question {{ history.length + 1 }}
                </Badge>
                <Badge v-if="result" :variant="result.passed ? 'default' : 'destructive'" :class="evaluation.type === 'tree' ? '' : 'ml-auto'">
                    {{ result.score }} / {{ result.maxScore }}
                </Badge>
            </div>
            <CardDescription v-if="evaluation.description">{{ evaluation.description }}</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
            <div v-for="(q, qi) in visibleQuestions" :key="q.id" class="space-y-2">
                <div class="flex items-start gap-2 text-sm font-medium">
                    <span class="text-muted-foreground">{{ evaluation.type === 'tree' && !result ? (history.length + 1) : (Number(qi) + 1) }}.</span>
                    <span class="flex-1">{{ q.statement }}</span>
                    <span class="shrink-0 text-xs text-muted-foreground">{{ q.points }} pt{{ q.points > 1 ? 's' : '' }}</span>
                    <component v-if="result && perQuestion(q.id)" :is="perQuestion(q.id)?.correct ? IconCircleCheck : IconCircleX"
                        class="size-4 shrink-0" :class="perQuestion(q.id)?.correct ? 'text-emerald-600' : 'text-destructive'" />
                </div>

                <!-- QCM -->
                <div v-if="q.type === 'qcm'" class="space-y-1.5 pl-5">
                    <label v-for="c in q.choices" :key="c.id"
                        class="flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm transition"
                        :class="picked(q.id).includes(c.id) ? 'border-primary bg-accent' : 'hover:bg-muted'">
                        <Checkbox :model-value="picked(q.id).includes(c.id)" :disabled="!!result"
                            @update:model-value="() => toggle(q, c.id)" />
                        {{ c.text }}
                    </label>
                    <p v-if="q.multiple" class="pl-1 text-xs text-muted-foreground">Plusieurs réponses possibles.</p>
                </div>

                <!-- Free -->
                <div v-else-if="q.type === 'free'" class="pl-5 space-y-3">
                    <Textarea v-model="free[q.id]" :disabled="!!result" rows="3"
                        placeholder="Votre réponse…" class="text-sm" />
                </div>

                <!-- File -->
                <div v-else-if="q.type === 'file'" class="pl-5 space-y-3">
                    <!-- File upload simulation -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold text-muted-foreground uppercase tracking-wider">Document joint (obligatoire)</label>
                        <div v-if="files[q.id] || perQuestionAnswer(q.id)?.file" class="flex items-center gap-2 p-2.5 rounded-lg border bg-violet-50/5 border-violet-100 dark:border-violet-950/40 text-xs text-foreground max-w-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 text-violet-600 dark:text-violet-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                            <span class="font-medium truncate flex-1">{{ files[q.id] || perQuestionAnswer(q.id)?.file }}</span>
                            <Button v-if="!result" variant="ghost" size="icon" class="size-6 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 shrink-0 rounded-full" @click="removeFile(q.id)">
                                <IconX class="size-3.5" />
                            </Button>
                        </div>
                        <div v-else class="flex items-center gap-3 flex-wrap">
                            <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-muted transition text-foreground bg-background">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                <span>Déposer un document</span>
                                <input type="file" class="hidden" :disabled="!!result" @change="(e) => handleFileUpload(q.id, e)" />
                            </label>
                            <span class="text-[10px] text-muted-foreground">PDF, ZIP, DOCX, etc. (max. 10 Mo)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Feedback -->
            <div v-if="aiFeedback" class="p-4 rounded-xl border border-violet-100 dark:border-violet-950/40 bg-violet-50/50 dark:bg-violet-950/10 space-y-1.5 transition-all">
                <div class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-wider">
                    <IconSparkles class="size-4 animate-pulse" />
                    <span>Retour personnalisé de l'IA</span>
                </div>
                <p class="text-sm text-foreground whitespace-pre-wrap leading-relaxed">{{ aiFeedback }}</p>
            </div>

            <div class="flex items-center gap-3">
                <template v-if="!result">
                    <Button v-if="evaluation.type === 'tree'" variant="outline" :disabled="history.length === 0" @click="handlePrev">
                        Précédent
                    </Button>
                    <Button v-if="evaluation.type === 'tree' && getNextQId() !== null" @click="handleNext">
                        Continuer
                    </Button>
                    <Button v-if="evaluation.type !== 'tree' || getNextQId() === null" :disabled="submitting" @click="submit">
                        <IconLoader2 v-if="submitting" class="size-4 animate-spin" /> Valider mes réponses
                    </Button>
                </template>
                <template v-else>
                    <Button variant="outline" @click="retry">Recommencer</Button>
                    <span class="text-sm" :class="result.passed ? 'text-emerald-600' : 'text-destructive'">
                        {{ result.passed ? 'Réussi !' : 'Échoué' }}
                        <span v-if="result.pointsEarned > 0" class="text-amber-600">· +{{ result.pointsEarned }} pts</span>
                    </span>
                </template>
            </div>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { IconClipboardCheck, IconCircleCheck, IconCircleX, IconLoader2, IconSparkles, IconX } from '@tabler/icons-vue'
import { useGamificationStore } from '@/stores/gamification'
import { showToast } from '@/composables/useToast'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const props = defineProps<{ evaluation: any }>()
const gam = useGamificationStore()

const choices = reactive<Record<number, number[]>>({})
const free = reactive<Record<number, string>>({})
const files = reactive<Record<number, string>>({})
const submitting = ref(false)
const result = ref<any | null>(null)

const currentQId = ref<number | null>(null)
const history = ref<number[]>([])

const sortedQuestions = computed(() => {
    return [...(props.evaluation?.questions || [])].sort((a: any, b: any) => a.position - b.position)
})

const activeQuestion = computed(() => {
    if (!sortedQuestions.value.length) return null
    if (currentQId.value === null) {
        return sortedQuestions.value[0]
    }
    return sortedQuestions.value.find((q) => q.id === currentQId.value) || sortedQuestions.value[0]
})

const visibleQuestions = computed(() => {
    if (result.value?.results) {
        return sortedQuestions.value.filter((q) => result.value.results.some((r: any) => Number(r.question) === Number(q.id)))
    }
    if (props.evaluation.type === 'tree') {
        return activeQuestion.value ? [activeQuestion.value] : []
    }
    return sortedQuestions.value
})

const aiFeedback = computed(() => {
    return result.value?.feedbackStudent || gam.evalResult(props.evaluation.id)?.feedbackStudent || null
})

function loadSaved() {
    const saved = gam.evalResult(props.evaluation.id)
    if (saved) {
        result.value = {
            score: saved.score,
            maxScore: saved.maxScore,
            passed: saved.passed,
            feedbackStudent: saved.feedbackStudent,
            results: saved.answers
        }
        if (saved.answers) {
            for (const ans of saved.answers) {
                const qid = Number(ans.question)
                if (ans.choices) {
                    choices[qid] = ans.choices as number[]
                }
                if (ans.text) {
                    free[qid] = ans.text as string
                }
                if (ans.file) {
                    files[qid] = ans.file as string
                }
            }
        }
        currentQId.value = sortedQuestions.value[0]?.id || null
        history.value = []
    } else {
        result.value = null
        for (const k of Object.keys(choices)) delete choices[Number(k)]
        for (const k of Object.keys(free)) delete free[Number(k)]
        for (const k of Object.keys(files)) delete files[Number(k)]
        currentQId.value = sortedQuestions.value[0]?.id || null
        history.value = []
    }
}

watch([() => props.evaluation.id, () => gam.evalResult(props.evaluation.id)], loadSaved, { immediate: true })

function picked(qid: number): number[] {
    return choices[qid] || []
}
function toggle(q: any, choiceId: number) {
    if (result.value) return
    const cur = choices[q.id] || []
    if (q.multiple) {
        choices[q.id] = cur.includes(choiceId) ? cur.filter((c) => c !== choiceId) : [...cur, choiceId]
    } else {
        choices[q.id] = cur.includes(choiceId) ? [] : [choiceId]
    }
}
function perQuestion(qid: number) {
    return result.value?.results?.find((r: any) => Number(r.question) === Number(qid)) || null
}
function perQuestionAnswer(qid: number) {
    const saved = gam.evalResult(props.evaluation.id)
    const activeResult = result.value?.answers || saved?.answers
    return activeResult?.find((a: any) => Number(a.question) === Number(qid)) || null
}

function handleFileUpload(qid: number, event: Event) {
    const target = event.target as HTMLInputElement
    if (target.files && target.files.length > 0 && target.files[0]) {
        files[qid] = target.files[0].name
    }
}
function removeFile(qid: number) {
    delete files[qid]
}

function getNextQId() {
    const q = activeQuestion.value
    if (!q) return null

    let targetId: number | null = null

    if (q.type === 'qcm') {
        const picked = choices[q.id] || []
        for (const cid of picked) {
            const choiceObj = q.choices?.find((c: any) => c.id === cid)
            if (choiceObj) {
                const rawNext = choiceObj.nextQuestion
                if (rawNext) {
                    targetId = typeof rawNext === 'object' ? rawNext.id : Number(String(rawNext).split('/').pop())
                    break
                }
            }
        }
    }

    if (targetId !== null) {
        return targetId
    }

    const curIdx = sortedQuestions.value.findIndex((x) => x.id === q.id)
    if (curIdx >= 0 && curIdx < sortedQuestions.value.length - 1) {
        return sortedQuestions.value[curIdx + 1].id
    }

    return null
}

function handleNext() {
    const q = activeQuestion.value
    if (!q) return

    if (q.type === 'file' && !files[q.id]) {
        showToast(`Veuillez joindre le document demandé.`, 'warning')
        return
    }

    const nextId = getNextQId()
    if (nextId !== null) {
        history.value.push(q.id)
        currentQId.value = nextId
    }
}

function handlePrev() {
    if (history.value.length > 0) {
        currentQId.value = history.value.pop() || null
    }
}

async function submit() {
    const visitedQIds = props.evaluation.type === 'tree' ? [...history.value, currentQId.value!] : sortedQuestions.value.map(q => q.id)

    // Check if files are missing for required file questions in visited path
    for (const q of props.evaluation.questions) {
        if (visitedQIds.includes(q.id) && q.type === 'file' && !files[q.id]) {
            showToast(`Veuillez joindre le document demandé pour la question : "${q.statement}"`, 'warning')
            return
        }
    }

    submitting.value = true
    try {
        const answers = props.evaluation.questions
            .filter((q: any) => visitedQIds.includes(q.id))
            .map((q: any) =>
                q.type === 'qcm'
                    ? { question: q.id, choices: choices[q.id] || [] }
                    : q.type === 'file'
                    ? { question: q.id, file: files[q.id] || '' }
                    : { question: q.id, text: free[q.id] || '' },
            )
        const res = await gam.submitEvaluation(props.evaluation.id, answers)
        result.value = {
            ...res,
            answers
        }
        showToast(res.passed ? `Évaluation réussie (${res.score}/${res.maxScore})` : `Score : ${res.score}/${res.maxScore}`, res.passed ? 'success' : 'warning')
        for (const b of res.newBadges) showToast(`${b.icon} Badge débloqué : ${b.label}`, 'success', 5000)
    } catch {
        showToast('Soumission impossible', 'error')
    } finally {
        submitting.value = false
    }
}
function retry() {
    result.value = null
    for (const k of Object.keys(choices)) delete choices[Number(k)]
    for (const k of Object.keys(free)) delete free[Number(k)]
    for (const k of Object.keys(files)) delete files[Number(k)]
    currentQId.value = sortedQuestions.value[0]?.id || null
    history.value = []
}
</script>
