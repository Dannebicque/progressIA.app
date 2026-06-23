<template>
  <BackofficeLayout>
    <div v-if="loading" class="py-12 text-center text-muted-foreground text-sm">
      Chargement de la fiche étudiant...
    </div>
    <div v-else-if="student" class="space-y-6">
      <!-- Header with back button -->
      <div class="flex items-center justify-between gap-4 flex-wrap">
        <div class="flex items-center gap-3">
          <RouterLink v-if="courseId" :to="`/backoffice/courses/${courseId}/tracking`">
            <Button variant="outline" size="sm">
              ← Retour au suivi du cours
            </Button>
          </RouterLink>
          <RouterLink v-else to="/backoffice/students">
            <Button variant="outline" size="sm">
              ← Retour aux étudiants
            </Button>
          </RouterLink>
          <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ student.name }}</h1>
            <p class="text-xs text-muted-foreground">{{ student.email }}</p>
            <p v-if="courseId && specificCourseStats" class="text-xs font-semibold mt-1" :style="{ color: specificCourseStats.courseAccentColor }">
              Fiche de suivi pour le cours : {{ specificCourseStats.courseTitle }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Badge variant="outline" class="text-xs py-1 px-3">{{ student.studentGroup || 'Sans groupe' }}</Badge>
          <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">🏆 {{ student.points }} points</span>
        </div>
      </div>

      <!-- COURSE SPECIFIC VIEW -->
      <div v-if="courseId && specificCourseStats" class="grid gap-6 md:grid-cols-3">
        <!-- Left 2 columns: Course tracking details -->
        <div class="md:col-span-2 space-y-6">
          <!-- Course Card / Banner -->
          <Card class="overflow-hidden border-t-4" :style="{ borderTopColor: specificCourseStats.courseAccentColor }">
            <CardHeader class="pb-3">
              <div class="flex items-center gap-2">
                <span class="inline-block size-3 rounded-full shrink-0" :style="{ backgroundColor: specificCourseStats.courseAccentColor }"></span>
                <CardTitle class="text-sm font-bold">Progression & Activité</CardTitle>
              </div>
            </CardHeader>
            <CardContent class="space-y-6">
              <!-- Big Progress Indicator -->
              <div class="flex flex-col sm:flex-row items-center gap-6 p-4 rounded-xl bg-muted/20 border">
                <div class="relative flex items-center justify-center size-20 shrink-0">
                  <svg class="size-full -rotate-90" viewBox="0 0 36 36">
                    <path
                      class="text-muted/30 stroke-current"
                      stroke-width="3"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                    <path
                      class="stroke-current transition-all duration-500 ease-out"
                      :style="{ color: specificCourseStats.courseAccentColor }"
                      stroke-width="3"
                      :stroke-dasharray="`${specificCourseStats.progressPct}, 100`"
                      stroke-linecap="round"
                      fill="none"
                      d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    />
                  </svg>
                  <div class="absolute text-lg font-bold" :style="{ color: specificCourseStats.courseAccentColor }">
                    {{ specificCourseStats.progressPct }}%
                  </div>
                </div>
                <div class="space-y-1.5 text-center sm:text-left flex-1">
                  <h3 class="font-bold text-sm">Avancement global dans le cours</h3>
                  <p class="text-xs text-muted-foreground">
                    L'étudiant a complété <strong>{{ specificCourseStats.completedPages }}</strong> pages sur les <strong>{{ specificCourseStats.totalPages }}</strong> pages de ce cours.
                  </p>
                  <Progress :model-value="specificCourseStats.progressPct" class="h-2 w-full mt-2" :style="{ '--color-primary': specificCourseStats.courseAccentColor }" />
                </div>
              </div>

              <!-- Detailed activity summary -->
              <div class="space-y-3">
                <h3 class="text-xs uppercase font-bold text-muted-foreground tracking-wider">Statistiques d'évaluation</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                  <div class="p-4 rounded-xl border bg-muted/10 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-muted-foreground">Quiz Validés</span>
                    <span class="text-2xl font-extrabold mt-1 text-emerald-600 dark:text-emerald-400">
                      {{ specificCourseStats.passedEvaluationsCount }} / {{ specificCourseStats.totalEvaluationsCount }}
                    </span>
                  </div>
                  <div class="p-4 rounded-xl border bg-muted/10 flex flex-col justify-between">
                    <span class="text-xs font-semibold text-muted-foreground">Score Total Obtenu</span>
                    <span class="text-2xl font-extrabold mt-1" :style="{ color: specificCourseStats.courseAccentColor }">
                      {{ specificCourseStats.totalEvaluationScore }} / {{ specificCourseStats.totalEvaluationMaxScore }}
                    </span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Quiz & Submissions List -->
          <Card>
            <CardHeader class="pb-3 border-b">
              <CardTitle class="text-sm font-bold">Évaluations et Quiz (Rendus)</CardTitle>
            </CardHeader>
            <CardContent class="pt-4 p-0">
              <div v-if="specificCourseStats.evaluations.length" class="divide-y">
                <div
                  v-for="ev in specificCourseStats.evaluations"
                  :key="ev.id"
                  class="p-4 hover:bg-muted/10 transition-colors space-y-3"
                >
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1">
                      <div class="font-semibold text-xs">{{ ev.title }}</div>
                      <div class="text-[10px] text-muted-foreground">
                        Récompense : <span class="font-medium text-indigo-600 dark:text-indigo-400">+{{ ev.pointsReward }} points</span>
                      </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                      <div class="text-right">
                        <div v-if="ev.attempted" class="font-bold text-xs" :class="ev.passed ? 'text-emerald-600' : 'text-rose-600'">
                          {{ ev.score }} / {{ ev.maxScore }}
                        </div>
                        <div v-else class="text-muted-foreground italic text-[11px]">Non tenté</div>
                      </div>
                      <Badge :variant="ev.attempted ? (ev.passed ? 'default' : 'destructive') : 'secondary'" class="text-[9px] uppercase font-semibold h-5 px-2">
                        {{ ev.attempted ? (ev.passed ? 'Validé' : 'Échoué') : 'Non tenté' }}
                      </Badge>
                      <Button
                        v-if="ev.attempted && ev.attemptId"
                        size="xs"
                        variant="outline"
                        class="gap-1 h-7 border-indigo-200 text-indigo-600 hover:bg-indigo-50/50 hover:text-indigo-700 font-semibold text-[10px]"
                        @click="openAIAnalysis(ev)"
                      >
                        <IconSparkles class="size-3" />
                        <span>{{ (ev.feedbackTeacher || ev.feedbackStudent) ? 'Voir analyse IA' : 'Analyser avec IA' }}</span>
                      </Button>
                    </div>
                  </div>

                  <!-- Detail of answers (free text response or file deposit) -->
                  <div v-if="ev.attempted && ev.answers && ev.answers.length" class="mt-2 pl-3 border-l-2 border-muted text-[11px] space-y-1.5 bg-muted/20 p-2 rounded-md">
                    <div class="font-semibold text-muted-foreground text-[9px] uppercase tracking-wider mb-1">Détails des réponses :</div>
                    <div v-for="ans in ev.answers" :key="ans.question" class="space-y-0.5">
                      <!-- If it is a text/free answer or has a file upload -->
                      <div v-if="ans.text || ans.file" class="text-xs">
                        <span class="text-muted-foreground font-semibold">Q: {{ getQuestionStatement(ev, ans.question) }}</span>
                        <p class="italic text-foreground mt-0.5 whitespace-pre-wrap">« {{ ans.text }} »</p>
                        <div v-if="ans.file" class="mt-1 flex items-center gap-1.5 font-medium text-indigo-600 dark:text-indigo-400">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                          <span>Fichier déposé : {{ ans.file }}</span>
                        </div>
                      </div>
                    </div>
                    <!-- AI feedback quick preview -->
                    <div v-if="ev.feedbackTeacher || ev.feedbackStudent" class="mt-2 border-t pt-2 space-y-1">
                      <div class="flex items-center gap-1 text-[9px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                        <IconSparkles class="size-3" />
                        <span>Analyse IA disponible</span>
                      </div>
                      <p v-if="ev.feedbackTeacher" class="text-[10px] text-muted-foreground line-clamp-1">
                        <strong>Prof :</strong> {{ ev.feedbackTeacher }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else class="text-xs text-muted-foreground italic text-center py-8">
                Aucune évaluation définie dans ce cours.
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Right 1 column: Student Info & Badges -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle class="text-sm font-bold">Profil Étudiant</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-3 text-xs">
                <div>
                  <span class="text-[10px] text-muted-foreground block uppercase font-semibold tracking-wider">Établissement</span>
                  <span class="font-semibold text-sm">{{ student.studentInstitution || 'Non spécifié' }}</span>
                </div>
                <div>
                  <span class="text-[10px] text-muted-foreground block uppercase font-semibold tracking-wider">Promotion / Année</span>
                  <span class="font-semibold text-sm">{{ student.studentYear || 'Non spécifiée' }}</span>
                </div>
                <div>
                  <span class="text-[10px] text-muted-foreground block uppercase font-semibold tracking-wider">Groupe</span>
                  <span class="font-semibold text-sm">{{ student.studentGroup || 'Sans groupe' }}</span>
                </div>
                <div>
                  <span class="text-[10px] text-muted-foreground block uppercase font-semibold tracking-wider">Points totaux</span>
                  <span class="font-bold text-sm text-indigo-600 dark:text-indigo-400">🏆 {{ student.points }} points</span>
                </div>
              </div>

              <RouterLink :to="`/backoffice/students/${student.id}`">
                <Button variant="outline" size="sm" class="w-full mt-4 text-xs">
                  Voir le profil global
                </Button>
              </RouterLink>
            </CardContent>
          </Card>

          <Card v-if="student.badges.length">
            <CardHeader>
              <CardTitle class="text-xs uppercase font-bold text-muted-foreground">Badges débloqués ({{ student.badges.length }})</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-wrap gap-2">
              <Badge v-for="b in student.badges" :key="b.code" variant="secondary" class="gap-1 py-1 px-2">
                <span>{{ b.icon }}</span>
                <span class="text-[9px] font-semibold">{{ b.label }}</span>
              </Badge>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- GLOBAL PROFILE VIEW -->
      <div v-else-if="!courseId" class="space-y-6">
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

      <!-- COURSE ID BUT NO STATS FOUND -->
      <div v-else class="py-16 text-center text-muted-foreground border border-dashed rounded-xl bg-muted/10">
        <p class="font-semibold text-lg">Aucune activité dans ce cours</p>
        <p class="text-sm">L'étudiant n'a pas encore commencé ce cours ou n'y est pas inscrit.</p>
        <RouterLink :to="`/backoffice/courses/${courseId}/tracking`">
          <Button class="mt-4" variant="outline">Retour au suivi du cours</Button>
        </RouterLink>
      </div>
    </div>
    <div v-else class="py-16 text-center text-muted-foreground">
      <p class="font-semibold text-lg">Étudiant introuvable</p>
      <p class="text-sm">L'étudiant demandé n'existe pas ou vous n'avez pas l'autorisation de le consulter.</p>
      <RouterLink to="/backoffice/students">
        <Button class="mt-4" variant="outline">Retour à la liste</Button>
      </RouterLink>
    </div>
    <!-- AI Feedback Modal -->
    <Dialog :open="isAIModalOpen" @update:open="(v: any) => (isAIModalOpen = v)">
      <DialogContent class="sm:max-w-4xl max-h-[90vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
            <IconBrain class="size-5" />
            <span>Analyse IA — {{ selectedAttempt?.title }}</span>
          </DialogTitle>
          <DialogDescription>
            Analyse automatique par intelligence artificielle sur les réponses de {{ student?.name }}.
          </DialogDescription>
        </DialogHeader>

        <!-- Loading State -->
        <div v-if="analyzing" class="py-12 flex flex-col items-center justify-center gap-4">
          <div class="relative size-16">
            <div class="absolute inset-0 rounded-full border-4 border-indigo-100 dark:border-indigo-950/40 animate-ping"></div>
            <div class="absolute inset-0 rounded-full border-4 border-t-indigo-600 dark:border-t-indigo-400 animate-spin"></div>
            <div class="absolute inset-0 flex items-center justify-center">
              <IconSparkles class="size-6 text-indigo-600 dark:text-indigo-400 animate-pulse" />
            </div>
          </div>
          <div class="text-center space-y-1">
            <p class="font-bold text-sm text-foreground">Génération de l'analyse en cours...</p>
            <p class="text-xs text-muted-foreground animate-pulse">L'IA de Groq évalue les réponses et rédige les retours.</p>
          </div>
        </div>

        <!-- Error State -->
        <div v-else-if="analysisError" class="py-8 text-center space-y-4">
          <p class="text-sm font-semibold text-rose-600">{{ analysisError }}</p>
          <Button variant="outline" size="sm" class="gap-1.5" @click="runAIAnalysis(selectedAttempt?.attemptId)">
            <IconSparkles class="size-3.5" /> Réessayer
          </Button>
        </div>

        <!-- Result State -->
        <div v-else-if="selectedAttempt" class="space-y-4 py-2">
          <!-- Score details -->
          <div class="flex items-center justify-between p-3 rounded-lg bg-muted/40 border text-xs">
            <div>
              <span class="text-muted-foreground">Score obtenu :</span>
              <strong class="text-sm ml-1" :class="selectedAttempt.passed ? 'text-emerald-600' : 'text-rose-600'">
                {{ selectedAttempt.score }} / {{ selectedAttempt.maxScore }}
              </strong>
            </div>
            <Badge :variant="selectedAttempt.passed ? 'default' : 'destructive'" class="text-[9px] uppercase font-bold">
              {{ selectedAttempt.passed ? 'Validé' : 'Échoué' }}
            </Badge>
          </div>

          <!-- Custom Tabs Navigation (Plain HTML buttons, no radix components) -->
          <div class="flex border-b border-muted">
            <button
              type="button"
              class="flex-1 py-3 text-center text-xs font-bold uppercase tracking-wider border-b-2 transition-all duration-200 cursor-pointer"
              :class="activeTab === 'teacher' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/5 dark:bg-indigo-950/10' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
              @click="activeTab = 'teacher'"
            >
              Notes pour l'Enseignant (Confidentiel)
            </button>
            <button
              type="button"
              class="flex-1 py-3 text-center text-xs font-bold uppercase tracking-wider border-b-2 transition-all duration-200 cursor-pointer"
              :class="activeTab === 'student' ? 'border-emerald-600 text-emerald-600 bg-emerald-50/5 dark:bg-emerald-950/10' : 'border-transparent text-muted-foreground hover:text-foreground hover:bg-muted/30'"
              @click="activeTab = 'student'"
            >
              Retour envoyé à l'Étudiant
            </button>
          </div>

          <!-- Teacher View Content -->
          <div v-if="activeTab === 'teacher'" class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2 w-full">
            <!-- Left 1/3: Student's answers -->
            <div class="md:col-span-1 space-y-2 w-full">
              <div class="font-bold text-muted-foreground text-[10px] uppercase tracking-wider">Réponses de l'étudiant</div>
              <div class="border rounded-lg p-3 bg-muted/10 text-xs max-h-[350px] overflow-y-auto space-y-3 w-full">
                <div v-for="ans in selectedAttempt.answers" :key="ans.question" class="space-y-1 border-b pb-2 last:border-b-0 last:pb-0">
                  <!-- If it is a text/free answer or has a file upload -->
                  <div v-if="ans.text || ans.file" class="space-y-1">
                    <span class="text-muted-foreground block font-semibold">Q: {{ getQuestionStatement(selectedAttempt, ans.question) }}</span>
                    <p class="italic text-foreground">« {{ ans.text }} »</p>
                    <div v-if="ans.file" class="mt-1 flex items-center gap-1.5 font-semibold text-indigo-600 dark:text-indigo-400">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                      <span>Fichier joint : {{ ans.file }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right 2/3: AI Feedback for Teacher -->
            <div class="md:col-span-2 space-y-2 w-full">
              <div class="font-bold text-indigo-600 dark:text-indigo-400 text-[10px] uppercase tracking-wider">Analyse critique pour le professeur</div>
              <div class="p-4 rounded-xl border border-indigo-100 dark:border-indigo-950/40 bg-indigo-50/10 text-xs leading-relaxed max-h-[350px] overflow-y-auto w-full">
                <p class="text-foreground whitespace-pre-line">{{ selectedAttempt.feedbackTeacher }}</p>
              </div>
            </div>
          </div>

          <!-- Student View Content -->
          <div v-else-if="activeTab === 'student'" class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2 w-full">
            <!-- Left 1/3: Student's answers -->
            <div class="md:col-span-1 space-y-2 w-full">
              <div class="font-bold text-muted-foreground text-[10px] uppercase tracking-wider">Réponses de l'étudiant</div>
              <div class="border rounded-lg p-3 bg-muted/10 text-xs max-h-[350px] overflow-y-auto space-y-3 w-full">
                <div v-for="ans in selectedAttempt.answers" :key="ans.question" class="space-y-1 border-b pb-2 last:border-b-0 last:pb-0">
                  <!-- If it is a text/free answer or has a file upload -->
                  <div v-if="ans.text || ans.file" class="space-y-1">
                    <span class="text-muted-foreground block font-semibold">Q: {{ getQuestionStatement(selectedAttempt, ans.question) }}</span>
                    <p class="italic text-foreground">« {{ ans.text }} »</p>
                    <div v-if="ans.file" class="mt-1 flex items-center gap-1.5 font-semibold text-indigo-600 dark:text-indigo-400">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg>
                      <span>Fichier joint : {{ ans.file }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right 2/3: AI Feedback for Student -->
            <div class="md:col-span-2 space-y-2 w-full">
              <div class="font-bold text-emerald-600 dark:text-emerald-400 text-[10px] uppercase tracking-wider">Retour pédagogique envoyé à l'élève</div>
              <div class="p-4 rounded-xl border border-emerald-100 dark:border-emerald-950/40 bg-emerald-50/10 text-xs leading-relaxed max-h-[350px] overflow-y-auto w-full">
                <p class="text-foreground whitespace-pre-line">{{ selectedAttempt.feedbackStudent }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 border-t pt-3 mt-4">
          <Button variant="outline" size="sm" @click="isAIModalOpen = false">Fermer</Button>
          <Button
            v-if="selectedAttempt && !analyzing"
            size="sm"
            class="gap-1 text-xs border-indigo-200 text-indigo-600 hover:bg-indigo-50/50"
            variant="outline"
            @click="runAIAnalysis(selectedAttempt.attemptId)"
          >
            <IconSparkles class="size-3.5" /> Réanalyser
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { IconSparkles, IconBrain } from '@tabler/icons-vue'
import BackofficeLayout from '@/components/BackofficeLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { api } from '@/api/client'
import { showToast } from '@/composables/useToast'

interface EvaluationAttemptStats {
  id: number;
  attemptId?: number | null;
  title: string;
  pointsReward: number;
  attempted: boolean;
  score: number | null;
  maxScore: number | null;
  passed: boolean;
  feedbackTeacher?: string | null;
  feedbackStudent?: string | null;
  answers?: Array<{
    question: number;
    choices?: number[];
    text?: string;
    file?: string;
  }>;
  questions?: Array<{
    id: number;
    statement: string;
    type: string;
    fileRequired: boolean;
  }>;
}

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
    evaluations: EvaluationAttemptStats[];
    passedEvaluationsCount: number;
    totalEvaluationsCount: number;
    totalEvaluationScore: number;
    totalEvaluationMaxScore: number;
  }>;
}

const route = useRoute()
const student = ref<Student | null>(null)
const loading = ref(true)

const courseId = computed(() => route.params.courseId as string | undefined)

const specificCourseStats = computed(() => {
  if (!student.value || !courseId.value) return null
  return student.value.courseStats.find((cs) => String(cs.courseId) === String(courseId.value)) || null
})

function getQuestionStatement(ev: EvaluationAttemptStats | null | undefined, questionId: number | string): string {
  const q = ev?.questions?.find((q) => String(q.id) === String(questionId))
  return q ? q.statement : 'Réponse libre'
}

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

// AI feedback modal state
const isAIModalOpen = ref(false)
const selectedAttempt = ref<EvaluationAttemptStats | null>(null)
const activeTab = ref<'teacher' | 'student'>('teacher')
const analyzing = ref(false)
const analysisError = ref<string | null>(null)

async function runAIAnalysis(attemptId: number | null | undefined) {
  if (!attemptId) return
  analyzing.value = true
  analysisError.value = null
  try {
    const res = await api.post<{ id: number; feedbackTeacher: string; feedbackStudent: string }>(
      `/api/attempts/${attemptId}/analyze-ai`
    )
    // Update local state immediately
    if (student.value) {
      for (const cs of student.value.courseStats) {
        const ev = cs.evaluations.find((e) => e.attemptId === attemptId)
        if (ev) {
          ev.feedbackTeacher = res.feedbackTeacher
          ev.feedbackStudent = res.feedbackStudent
          // Also update the active modal ref
          if (selectedAttempt.value && selectedAttempt.value.attemptId === attemptId) {
            selectedAttempt.value.feedbackTeacher = res.feedbackTeacher
            selectedAttempt.value.feedbackStudent = res.feedbackStudent
          }
          break
        }
      }
    }
    showToast("Analyse IA générée avec succès")
  } catch (e: unknown) {
    console.error(e)
    const errMsg = e instanceof Error ? e.message : String(e)
    analysisError.value = errMsg || "Erreur lors de la génération de l'analyse."
    showToast("Échec de l'analyse IA", 'error')
  } finally {
    analyzing.value = false
  }
}

function openAIAnalysis(ev: EvaluationAttemptStats) {
  selectedAttempt.value = ev
  isAIModalOpen.value = true
  analysisError.value = null
  activeTab.value = 'teacher'
  // If feedback hasn't been generated yet, start the generation
  if (!ev.feedbackTeacher && !ev.feedbackStudent && ev.attemptId) {
    runAIAnalysis(ev.attemptId)
  } else {
    analyzing.value = false
  }
}

onMounted(() => {
  loadStudentData()
})
</script>
