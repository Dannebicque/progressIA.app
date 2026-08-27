<template>
  <BackofficeLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Comptes Utilisateurs</h1>
        <p class="text-sm text-muted-foreground">Gérez les comptes, rôles et accès de la plateforme.</p>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- COLUMN 1: Create / Edit Form -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">{{
            editingUserId ? "Modifier l'utilisateur" : "Ajouter un utilisateur"
          }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="space-y-1.5">
            <Label for="userName">Nom</Label>
            <Input id="userName" v-model="form.name" />
          </div>
          <div class="space-y-1.5">
            <Label for="userEmail">Email</Label>
            <Input id="userEmail" v-model="form.email" type="email" />
          </div>
          <div class="space-y-1.5">
            <Label for="userPass">Mot de passe {{ editingUserId ? '(laisser vide pour inchangé)' : '' }}</Label>
            <Input id="userPass" v-model="form.password" type="password" placeholder="6 caractères minimum" />
          </div>
          <div class="space-y-1.5">
            <Label for="userRole">Rôle</Label>
            <Select id="userRole" v-model="form.role">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ROLE_STUDENT">Étudiant</SelectItem>
                <SelectItem value="ROLE_TEACHER">Enseignant</SelectItem>
                <SelectItem value="ROLE_SCHOOL_ADMIN">Responsable d'établissement</SelectItem>
                <SelectItem v-if="auth.isSuperAdmin()" value="ROLE_SUPER_ADMIN">Super Admin</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Institution selection (Super Admin only, for School Admin it is locked to their institution) -->
          <div v-if="auth.isSuperAdmin() && form.role !== 'ROLE_SUPER_ADMIN'" class="space-y-1.5">
            <Label for="userInst">Établissement</Label>
            <Select id="userInst" v-model="form.institutionId">
              <SelectTrigger><SelectValue placeholder="Aucun" /></SelectTrigger>
              <SelectContent>
                <SelectItem value="none">Aucun</SelectItem>
                <SelectItem v-for="inst in institutions" :key="inst.id" :value="String(inst.id)">
                  {{ inst.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Semester and Formation selection (only for ROLE_STUDENT) -->
          <template v-if="form.role === 'ROLE_STUDENT'">
            <div class="space-y-1.5">
              <Label for="userSem">Semestre</Label>
              <Select id="userSem" v-model="form.semesterId">
                <SelectTrigger><SelectValue placeholder="Aucun" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Aucun</SelectItem>
                  <SelectItem v-for="sem in availableSemesters" :key="sem.id" :value="String(sem.id)">
                    {{ sem.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label for="userForm">Formation</Label>
              <Select id="userForm" v-model="form.formationId">
                <SelectTrigger><SelectValue placeholder="Aucun" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Aucun</SelectItem>
                  <SelectItem v-for="f in availableFormations" :key="f.id" :value="String(f.id)">
                    {{ f.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div class="space-y-1.5">
              <Label for="userGroup">Groupe TD/TP</Label>
              <Input id="userGroup" v-model="form.studentGroup" placeholder="Ex: TD1 - TP2" />
            </div>
          </template>

          <div class="flex gap-2 pt-2">
            <Button class="flex-1" @click="saveUser" :disabled="saving">
              {{ editingUserId ? "Mettre à jour" : "Ajouter" }}
            </Button>
            <Button v-if="editingUserId" variant="outline" @click="resetForm" :disabled="saving">
              Annuler
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- COLUMN 2 & 3: Users List -->
      <Card class="lg:col-span-2">
        <CardHeader class="space-y-3">
          <CardTitle class="text-base">Utilisateurs</CardTitle>
          <div class="grid gap-2 md:grid-cols-2">
            <Input v-model="search" placeholder="Rechercher (nom / email)" />
            <Select v-model="roleFilter">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous les rôles</SelectItem>
                <SelectItem value="ROLE_STUDENT">Étudiant</SelectItem>
                <SelectItem value="ROLE_TEACHER">Enseignant</SelectItem>
                <SelectItem value="ROLE_SCHOOL_ADMIN">Responsable d'établissement</SelectItem>
                <SelectItem value="ROLE_SUPER_ADMIN">Super Admin</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="loading" class="py-8 text-center text-sm text-muted-foreground">
            Chargement des comptes utilisateurs...
          </div>
          <template v-else>
            <div
              v-for="user in filteredUsers"
              :key="user.id"
              class="rounded-lg border p-3 hover:border-primary/40 transition-colors"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <p class="font-medium text-sm">{{ user.name }}</p>
                  <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                  <div class="flex flex-wrap gap-2 pt-1 text-[10px] text-muted-foreground">
                    <span v-if="user.institution" class="font-semibold text-indigo-600 dark:text-indigo-400">
                      🏫 {{ user.institution.name }}
                    </span>
                    <span v-if="user.studentSemester">
                      · Semestre : {{ user.studentSemester.name }}
                    </span>
                    <span v-if="user.studentFormation">
                      · Formation : {{ user.studentFormation.name }}
                    </span>
                    <span v-if="user.studentGroup">
                      · Groupe : {{ user.studentGroup }}
                    </span>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <Badge variant="secondary" class="text-[10px]">
                    {{ getRoleLabel(user.roles) }}
                  </Badge>
                  <Button size="xs" variant="outline" @click="editUser(user)">Modifier</Button>
                  <Button
                    size="xs"
                    variant="ghost"
                    class="text-destructive hover:bg-destructive/10"
                    @click="removeUser(user.id)"
                    :disabled="user.id === auth.user?.id"
                  >
                    <IconTrash class="size-4" />
                  </Button>
                </div>
              </div>
            </div>
            <p
              v-if="!filteredUsers.length"
              class="py-8 text-center text-sm text-muted-foreground"
            >
              Aucun utilisateur trouvé.
            </p>
          </template>
        </CardContent>
      </Card>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from "vue"
import { IconTrash } from "@tabler/icons-vue"
import BackofficeLayout from "@/components/BackofficeLayout.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { showToast } from "@/composables/useToast"
import { confirmDialog } from "@/composables/useConfirm"
import { useAuthStore } from "@/stores/auth"
import { api } from "@/api/client"

interface Institution {
  id: number
  name: string
}

interface Semester {
  id: number
  name: string
  institution: Institution
}

interface Formation {
  id: number
  name: string
  institution: Institution
}

interface UserAccount {
  id: number
  name: string
  email: string
  roles: string[]
  studentGroup?: string
  institution?: Institution
  studentSemester?: Semester
  studentFormation?: Formation
}

const auth = useAuthStore()
const users = ref<UserAccount[]>([])
const institutions = ref<Institution[]>([])
const semesters = ref<Semester[]>([])
const formations = ref<Formation[]>([])

const loading = ref(false)
const saving = ref(false)
const editingUserId = ref<number | null>(null)
const search = ref("")
const roleFilter = ref<string>("all")

const form = reactive({
  name: "",
  email: "",
  password: "",
  role: "ROLE_STUDENT",
  institutionId: "none",
  semesterId: "none",
  formationId: "none",
  studentGroup: ""
})

// Compute active institution ID to filter semesters/formations
const activeInstitutionId = computed(() => {
  if (auth.isSuperAdmin()) {
    return form.institutionId === "none" ? "" : form.institutionId
  }
  return String(auth.user?.institution?.id || "")
})

const availableSemesters = computed(() => {
  if (!activeInstitutionId.value) return []
  return semesters.value.filter(s => s.institution && String(s.institution.id) === activeInstitutionId.value)
})

const availableFormations = computed(() => {
  if (!activeInstitutionId.value) return []
  return formations.value.filter(f => f.institution && String(f.institution.id) === activeInstitutionId.value)
})

const filteredUsers = computed(() => {
  const q = search.value.trim().toLowerCase()
  return users.value.filter((user) => {
    let roleMatch = true
    if (roleFilter.value !== "all") {
      roleMatch = user.roles.includes(roleFilter.value)
    }
    const queryMatch =
      !q ||
      user.name.toLowerCase().includes(q) ||
      user.email.toLowerCase().includes(q)
    return roleMatch && queryMatch
  })
})

async function loadData() {
  loading.value = true
  try {
    const [uRes, iRes, sRes, fRes] = await Promise.all([
      api.get<UserAccount[]>("/api/users"),
      api.get<Institution[]>("/api/institutions"),
      api.get<Semester[]>("/api/semesters"),
      api.get<Formation[]>("/api/formations")
    ])

    users.value = (uRes as any)["hydra:member"] || uRes
    institutions.value = (iRes as any)["hydra:member"] || iRes
    semesters.value = (sRes as any)["hydra:member"] || sRes
    formations.value = (fRes as any)["hydra:member"] || fRes
  } catch (e) {
    console.error(e)
    showToast("Erreur lors du chargement des données", "error")
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

function resetForm() {
  editingUserId.value = null
  form.name = ""
  form.email = ""
  form.password = ""
  form.role = "ROLE_STUDENT"
  form.institutionId = "none"
  form.semesterId = "none"
  form.formationId = "none"
  form.studentGroup = ""
}

function editUser(user: UserAccount) {
  editingUserId.value = user.id
  form.name = user.name
  form.email = user.email
  form.password = ""
  
  // Resolve primary role
  if (user.roles.includes("ROLE_SUPER_ADMIN")) form.role = "ROLE_SUPER_ADMIN"
  else if (user.roles.includes("ROLE_SCHOOL_ADMIN")) form.role = "ROLE_SCHOOL_ADMIN"
  else if (user.roles.includes("ROLE_TEACHER")) form.role = "ROLE_TEACHER"
  else form.role = "ROLE_STUDENT"

  form.institutionId = user.institution ? String(user.institution.id) : "none"
  form.semesterId = user.studentSemester ? String(user.studentSemester.id) : "none"
  form.formationId = user.studentFormation ? String(user.studentFormation.id) : "none"
  form.studentGroup = user.studentGroup || ""
}

function getRoleLabel(roles: string[]): string {
  if (roles.includes("ROLE_SUPER_ADMIN")) return "Super Admin"
  if (roles.includes("ROLE_SCHOOL_ADMIN")) return "Responsable"
  if (roles.includes("ROLE_TEACHER")) return "Enseignant"
  return "Étudiant"
}

async function saveUser() {
  if (!form.name.trim() || !form.email.trim()) {
    showToast("Nom et email requis", "error")
    return
  }

  if (!editingUserId.value && (!form.password || form.password.length < 6)) {
    showToast("Le mot de passe doit faire au moins 6 caractères", "error")
    return
  }

  saving.value = true
  try {
    const roles = [form.role]
    if (form.role === "ROLE_SUPER_ADMIN") {
      roles.push("ROLE_SCHOOL_ADMIN", "ROLE_TEACHER", "ROLE_USER")
    } else if (form.role === "ROLE_SCHOOL_ADMIN") {
      roles.push("ROLE_TEACHER", "ROLE_USER")
    } else if (form.role === "ROLE_TEACHER") {
      roles.push("ROLE_USER")
    } else {
      roles.push("ROLE_USER")
    }

    const payload: any = {
      name: form.name.trim(),
      email: form.email.trim(),
      roles
    }

    if (form.password) {
      payload.password = form.password
    }

    // Set institution
    let instId = ""
    if (auth.isSuperAdmin()) {
      instId = form.institutionId !== "none" ? form.institutionId : ""
    } else {
      instId = String(auth.user?.institution?.id || "")
    }

    if (instId && form.role !== "ROLE_SUPER_ADMIN") {
      payload.institution = `/api/institutions/${instId}`
    } else {
      payload.institution = null
    }

    // If Student, link semester, formation and group
    if (form.role === "ROLE_STUDENT") {
      payload.studentSemester = form.semesterId !== "none" ? `/api/semesters/${form.semesterId}` : null
      payload.studentFormation = form.formationId !== "none" ? `/api/formations/${form.formationId}` : null
      payload.studentGroup = form.studentGroup.trim() || null
    } else {
      payload.studentSemester = null
      payload.studentFormation = null
      payload.studentGroup = null
    }

    if (editingUserId.value) {
      await api.patch(`/api/users/${editingUserId.value}`, payload)
      showToast("Utilisateur mis à jour avec succès")
    } else {
      await api.post("/api/users", payload)
      showToast("Utilisateur créé avec succès")
    }
    resetForm()
    await loadData()
  } catch (e: any) {
    console.error(e)
    const detail = e.body?.detail || "Erreur lors de l'enregistrement."
    showToast(detail, "error")
  } finally {
    saving.value = false
  }
}

async function removeUser(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer cet utilisateur ?",
      description: "Cette action est définitive.",
      confirmText: "Supprimer",
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/users/${id}`)
    showToast("Utilisateur supprimé")
    if (editingUserId.value === id) resetForm()
    await loadData()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la suppression", "error")
  }
}
</script>
