<template>
    <div v-if="evalData" class="space-y-4 rounded-lg border bg-muted/30 p-4">
        <!-- evaluation fields -->
        <div class="grid gap-3 sm:grid-cols-3">
            <div class="space-y-1.5 sm:col-span-2"><Label>Titre de l'évaluation</Label><Input v-model="title" /></div>
            <div class="space-y-1.5"><Label>Bonus (points)</Label><Input type="number" min="0" v-model.number="pointsReward" /></div>
            <div class="space-y-1.5 sm:col-span-3"><Label>Description</Label><Input v-model="description" placeholder="Optionnel" /></div>
        </div>
        <div class="flex gap-2">
            <Button size="sm" @click="saveEval">Enregistrer l'évaluation</Button>
            <Button size="sm" variant="ghost" class="text-destructive" @click="$emit('delete')"><IconTrash class="size-4" /> Supprimer</Button>
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
                    <Button size="icon-sm" variant="ghost" class="text-destructive" @click="removeChoice(q, c)"><IconX class="size-4" /></Button>
                </div>
                <Button size="sm" variant="outline" @click="addChoice(q)"><IconPlus class="size-4" /> Ajouter un choix</Button>
            </div>
        </div>

        <Button size="sm" variant="outline" @click="addQuestion"><IconPlus class="size-4" /> Ajouter une question</Button>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
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

async function reload() {
    evalData.value = await store.fetchEvaluationAdmin(props.evaluationId)
    title.value = evalData.value.title
    description.value = evalData.value.description || ''
    pointsReward.value = evalData.value.pointsReward
}
watch(() => props.evaluationId, reload, { immediate: true })

async function saveEval() {
    await store.updateEvaluation(props.evaluationId, { title: title.value, description: description.value, pointsReward: pointsReward.value })
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
