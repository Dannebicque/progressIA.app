<template>
  <BackofficeLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Gestion des Formations</h1>
        <p class="text-sm text-muted-foreground">Configurez les diplômes et filières d'études de l'établissement.</p>
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
            editingId ? "Modifier la formation" : "Ajouter une formation"
          }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="space-y-1.5">
            <Label for="formName">Nom du diplôme / formation</Label>
            <Input id="formName" v-model="form.name" placeholder="Ex: MMI, Informatique" />
          </div>
          <div class="flex gap-2 pt-2">
            <Button class="flex-1" @click="saveFormation" :disabled="saving">
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
          <CardTitle class="text-base">Formations configurées ({{ filteredFormations.length }})</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div v-if="loading" class="py-8 text-center text-sm text-muted-foreground">
            Chargement des formations...
          </div>
          <template v-else>
            <div
              v-for="f in filteredFormations"
              :key="f.id"
              class="rounded-lg border p-3 hover:border-primary/40 transition-colors flex items-center justify-between gap-4"
            >
              <div class="space-y-1">
                <p class="font-medium text-sm">{{ f.name }}</p>
                <p class="text-xs text-muted-foreground" v-if="auth.isSuperAdmin() && f.institution">
                  Établissement : {{ f.institution.name }}
                </p>
              </div>
              <div class="flex items-center gap-2">
                <Button size="xs" variant="outline" @click="editFormation(f)">Modifier</Button>
                <Button size="xs" variant="ghost" class="text-destructive hover:bg-destructive/10" @click="removeFormation(f.id)">
                  <IconTrash class="size-4" />
                </Button>
              </div>
            </div>
            <p v-if="!filteredFormations.length" class="py-8 text-center text-sm text-muted-foreground">
              Aucune formation trouvée pour cet établissement.
            </p>
          </template>
        </CardContent>
      </Card>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue"
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

interface Formation {
  id: number
  name: string
  institution: Institution
}

const auth = useAuthStore()
const formations = ref<Formation[]>([])
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

const filteredFormations = computed(() => {
  if (!activeInstitutionId.value) return []
  return formations.value.filter(f => f.institution && String(f.institution.id) === activeInstitutionId.value)
})

async function loadFormations() {
  loading.value = true
  try {
    const res = await api.get<Formation[]>("/api/formations")
    const data = (res as any)["hydra:member"] || res
    formations.value = data
  } catch (e) {
    console.error(e)
    showToast("Erreur lors du chargement des formations", "error")
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
  await loadFormations()
  if (auth.isSuperAdmin()) {
    await loadInstitutions()
  }
})

function resetForm() {
  editingId.value = null
  form.name = ""
}

function editFormation(f: Formation) {
  editingId.value = f.id
  form.name = f.name
}

async function saveFormation() {
  if (!form.name.trim()) {
    showToast("Le nom de la formation est requis", "error")
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
      await api.patch(`/api/formations/${editingId.value}`, payload)
      showToast("Formation mise à jour avec succès")
    } else {
      await api.post("/api/formations", payload)
      showToast("Formation ajoutée avec succès")
    }
    resetForm()
    await loadFormations()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de l'enregistrement", "error")
  } finally {
    saving.value = false
  }
}

async function removeFormation(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer cette formation ?",
      description: "Toutes les attributions d'étudiants à cette formation seront réinitialisées.",
      confirmText: "Supprimer",
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/formations/${id}`)
    showToast("Formation supprimée")
    if (editingId.value === id) resetForm()
    await loadFormations()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la suppression", "error")
  }
}
</script>
