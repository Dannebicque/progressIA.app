<template>
    <Card>
        <CardHeader>
            <div class="flex items-center gap-2">
                <IconClipboardCheck class="size-5 text-primary" />
                <CardTitle class="text-base">{{ evaluation.title }}</CardTitle>
                <Badge v-if="result" :variant="result.passed ? 'default' : 'destructive'" class="ml-auto">
                    {{ result.score }} / {{ result.maxScore }}
                </Badge>
            </div>
            <CardDescription v-if="evaluation.description">{{ evaluation.description }}</CardDescription>
        </CardHeader>
        <CardContent class="space-y-5">
            <div v-for="(q, qi) in evaluation.questions" :key="q.id" class="space-y-2">
                <div class="flex items-start gap-2 text-sm font-medium">
                    <span class="text-muted-foreground">{{ Number(qi) + 1 }}.</span>
                    <span class="flex-1">{{ q.statement }}</span>
                    <span class="shrink-0 text-xs text-muted-foreground">{{ q.points }} pt{{ q.points > 1 ? 's' : '' }}</span>
                    <component v-if="result" :is="perQuestion(q.id)?.correct ? IconCircleCheck : IconCircleX"
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
                <div v-else class="pl-5">
                    <Textarea v-model="free[q.id]" :disabled="!!result" rows="3"
                        placeholder="Votre réponse…" class="text-sm" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Button v-if="!result" :disabled="submitting" @click="submit">
                    <IconLoader2 v-if="submitting" class="size-4 animate-spin" /> Valider mes réponses
                </Button>
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
import { ref, reactive } from 'vue'
import { IconClipboardCheck, IconCircleCheck, IconCircleX, IconLoader2 } from '@tabler/icons-vue'
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
const submitting = ref(false)
const result = ref<any | null>(null)

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

async function submit() {
    submitting.value = true
    try {
        const answers = props.evaluation.questions.map((q: any) =>
            q.type === 'qcm'
                ? { question: q.id, choices: choices[q.id] || [] }
                : { question: q.id, text: free[q.id] || '' },
        )
        const res = await gam.submitEvaluation(props.evaluation.id, answers)
        result.value = res
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
}
</script>
