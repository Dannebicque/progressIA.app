<template>
    <div v-if="evalData" class="space-y-4 rounded-lg border bg-muted/30 p-4">
        <!-- evaluation fields -->
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="space-y-1.5 sm:col-span-2"><Label>Titre de l'évaluation</Label><Input v-model="title" /></div>
            <div class="space-y-1.5"><Label>Type d'évaluation</Label>
                <Select v-model="type">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="linear">Classique (Linéaire)</SelectItem>
                        <SelectItem value="tree">Arbre de décision / Adaptatif</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="space-y-1.5"><Label>Bonus (points)</Label><Input type="number" min="0" v-model.number="pointsReward" /></div>
            <div class="space-y-1.5 sm:col-span-4"><Label>Description</Label><Input v-model="description" placeholder="Optionnel" /></div>
        </div>
        <div class="flex gap-2">
            <Button size="sm" @click="saveEval">Enregistrer l'évaluation</Button>
            <Button size="sm" variant="ghost" class="text-destructive" @click="$emit('delete')"><IconTrash class="size-4" /> Supprimer</Button>
        </div>

        <div v-if="type === 'tree' && (diagnostics.errors.length || diagnostics.warnings.length)" class="space-y-1.5 p-3 rounded-lg border border-amber-200 bg-amber-50/50 text-xs text-amber-800 dark:border-amber-950/40 dark:bg-amber-950/10 dark:text-amber-400">
            <div class="font-bold flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <span>Diagnostic de l'Arbre :</span>
            </div>
            <ul class="list-disc pl-4 space-y-0.5">
                <li v-for="err in diagnostics.errors" :key="err" class="text-rose-600 dark:text-rose-400 font-medium">{{ err }}</li>
                <li v-for="wrn in diagnostics.warnings" :key="wrn">{{ wrn }}</li>
            </ul>
        </div>

        <Separator />

        <!-- questions -->
        <div v-for="(q, qi) in evalData.questions" :key="q.id" class="space-y-2 rounded-md border bg-card p-3">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-muted-foreground">Q{{ Number(qi) + 1 }}</span>
                <Select :model-value="q.type" @update:model-value="(v: any) => patchQuestion(q, { type: v })">
                    <SelectTrigger class="w-32"><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="qcm">QCM</SelectItem>
                        <SelectItem value="free">Réponse libre</SelectItem>
                        <SelectItem value="file">Dépôt de fichier</SelectItem>
                    </SelectContent>
                </Select>
                <Input type="number" min="1" class="w-20" :model-value="q.points" @update:model-value="(v: any) => patchQuestion(q, { points: Number(v) })" />
                <label v-if="q.type === 'qcm'" class="flex items-center gap-1.5 text-xs">
                    <Checkbox :model-value="q.multiple" @update:model-value="(v: any) => patchQuestion(q, { multiple: !!v })" /> Multiple
                </label>
                <Button size="icon-sm" variant="ghost" class="ml-auto text-destructive" @click="removeQuestion(q)"><IconTrash class="size-4" /></Button>
            </div>
            <Textarea :model-value="q.statement" rows="2" placeholder="Énoncé de la question"
                @update:model-value="(v: any) => (q.statement = v)" @blur="patchQuestion(q, { statement: q.statement })" />

            <!-- choices (qcm) -->
            <div v-if="q.type === 'qcm'" class="space-y-1.5 pl-2">
                <div v-for="c in q.choices" :key="c.id" class="flex items-center gap-2">
                    <Checkbox :model-value="c.correct" @update:model-value="(v: any) => patchChoice(c, { correct: !!v })" title="Bonne réponse" />
                    <Input :model-value="c.text" class="flex-1" @update:model-value="(v: any) => (c.text = v)" @blur="patchChoice(c, { text: c.text })" />
                    <div v-if="type === 'tree'" class="w-48 shrink-0">
                        <Select :model-value="c.nextQuestion ? (c.nextQuestion.id !== undefined ? String(c.nextQuestion.id) : String(c.nextQuestion).split('/').pop()) : 'none'" 
                            @update:model-value="(v: any) => patchChoice(c, { nextQuestion: v === 'none' ? null : `/api/questions/${v}` })">
                            <SelectTrigger class="h-8 text-xs"><SelectValue placeholder="Question suiv. (défaut)" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Question suiv. (défaut)</SelectItem>
                                <SelectItem v-for="otherQ in otherQuestions(q.id)" :key="otherQ.id" :value="String(otherQ.id)">
                                    Q{{ otherQ.position + 1 }} : {{ truncateText(otherQ.statement, 20) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button size="icon-sm" variant="ghost" class="text-destructive" @click="removeChoice(q, c)"><IconX class="size-4" /></Button>
                </div>
                <Button size="sm" variant="outline" @click="addChoice(q)"><IconPlus class="size-4" /> Ajouter un choix</Button>
            </div>
        </div>

        <Button size="sm" variant="outline" @click="addQuestion"><IconPlus class="size-4" /> Ajouter une question</Button>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { IconTrash, IconPlus, IconX } from '@tabler/icons-vue'
import { useCoursesStore } from '@/stores/courses'
import { showToast } from '@/composables/useToast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Separator } from '@/components/ui/separator'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const props = defineProps<{ evaluationId: number | string }>()
defineEmits(['delete'])
const store = useCoursesStore()

const evalData = ref<any | null>(null)
const title = ref('')
const description = ref('')
const pointsReward = ref(20)
const type = ref('linear')

function otherQuestions(currentQId: number) {
    return evalData.value?.questions?.filter((oq: any) => oq.id !== currentQId) || []
}

function truncateText(str: string, len: number) {
    if (!str) return ''
    return str.length > len ? str.slice(0, len) + '...' : str
}

const diagnostics = computed(() => {
    if (type.value !== 'tree' || !evalData.value?.questions?.length) {
        return { errors: [], warnings: [] }
    }
    
    const questions = evalData.value.questions
    const qMap = new Map<number, any>()
    questions.forEach((q: any) => qMap.set(q.id, q))
    
    const sortedQs = [...questions].sort((a: any, b: any) => a.position - b.position)
    const firstQ = sortedQs[0]
    
    const visited = new Set<number>()
    const errors: string[] = []
    const warnings: string[] = []
    
    const checkPath = (qid: number, currentPath: Set<number>) => {
        if (currentPath.has(qid)) {
            errors.push(`Boucle infinie détectée impliquant la question Q${sortedQs.findIndex(x => x.id === qid) + 1}.`)
            return
        }
        currentPath.add(qid)
        visited.add(qid)
        
        const q = qMap.get(qid)
        if (!q) return
        
        const nextTargets = new Set<number>()
        let hasLinearFallback = false
        
        if (q.type === 'qcm') {
            if (q.choices?.length) {
                for (const c of q.choices) {
                    const targetId = c.nextQuestion?.id || c.nextQuestion
                    if (targetId) {
                        nextTargets.add(Number(targetId))
                    } else {
                        hasLinearFallback = true
                    }
                }
            } else {
                hasLinearFallback = true
            }
        } else {
            hasLinearFallback = true
        }
        
        if (hasLinearFallback) {
            const curIdx = sortedQs.findIndex(x => x.id === qid)
            if (curIdx >= 0 && curIdx < sortedQs.length - 1) {
                nextTargets.add(sortedQs[curIdx + 1].id)
            }
        }
        
        for (const tid of nextTargets) {
            checkPath(tid, new Set(currentPath))
        }
    }
    
    checkPath(firstQ.id, new Set())
    
    sortedQs.forEach((q: any, qi: number) => {
        if (!visited.has(q.id)) {
            warnings.push(`La question Q${qi + 1} ("${truncateText(q.statement, 20)}") est inaccessible dans l'arbre.`)
        }
    })
    
    return { errors, warnings }
})

async function reload() {
    evalData.value = await store.fetchEvaluationAdmin(props.evaluationId)
    title.value = evalData.value.title
    description.value = evalData.value.description || ''
    pointsReward.value = evalData.value.pointsReward
    type.value = evalData.value.type || 'linear'
}
watch(() => props.evaluationId, reload, { immediate: true })

async function saveEval() {
    await store.updateEvaluation(props.evaluationId, { title: title.value, description: description.value, pointsReward: pointsReward.value, type: type.value })
    showToast('Évaluation enregistrée')
    await reload()
}

async function addQuestion() {
    await store.addQuestion(props.evaluationId, { position: evalData.value.questions.length })
    await store.fetchCourses()
    await reload()
}
async function patchQuestion(q: any, patch: any) {
    await store.updateQuestion(q.id, patch)
    Object.assign(q, patch)
}
async function removeQuestion(q: any) {
    await store.deleteQuestion(q.id)
    await store.fetchCourses()
    await reload()
}
async function addChoice(q: any) {
    await store.addChoice(q.id, { text: 'Réponse', position: q.choices.length })
    await store.fetchCourses()
    await reload()
}
async function patchChoice(c: any, patch: any) {
    await store.updateChoice(c.id, patch)
    Object.assign(c, patch)
}
async function removeChoice(q: any, c: any) {
    await store.deleteChoice(c.id)
    await store.fetchCourses()
    await reload()
}
</script>
