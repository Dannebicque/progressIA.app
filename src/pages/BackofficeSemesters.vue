<template>
  <BackofficeLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Gestion des Semestres</h1>
        <p class="text-sm text-muted-foreground">Configurez les semestres d'études pour filtrer les accès aux cours.</p>
      </div>
    </div>

    <!-- Institution Selection for Super Admin -->
    <Card v-if="auth.isSuperAdmin()" class="mb-6">
      <CardContent class="pt-5 flex items-center gap-4">
        <Label for="instSelect" class="font-semibold text-sm">Établissement géré :</Label>
        <Select id="instSelect" v-model="selectedInstitutionId" class="max-w-xs">
          <SelectTrigger><SelectValue placeholder="Choisir un établissement" /></SelectTrigger>
          <SelectContent>
            <SelectItem v-for="inst in institutions" :key="inst.id" :value="String(inst.id)">
              {{ inst.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </CardContent>
    </Card>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- COLUMN 1: Form -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">{{
            editingId ? "Modifier le semestre" : "Ajouter un semestre"
          }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="space-y-1.5">
            <Label for="semName">Nom du semestre</Label>
            <Input id="semName" v-model="form.name" placeholder="Ex: S1, Semestre 3" />
          </div>
          <div class="flex gap-2 pt-2">
            <Button class="flex-1" @click="saveSemester" :disabled="saving">
              {{ editingId ? "Mettre à jour" : "Ajouter" }}
            </Button>
            <Button v-if="editingId" variant="outline" @click="resetForm" :disabled="saving">
              Annuler
            </Button>
          </div>
        </CardContent>
      </Card>

      <!-- COLUMN 2 & 3: List -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle class="text-base">Semestres configurés ({{ filteredSemesters.length }})</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="loading" class="py-8 text-center text-sm text-muted-foreground">
            Chargement des semestres...
          </div>
          <template v-else>
            <div
              v-for="sem in filteredSemesters"
              :key="sem.id"
              class="rounded-lg border p-3 hover:border-primary/40 transition-colors flex items-center justify-between gap-4"
            >
              <div class="space-y-1">
                <p class="font-medium text-sm">{{ sem.name }}</p>
                <p class="text-xs text-muted-foreground" v-if="auth.isSuperAdmin() && sem.institution">
                  Établissement : {{ sem.institution.name }}
                </p>
              </div>
              <div class="flex items-center gap-2">
                <Button size="xs" variant="outline" @click="editSemester(sem)">Modifier</Button>
                <Button size="xs" variant="ghost" class="text-destructive hover:bg-destructive/10" @click="removeSemester(sem.id)">
                  <IconTrash class="size-4" />
                </Button>
              </div>
            </div>
            <p v-if="!filteredSemesters.length" class="py-8 text-center text-sm text-muted-foreground">
              Aucun semestre trouvé pour cet établissement.
            </p>
          </template>
        </CardContent>
      </Card>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed, watch } from "vue"
import { IconTrash } from "@tabler/icons-vue"
import BackofficeLayout from "@/components/BackofficeLayout.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
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

const auth = useAuthStore()
const semesters = ref<Semester[]>([])
const institutions = ref<Institution[]>([])
const loading = ref(false)
const saving = ref(false)
const editingId = ref<number | null>(null)
const selectedInstitutionId = ref<string>("")

const form = reactive({
  name: ""
})

// Determine managed institution ID
const activeInstitutionId = computed(() => {
  if (auth.isSuperAdmin()) {
    return selectedInstitutionId.value
  }
  return String(auth.user?.institution?.id || "")
})

const filteredSemesters = computed(() => {
  if (!activeInstitutionId.value) return []
  return semesters.value.filter(s => s.institution && String(s.institution.id) === activeInstitutionId.value)
})

async function loadSemesters() {
  loading.value = true
  try {
    const res = await api.get<Semester[]>("/api/semesters")
    const data = (res as any)["hydra:member"] || res
    semesters.value = data
  } catch (e) {
    console.error(e)
    showToast("Erreur lors du chargement des semestres", "error")
  } finally {
    loading.value = false
  }
}

async function loadInstitutions() {
  try {
    const res = await api.get<Institution[]>("/api/institutions")
    const data = (res as any)["hydra:member"] || res
    institutions.value = data
    if (data.length && !selectedInstitutionId.value) {
      selectedInstitutionId.value = String(data[0].id)
    }
  } catch (e) {
    console.error(e)
  }
}

onMounted(async () => {
  await loadSemesters()
  if (auth.isSuperAdmin()) {
    await loadInstitutions()
  }
})

function resetForm() {
  editingId.value = null
  form.name = ""
}

function editSemester(sem: Semester) {
  editingId.value = sem.id
  form.name = sem.name
}

async function saveSemester() {
  if (!form.name.trim()) {
    showToast("Le nom du semestre est requis", "error")
    return
  }

  const instId = activeInstitutionId.value
  if (!instId) {
    showToast("Veuillez sélectionner un établissement", "error")
    return
  }

  saving.value = true
  try {
    const payload = {
      name: form.name.trim(),
      institution: `/api/institutions/${instId}`
    }

    if (editingId.value) {
      await api.patch(`/api/semesters/${editingId.value}`, payload)
      showToast("Semestre mis à jour avec succès")
    } else {
      await api.post("/api/semesters", payload)
      showToast("Semestre ajouté avec succès")
    }
    resetForm()
    await loadSemesters()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de l'enregistrement", "error")
  } finally {
    saving.value = false
  }
}

async function removeSemester(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer ce semestre ?",
      description: "Toutes les attributions d'étudiants à ce semestre seront réinitialisées.",
      confirmText: "Supprimer",
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/semesters/${id}`)
    showToast("Semestre supprimé")
    if (editingId.value === id) resetForm()
    await loadSemesters()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la suppression", "error")
  }
}
</script>
