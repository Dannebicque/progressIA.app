<template>
  <AppLayout>
    <div v-if="loading" class="py-12 text-center text-muted-foreground text-sm">
      Chargement du suivi de cours...
    </div>
    <div v-else-if="course" class="space-y-6">
      <!-- Header with back button -->
      <Card class="overflow-hidden pt-0">
        <div class="h-2 w-full" :style="{ background: accent }"></div>
        <CardContent class="pt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl text-white"
              :style="{ background: `linear-gradient(135deg, ${accent}, ${accent}99)` }">
              <IconSchool class="size-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <RouterLink to="/backoffice/courses">
                  <Button variant="outline" size="xs">← Retour aux cours</Button>
                </RouterLink>
                <h1 class="text-xl font-bold tracking-tight">{{ course.title }}</h1>
              </div>
              <p class="text-xs text-muted-foreground mt-1">{{ course.theme }} · Suivi des Étudiants</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- KPI Summary Cards -->
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Étudiants actifs</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-bold text-2xl text-primary" :style="{ color: accent }">{{ activeStudentsCount }}</p>
            <p class="text-[10px] text-muted-foreground mt-1">Étudiants ayant démarré le cours</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Moyenne de progression</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-bold text-2xl text-primary" :style="{ color: accent }">{{ averageProgressPct }}%</p>
            <p class="text-[10px] text-muted-foreground mt-1">Moyenne d'avancement de la classe</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Quiz validés</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="font-bold text-2xl text-primary" :style="{ color: accent }">{{ totalPassedQuizzes }}</p>
            <p class="text-[10px] text-muted-foreground mt-1">Total des évaluations réussies</p>
          </CardContent>
        </Card>
      </div>

      <!-- Student Tracking Table -->
      <Card>
        <CardHeader class="pb-3 flex flex-row items-center justify-between gap-4 flex-wrap">
          <CardTitle class="text-base font-semibold">Suivi d'avancement de la classe ({{ courseStudentStats.length }})</CardTitle>
          <div class="flex items-center gap-2">
            <Select v-model="groupFilter">
              <SelectTrigger class="w-40 h-8 text-xs"><SelectValue placeholder="Tous groupes" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous groupes</SelectItem>
                <SelectItem v-for="g in groupOptions" :key="g" :value="g">{{ g }}</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent class="p-0">
          <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
              <thead class="bg-muted text-[10px] uppercase font-bold text-muted-foreground">
                <tr>
                  <th class="p-3 pl-6">Étudiant</th>
                  <th class="p-3">Groupe / Promo</th>
                  <th class="p-3">Progression</th>
                  <th class="p-3">Evaluations validées</th>
                  <th class="p-3 text-right pr-6">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="s in filteredStudentStats" :key="s.id" class="hover:bg-muted/40 transition-colors">
                  <td class="p-3 pl-6">
                    <div class="font-semibold text-sm text-foreground">{{ s.name }}</div>
                    <div class="text-muted-foreground text-[10px]">{{ s.email }}</div>
                  </td>
                  <td class="p-3">
                    <div class="font-medium">{{ s.studentGroup || 'Sans groupe' }}</div>
                    <div class="text-muted-foreground text-[10px] mt-0.5">{{ s.studentYear }}</div>
                  </td>
                  <td class="p-3">
                    <div class="flex items-center gap-2 max-w-[160px]">
                      <Progress :model-value="s.progressPct" class="h-1.5 flex-1" :style="{ '--color-primary': accent }" />
                      <span class="font-bold text-[10px] shrink-0" :style="{ color: accent }">{{ s.progressPct }}%</span>
                    </div>
                    <div class="text-muted-foreground text-[10px] mt-0.5">{{ s.completedPages }} / {{ s.totalPages }} pages</div>
                  </td>
                  <td class="p-3">
                    <div class="font-semibold">{{ s.passedEvaluationsCount }} / {{ s.totalEvaluationsCount }} validé(s)</div>
                    <div class="text-muted-foreground text-[10px] mt-0.5">Note totale : {{ s.totalEvaluationScore }} / {{ s.totalEvaluationMaxScore }}</div>
                  </td>
                  <td class="p-3 text-right pr-6">
                    <RouterLink :to="`/backoffice/students/${s.id}`">
                      <Button size="xs" variant="outline">Fiche étudiant</Button>
                    </RouterLink>
                  </td>
                </tr>
                <tr v-if="!filteredStudentStats.length">
                  <td colspan="5" class="p-10 text-center text-muted-foreground italic">Aucun étudiant actif correspondant.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
    <div v-else class="py-16 text-center text-muted-foreground">
      <p class="font-semibold text-lg">Cours introuvable</p>
      <p class="text-sm">Le cours demandé n'existe pas ou vous n'avez pas l'autorisation de le consulter.</p>
      <RouterLink to="/backoffice/courses">
        <Button class="mt-4" variant="outline">Retour à la liste des cours</Button>
      </RouterLink>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { IconSchool } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { useCoursesStore } from '@/stores/courses'
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
const store = useCoursesStore()

const course = computed(() => store.getCourse(route.params.id as string))
const accent = computed(() => course.value?.accentColor || '#7c3aed')

const students = ref<Student[]>([])
const loading = ref(true)
const groupFilter = ref('all')

async function loadData() {
  loading.value = true
  try {
    if (!store.loaded) await store.fetchCourses()
    students.value = await api.get<Student[]>('/api/teacher/students')
  } catch (e) {
    console.error(e)
    showToast('Erreur lors du chargement des données de suivi', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

const courseStudentStats = computed(() => {
  if (!course.value) return [];
  const stats: any[] = [];
  for (const s of students.value) {
    const cs = s.courseStats?.find((c: any) => String(c.courseId) === String(course.value.id));
    if (cs) {
      stats.push({
        id: s.id,
        name: s.name,
        email: s.email,
        studentGroup: s.studentGroup,
        studentYear: s.studentYear,
        progressPct: cs.progressPct,
        completedPages: cs.completedPages,
        totalPages: cs.totalPages,
        passedEvaluationsCount: cs.passedEvaluationsCount,
        totalEvaluationsCount: cs.totalEvaluationsCount,
        totalEvaluationScore: cs.totalEvaluationScore,
        totalEvaluationMaxScore: cs.totalEvaluationMaxScore,
        rawStudent: s
      });
    }
  }
  return stats.sort((a, b) => a.name.localeCompare(b.name));
});

const filteredStudentStats = computed(() => {
  if (groupFilter.value === 'all') return courseStudentStats.value;
  return courseStudentStats.value.filter(s => s.studentGroup && s.studentGroup.includes(groupFilter.value));
});

const groupOptions = computed(() => {
  return [
    ...new Set(
      courseStudentStats.value.flatMap((s) =>
        s.studentGroup ? s.studentGroup.split(/[,/\-\s]+/).map((g: string) => g.trim()).filter(Boolean) : []
      )
    )
  ].sort();
});

const activeStudentsCount = computed(() => {
  return courseStudentStats.value.filter((s) => s.progressPct > 0 || s.passedEvaluationsCount > 0).length;
});

const averageProgressPct = computed(() => {
  if (!courseStudentStats.value.length) return 0;
  const total = courseStudentStats.value.reduce((sum, s) => sum + s.progressPct, 0);
  return Math.round(total / courseStudentStats.value.length);
});

const totalPassedQuizzes = computed(() => {
  return courseStudentStats.value.reduce((sum, s) => sum + s.passedEvaluationsCount, 0);
});
</script>
