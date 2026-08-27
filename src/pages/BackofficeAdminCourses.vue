<template>
  <BackofficeLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Supervision des Cours</h1>
        <p class="text-sm text-muted-foreground">
          {{
            auth.isSuperAdmin()
              ? "Supervisez et configurez l'ensemble des cours de la plateforme."
              : "Supervisez et configurez les cours de votre établissement."
          }}
        </p>
      </div>
      <Button @click="openCreateModal">
        <IconPlus class="mr-2 size-4" /> Nouveau Cours
      </Button>
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <CardContent class="pt-5 grid gap-4 md:grid-cols-4">
        <div class="space-y-1">
          <Label for="searchTitle" class="text-xs">Rechercher</Label>
          <Input id="searchTitle" v-model="searchQuery" placeholder="Titre du cours..." />
        </div>
        <div class="space-y-1" v-if="auth.isSuperAdmin()">
          <Label for="filterInst" class="text-xs">Établissement</Label>
          <Select id="filterInst" v-model="filterInstitutionId">
            <SelectTrigger><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous</SelectItem>
              <SelectItem v-for="inst in institutions" :key="inst.id" :value="String(inst.id)">
                {{ inst.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1">
          <Label for="filterForm" class="text-xs">Formation</Label>
          <Select id="filterForm" v-model="filterFormationId">
            <SelectTrigger><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Toutes</SelectItem>
              <SelectItem v-for="f in formations" :key="f.id" :value="String(f.id)">
                {{ f.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <div class="space-y-1">
          <Label for="filterTeacher" class="text-xs">Enseignant / Auteur</Label>
          <Select id="filterTeacher" v-model="filterTeacherId">
            <SelectTrigger><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Tous</SelectItem>
              <SelectItem v-for="t in teachers" :key="t.id" :value="String(t.id)">
                {{ t.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
      </CardContent>
    </Card>

    <!-- Courses List -->
    <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground">
      Chargement des cours...
    </div>
    <div v-else class="grid gap-6 md:grid-cols-2">
      <Card v-for="course in filteredCourses" :key="course.id" class="flex flex-col">
        <CardHeader class="pb-3">
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span
                  class="inline-block size-3 rounded-full shrink-0"
                  :style="{ backgroundColor: course.accentColor || '#7c3aed' }"
                ></span>
                <CardTitle class="text-base">{{ course.title }}</CardTitle>
              </div>
              <p class="text-xs text-muted-foreground">{{ course.theme }} · {{ course.level }}</p>
            </div>
            <Badge :variant="course.visible ? 'default' : 'secondary'">
              {{ course.visible ? 'Visible' : 'Masqué' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="flex-1 space-y-3 pb-4 text-xs">
          <p class="text-muted-foreground line-clamp-2" v-if="course.scenario">{{ course.scenario }}</p>
          
          <div class="space-y-1.5 pt-2">
            <div>
              <strong class="text-foreground">Auteur(s) / Enseignant(s) :</strong>
              <div class="flex flex-wrap gap-1 mt-1" v-if="course.teachers?.length">
                <Badge variant="outline" v-for="t in course.teachers" :key="t.id">{{ t.name }}</Badge>
              </div>
              <p class="text-muted-foreground italic mt-0.5" v-else>Aucun auteur assigné</p>
            </div>
            
            <div class="pt-1">
              <strong class="text-foreground">Semestres :</strong>
              <div class="flex flex-wrap gap-1 mt-1" v-if="course.semesters?.length">
                <Badge variant="secondary" v-for="s in course.semesters" :key="s.id">{{ s.name }}</Badge>
              </div>
              <p class="text-muted-foreground italic mt-0.5" v-else>Non restreint par semestre</p>
            </div>

            <div class="pt-1">
              <strong class="text-foreground">Formations :</strong>
              <div class="flex flex-wrap gap-1 mt-1" v-if="course.formations?.length">
                <Badge variant="secondary" v-for="f in course.formations" :key="f.id">{{ f.name }}</Badge>
              </div>
              <p class="text-muted-foreground italic mt-0.5" v-else>Non restreint par formation</p>
            </div>

            <div class="pt-1" v-if="auth.isSuperAdmin()">
              <strong class="text-foreground">Établissements :</strong>
              <div class="flex flex-wrap gap-1 mt-1" v-if="course.institutions?.length">
                <Badge variant="secondary" class="bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400" v-for="i in course.institutions" :key="i.id">
                  {{ i.name }}
                </Badge>
              </div>
              <p class="text-muted-foreground italic mt-0.5" v-else>Aucun établissement lié</p>
            </div>
          </div>
        </CardContent>
        <CardFooter class="pt-2 border-t border-border/50 flex justify-end gap-2 bg-muted/20 rounded-b-lg">
          <Button variant="outline" size="sm" @click="editCourse(course)">Modifier</Button>
          <Button variant="destructive" size="sm" @click="removeCourse(course.id)">
            Supprimer
          </Button>
        </CardFooter>
      </Card>
      <div v-if="!filteredCourses.length" class="col-span-full py-12 text-center text-sm text-muted-foreground">
        Aucun cours ne correspond aux critères.
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <Dialog v-model:open="modalOpen">
      <DialogContent class="max-w-2xl max-h-[85vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>{{ editingCourseId ? 'Modifier le cours' : 'Créer un cours' }}</DialogTitle>
          <DialogDescription>
            Configurez les métadonnées et affectations du cours.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2">
          <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
              <Label for="cTitle">Titre du cours</Label>
              <Input id="cTitle" v-model="form.title" placeholder="Ex: Introduction à Vue 3" />
            </div>
            <div class="space-y-1.5">
              <Label for="cTheme">Thème</Label>
              <Input id="cTheme" v-model="form.theme" placeholder="Ex: Développement Web" />
            </div>
            <div class="space-y-1.5">
              <Label for="cLevel">Niveau</Label>
              <Input id="cLevel" v-model="form.level" placeholder="Ex: BUT2, Intermédiaire" />
            </div>
            <div class="space-y-1.5">
              <Label for="cCategory">Catégorie</Label>
              <Select id="cCategory" v-model="form.category">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="front">Frontend</SelectItem>
                  <SelectItem value="back">Backend</SelectItem>
                  <SelectItem value="fullstack">Fullstack</SelectItem>
                  <SelectItem value="other">Autre</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label for="cColor">Couleur d'accentuation</Label>
              <Input id="cColor" type="color" v-model="form.accentColor" />
            </div>
            <div class="flex items-center space-x-2 pt-6">
              <input type="checkbox" id="cVisible" v-model="form.visible" class="rounded border-gray-300 text-primary focus:ring-primary size-4" />
              <Label for="cVisible">Visible par les étudiants</Label>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="cContext">Contexte pédagogique</Label>
            <Input id="cContext" v-model="form.context" placeholder="Ex: BUT2 MMI - TD1" />
          </div>

          <div class="space-y-1.5">
            <Label for="cScenario">Scénario / Description</Label>
            <textarea id="cScenario" v-model="form.scenario" class="w-full min-h-[80px] rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring" placeholder="Scénario d'apprentissage..."></textarea>
          </div>

          <!-- ASSIGNMENTS checklists -->
          <div class="border-t pt-4 grid gap-4 md:grid-cols-2">
            <!-- Teachers Assignment -->
            <div class="space-y-2">
              <Label class="font-semibold text-sm">Assigner des Enseignants / Auteurs</Label>
              <div class="max-h-[150px] overflow-y-auto border rounded-md p-2 space-y-1.5">
                <div v-for="t in teachers" :key="t.id" class="flex items-center space-x-2">
                  <input type="checkbox" :id="'t-'+t.id" :value="t.id" v-model="form.teacherIds" class="rounded border-gray-300 text-primary size-3.5" />
                  <label :for="'t-'+t.id" class="text-xs text-muted-foreground cursor-pointer">{{ t.name }}</label>
                </div>
              </div>
            </div>

            <!-- Semesters Assignment -->
            <div class="space-y-2">
              <Label class="font-semibold text-sm">Assigner des Semestres</Label>
              <div class="max-h-[150px] overflow-y-auto border rounded-md p-2 space-y-1.5">
                <div v-for="s in semesters" :key="s.id" class="flex items-center space-x-2">
                  <input type="checkbox" :id="'s-'+s.id" :value="s.id" v-model="form.semesterIds" class="rounded border-gray-300 text-primary size-3.5" />
                  <label :for="'s-'+s.id" class="text-xs text-muted-foreground cursor-pointer">
                    {{ s.name }} <span class="text-[10px]" v-if="auth.isSuperAdmin() && s.institution">({{ s.institution.name }})</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Formations Assignment -->
            <div class="space-y-2">
              <Label class="font-semibold text-sm">Assigner des Formations</Label>
              <div class="max-h-[150px] overflow-y-auto border rounded-md p-2 space-y-1.5">
                <div v-for="f in formations" :key="f.id" class="flex items-center space-x-2">
                  <input type="checkbox" :id="'f-'+f.id" :value="f.id" v-model="form.formationIds" class="rounded border-gray-300 text-primary size-3.5" />
                  <label :for="'f-'+f.id" class="text-xs text-muted-foreground cursor-pointer">
                    {{ f.name }} <span class="text-[10px]" v-if="auth.isSuperAdmin() && f.institution">({{ f.institution.name }})</span>
                  </label>
                </div>
              </div>
            </div>

            <!-- Institutions Assignment (Super Admin only) -->
            <div class="space-y-2" v-if="auth.isSuperAdmin()">
              <Label class="font-semibold text-sm">Assigner des Établissements</Label>
              <div class="max-h-[150px] overflow-y-auto border rounded-md p-2 space-y-1.5">
                <div v-for="i in institutions" :key="i.id" class="flex items-center space-x-2">
                  <input type="checkbox" :id="'i-'+i.id" :value="i.id" v-model="form.institutionIds" class="rounded border-gray-300 text-primary size-3.5" />
                  <label :for="'i-'+i.id" class="text-xs text-muted-foreground cursor-pointer">{{ i.name }}</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter class="border-t pt-4">
          <Button variant="outline" @click="modalOpen = false" :disabled="saving">Annuler</Button>
          <Button @click="saveCourse" :disabled="saving">Enregistrer</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue"
import { IconPlus } from "@tabler/icons-vue"
import BackofficeLayout from "@/components/BackofficeLayout.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from "@/components/ui/dialog"
import { showToast } from "@/composables/useToast"
import { confirmDialog } from "@/composables/useConfirm"
import { useAuthStore } from "@/stores/auth"
import { useCoursesStore } from "@/stores/courses"
import { api } from "@/api/client"

interface User {
  id: number
  name: string
  roles: string[]
}

interface Institution {
  id: number
  name: string
}

interface Semester {
  id: number
  name: string
  institution?: Institution
}

interface Formation {
  id: number
  name: string
  institution?: Institution
}

const auth = useAuthStore()
const courseStore = useCoursesStore()

const loading = ref(false)
const saving = ref(false)
const modalOpen = ref(false)
const editingCourseId = ref<number | null>(null)

// Filters state
const searchQuery = ref("")
const filterInstitutionId = ref("all")
const filterFormationId = ref("all")
const filterTeacherId = ref("all")

// Data sets
const rawCourses = ref<any[]>([])
const teachers = ref<User[]>([])
const institutions = ref<Institution[]>([])
const semesters = ref<Semester[]>([])
const formations = ref<Formation[]>([])

const form = reactive({
  title: "",
  theme: "",
  level: "",
  category: "other",
  accentColor: "#7c3aed",
  visible: true,
  context: "",
  scenario: "",
  teacherIds: [] as number[],
  semesterIds: [] as number[],
  formationIds: [] as number[],
  institutionIds: [] as number[]
})

// Filtered courses
const filteredCourses = computed(() => {
  const query = searchQuery.value.trim().toLowerCase()
  return rawCourses.value.filter(c => {
    // Search query
    if (query && !c.title.toLowerCase().includes(query)) return false
    
    // Institution filter
    if (filterInstitutionId.value !== "all") {
      if (!c.institutions?.some((i: any) => String(i.id) === filterInstitutionId.value)) return false
    }
    
    // Formation filter
    if (filterFormationId.value !== "all") {
      if (!c.formations?.some((f: any) => String(f.id) === filterFormationId.value)) return false
    }

    // Teacher filter
    if (filterTeacherId.value !== "all") {
      if (!c.teachers?.some((t: any) => String(t.id) === filterTeacherId.value)) return false
    }

    return true
  })
})

async function loadData() {
  loading.value = true
  try {
    const [cRes, uRes, iRes, sRes, fRes] = await Promise.all([
      api.get<any[]>("/api/courses"),
      api.get<User[]>("/api/users"),
      api.get<Institution[]>("/api/institutions"),
      api.get<Semester[]>("/api/semesters"),
      api.get<Formation[]>("/api/formations")
    ])

    rawCourses.value = (cRes as any)["hydra:member"] || cRes
    
    const allUsers = (uRes as any)["hydra:member"] || uRes
    teachers.value = allUsers.filter((u: User) => u.roles.includes("ROLE_TEACHER"))
    
    institutions.value = (iRes as any)["hydra:member"] || iRes
    semesters.value = (sRes as any)["hydra:member"] || sRes
    formations.value = (fRes as any)["hydra:member"] || fRes
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la récupération des données", "error")
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

function openCreateModal() {
  editingCourseId.value = null
  form.title = ""
  form.theme = ""
  form.level = ""
  form.category = "other"
  form.accentColor = "#7c3aed"
  form.visible = true
  form.context = ""
  form.scenario = ""
  
  // By default, add current teacher to course if creating
  form.teacherIds = auth.isTeacher() && auth.user?.id ? [auth.user.id] : []
  form.semesterIds = []
  form.formationIds = []
  
  // By default, add active institution if not super admin
  const defaultInstId = auth.user?.institution?.id
  form.institutionIds = defaultInstId ? [defaultInstId] : []

  modalOpen.value = true
}

function editCourse(c: any) {
  editingCourseId.value = c.id
  form.title = c.title || ""
  form.theme = c.theme || ""
  form.level = c.level || ""
  form.category = c.category || "other"
  form.accentColor = c.accentColor || "#7c3aed"
  form.visible = c.visible !== false
  form.context = c.context || ""
  form.scenario = c.scenario || ""
  
  form.teacherIds = c.teachers?.map((t: any) => t.id) || []
  form.semesterIds = c.semesters?.map((s: any) => s.id) || []
  form.formationIds = c.formations?.map((f: any) => f.id) || []
  form.institutionIds = c.institutions?.map((i: any) => i.id) || []
  
  modalOpen.value = true
}

async function saveCourse() {
  if (!form.title.trim()) {
    showToast("Le titre du cours est requis", "error")
    return
  }

  // Enforce institution link if not super admin
  const activeInstId = auth.user?.institution?.id
  if (!auth.isSuperAdmin() && activeInstId) {
    if (!form.institutionIds.includes(activeInstId)) {
      form.institutionIds.push(activeInstId)
    }
  }

  if (!form.institutionIds.length) {
    showToast("Le cours doit être lié à au moins un établissement", "error")
    return
  }

  saving.value = true
  try {
    const payload = {
      title: form.title.trim(),
      theme: form.theme.trim() || null,
      level: form.level.trim() || null,
      category: form.category,
      accentColor: form.accentColor,
      visible: form.visible,
      context: form.context.trim() || null,
      scenario: form.scenario.trim() || null,
      teachers: form.teacherIds.map(id => `/api/users/${id}`),
      semesters: form.semesterIds.map(id => `/api/semesters/${id}`),
      formations: form.formationIds.map(id => `/api/formations/${id}`),
      institutions: form.institutionIds.map(id => `/api/institutions/${id}`)
    }

    if (editingCourseId.value) {
      await api.patch(`/api/courses/${editingCourseId.value}`, payload)
      showToast("Cours mis à jour")
    } else {
      await api.post("/api/courses", payload)
      showToast("Cours créé")
    }
    
    modalOpen.value = false
    await loadData()
    // Refresh sidebar store
    await courseStore.fetchCourses()
  } catch (e: any) {
    console.error(e)
    showToast(e.body?.detail || "Erreur lors de l'enregistrement", "error")
  } finally {
    saving.value = false
  }
}

async function removeCourse(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer ce cours ?",
      description: "Cela supprimera également toutes les séances, chapitres, pages et statistiques liés à ce cours. Cette action est irréversible.",
      confirmText: "Supprimer",
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/courses/${id}`)
    showToast("Cours supprimé")
    await loadData()
    // Refresh sidebar store
    await courseStore.fetchCourses()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la suppression", "error")
  }
}
</script>
