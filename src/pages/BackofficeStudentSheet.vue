<template>
  <BackofficeLayout>
    <div v-if="loading" class="py-12 text-center text-muted-foreground text-sm">
      Chargement de la fiche étudiant...
    </div>
    <div v-else-if="student" class="space-y-6">
      <!-- Header with back button -->
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
          <RouterLink to="/backoffice/students">
            <Button variant="outline" size="sm">
              ← Retour aux étudiants
            </Button>
          </RouterLink>
          <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ student.name }}</h1>
            <p class="text-xs text-muted-foreground">{{ student.email }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="text-xs py-1 px-3">{{ student.studentGroup || 'Sans groupe' }}</Badge>
          <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">🏆 {{ student.points }} points</span>
        </div>
      </div>

      <!-- General Info Grid -->
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Établissement</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-semibold text-base">{{ student.studentInstitution || 'Non spécifié' }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Promotion / Année</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-semibold text-base">{{ student.studentYear || 'Non spécifiée' }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Badges débloqués</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-semibold text-base">{{ student.badges.length }} badge(s)</p>
          </CardContent>
        </Card>
      </div>

      <!-- Badges detail -->
      <Card v-if="student.badges.length">
        <CardHeader>
          <CardTitle class="text-sm font-semibold">Badges de l'étudiant</CardTitle>
        </CardHeader>
        <CardContent class="flex flex-wrap gap-2">
          <Badge v-for="b in student.badges" :key="b.code" variant="secondary" class="gap-1.5 py-1.5 px-3">
            <span>{{ b.icon }}</span>
            <div class="text-left">
              <div class="font-semibold text-xs leading-none">{{ b.label }}</div>
              <div class="text-[9px] text-muted-foreground mt-0.5 leading-none">{{ b.description }}</div>
            </div>
          </Badge>
        </CardContent>
      </Card>

      <!-- Course stats -->
      <div>
        <h2 class="text-lg font-bold tracking-tight mb-4">Progression dans les cours</h2>
        <div class="grid gap-6 md:grid-cols-2">
          <Card v-for="cs in student.courseStats" :key="cs.courseId" class="flex flex-col">
            <CardHeader class="pb-3 border-b">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                  <span class="inline-block size-3 rounded-full shrink-0" :style="{ backgroundColor: cs.courseAccentColor }"></span>
                  <CardTitle class="text-sm font-bold">{{ cs.courseTitle }}</CardTitle>
                </div>
                <Badge variant="outline" class="text-xs">{{ cs.completedPages }} / {{ cs.totalPages }} pages</Badge>
              </div>
            </CardHeader>
            <CardContent class="pt-4 flex-1 space-y-4">
              <!-- Progress bar -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-medium">
                  <span class="text-muted-foreground">Avancement global</span>
                  <span :style="{ color: cs.courseAccentColor }">{{ cs.progressPct }}%</span>
                </div>
                <Progress :model-value="cs.progressPct" class="h-2" :style="{ '--color-primary': cs.courseAccentColor }" />
              </div>

              <!-- Evaluations list -->
              <div v-if="cs.evaluations.length" class="space-y-2 pt-2">
                <div class="text-[10px] uppercase font-bold text-muted-foreground tracking-wider">Évaluations et quiz</div>
                <div class="grid gap-2">
                  <div
                    v-for="ev in cs.evaluations"
                    :key="ev.id"
                    class="flex items-center justify-between p-2.5 rounded-lg bg-muted/30 border text-xs"
                  >
                    <div class="font-medium truncate pr-2">{{ ev.title }}</div>
                    <div class="shrink-0 flex items-center gap-1.5">
                      <span v-if="ev.attempted" class="font-bold" :class="ev.passed ? 'text-emerald-600' : 'text-rose-600'">
                        {{ ev.score }} / {{ ev.maxScore }}
                      </span>
                      <span v-else class="text-muted-foreground italic text-[11px]">Non tenté</span>
                      <Badge v-if="ev.attempted" :variant="ev.passed ? 'default' : 'destructive'" class="text-[9px] uppercase font-semibold h-4 px-1.5">
                        {{ ev.passed ? 'Validé' : 'Échoué' }}
                      </Badge>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else class="text-xs text-muted-foreground italic text-center py-4">
                Aucune évaluation définie dans ce cours.
              </p>
            </CardContent>
          </Card>
          <div v-if="!student.courseStats.length" class="col-span-2 text-center py-12 text-muted-foreground italic border border-dashed rounded-xl bg-muted/10">
            Aucune activité enregistrée sur les cours pour le moment.
          </div>
        </div>
      </div>
    </div>
    <div v-else class="py-16 text-center text-muted-foreground">
      <p class="font-semibold text-lg">Étudiant introuvable</p>
      <p class="text-sm">L'étudiant demandé n'existe pas ou vous n'avez pas l'autorisation de le consulter.</p>
      <RouterLink to="/backoffice/students">
        <Button class="mt-4" variant="outline">Retour à la liste</Button>
      </RouterLink>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import BackofficeLayout from '@/components/BackofficeLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { api } from '@/api/client'
import { showToast } from '@/composables/useToast'

interface Student {
  id: number;
  name: string;
  email: string;
  studentGroup: string;
  studentYear: string;
  studentInstitution: string;
  points: number;
  badges: Array<{ code: string; icon: string; label: string; description: string }>;
  courseStats: Array<{
    courseId: number;
    courseTitle: string;
    courseAccentColor: string;
    totalPages: number;
    completedPages: number;
    progressPct: number;
    evaluations: Array<{
      id: number;
      title: string;
      pointsReward: number;
      attempted: boolean;
      score: number | null;
      maxScore: number | null;
      passed: boolean;
    }>;
    passedEvaluationsCount: number;
    totalEvaluationsCount: number;
    totalEvaluationScore: number;
    totalEvaluationMaxScore: number;
  }>;
}

const route = useRoute()
const student = ref<Student | null>(null)
const loading = ref(true)

async function loadStudentData() {
  loading.value = true
  try {
    const list = await api.get<Student[]>('/api/teacher/students')
    const match = list.find((s) => String(s.id) === String(route.params.id))
    student.value = match || null
  } catch (e) {
    console.error(e)
    showToast('Erreur lors du chargement des données de l\'étudiant', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadStudentData()
})
</script>
