<template>
  <BackofficeLayout>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Gestion des Établissements</h1>
        <p class="text-sm text-muted-foreground">Configurez les établissements autorisés, surveillez les frais fixes et facturations étudiantes.</p>
      </div>
      <Button @click="openCreateModal">
        <IconPlus class="mr-2 size-4" /> Nouvel Établissement
      </Button>
    </div>

    <!-- Metrics summary -->
    <div class="grid gap-6 md:grid-cols-3 mb-6">
      <Card>
        <CardHeader class="pb-2">
          <CardDescription class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Établissements Actifs
          </CardDescription>
        </CardHeader>
        <CardContent>
          <span class="text-3xl font-extrabold tracking-tight">{{ institutions.length }}</span>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2">
          <CardDescription class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Total Étudiants Inscrits
          </CardDescription>
        </CardHeader>
        <CardContent>
          <span class="text-3xl font-extrabold tracking-tight text-indigo-600 dark:text-indigo-400">{{ totalStudentsCount }}</span>
        </CardContent>
      </Card>
      <Card>
        <CardHeader class="pb-2">
          <CardDescription class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
            Facturation Totale Estimée
          </CardDescription>
        </CardHeader>
        <CardContent>
          <span class="text-3xl font-extrabold tracking-tight text-primary">{{ totalGlobalCost.toFixed(2) }} €</span>
        </CardContent>
      </Card>
    </div>

    <!-- Datatable Card -->
    <Card>
      <CardHeader class="pb-3 flex flex-wrap items-center justify-between gap-4">
        <CardTitle class="text-base">Liste des établissements</CardTitle>
        <Input v-model="search" placeholder="Rechercher un établissement..." class="max-w-[250px]" />
      </CardHeader>
      <CardContent class="p-0">
        <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground">
          Chargement des établissements...
        </div>
        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse">
            <thead>
              <tr class="border-b bg-muted/40 font-semibold text-muted-foreground select-none uppercase tracking-wider text-[10px]">
                <th class="p-3">Nom</th>
                <th class="p-3 text-right">Abonnement Fixe</th>
                <th class="p-3 text-right">Coût / Étudiant</th>
                <th class="p-3 text-center">Étudiants</th>
                <th class="p-3 text-right">Total Facturation</th>
                <th class="p-3 text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="inst in filteredInstitutions"
                :key="inst.id"
                class="border-b last:border-0 hover:bg-muted/20 transition-colors"
              >
                <td class="p-3 font-semibold text-foreground">{{ inst.name }}</td>
                <td class="p-3 text-right">{{ Number(inst.subscriptionFee).toFixed(2) }} €</td>
                <td class="p-3 text-right">{{ Number(inst.costPerStudent).toFixed(2) }} €</td>
                <td class="p-3 text-center">
                  <Badge variant="secondary">{{ getStudentCount(inst.id) }}</Badge>
                </td>
                <td class="p-3 text-right font-bold text-primary">
                  {{ calculateBilling(inst).toFixed(2) }} €
                </td>
                <td class="p-3 text-center">
                  <div class="flex items-center justify-center gap-2">
                    <Button size="xs" variant="outline" @click="router.push(`/backoffice/institutions/${inst.id}`)">
                      Gérer
                    </Button>
                    <Button size="xs" variant="ghost" class="text-destructive hover:bg-destructive/10" @click="removeInstitution(inst.id)">
                      <IconTrash class="size-4" />
                    </Button>
                  </div>
                </td>
              </tr>
              <tr v-if="!filteredInstitutions.length">
                <td colspan="6" class="p-8 text-center text-muted-foreground italic">
                  Aucun établissement trouvé.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>

    <!-- Create Dialog -->
    <Dialog v-model:open="createModalOpen">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Ajouter un établissement</DialogTitle>
          <DialogDescription>
            Renseignez les détails initiaux de l'établissement à créer.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2">
          <div class="space-y-1.5">
            <Label for="instName">Nom de l'établissement</Label>
            <Input id="instName" v-model="form.name" placeholder="Ex: IUT de Bordeaux" />
          </div>
          <div class="space-y-1.5">
            <Label for="subFee">Frais d'abonnement fixe (€)</Label>
            <Input id="subFee" type="number" step="0.01" min="0" v-model="form.subscriptionFee" />
          </div>
          <div class="space-y-1.5">
            <Label for="studentCost">Coût annuel par étudiant (€)</Label>
            <Input id="studentCost" type="number" step="0.01" min="0" v-model="form.costPerStudent" />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="createModalOpen = false" :disabled="saving">Annuler</Button>
          <Button @click="createInstitution" :disabled="saving">Créer et Configurer</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue"
import { useRouter } from "vue-router"
import { IconTrash, IconPlus } from "@tabler/icons-vue"
import BackofficeLayout from "@/components/BackofficeLayout.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
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
import { api } from "@/api/client"

interface Institution {
  id: number
  name: string
  subscriptionFee: string
  costPerStudent: string
}

interface User {
  id: number
  roles: string[]
  institution?: { id: number }
}

const router = useRouter()
const institutions = ref<Institution[]>([])
const students = ref<User[]>([])
const loading = ref(false)
const saving = ref(false)
const createModalOpen = ref(false)
const search = ref("")

const form = reactive({
  name: "",
  subscriptionFee: "0.00",
  costPerStudent: "0.00"
})

const filteredInstitutions = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return institutions.value
  return institutions.value.filter(inst => inst.name.toLowerCase().includes(q))
})

const totalStudentsCount = computed(() => students.value.length)

const totalGlobalCost = computed(() => {
  return institutions.value.reduce((acc, inst) => {
    return acc + calculateBilling(inst)
  }, 0)
})

function getStudentCount(instId: number): number {
  return students.value.filter(s => s.institution && s.institution.id === instId).length
}

function calculateBilling(inst: Institution): number {
  const count = getStudentCount(inst.id)
  const fixed = Number(inst.subscriptionFee || 0)
  const perStudent = Number(inst.costPerStudent || 0)
  return fixed + (count * perStudent)
}

async function loadData() {
  loading.value = true
  try {
    const [instRes, uRes] = await Promise.all([
      api.get<Institution[]>("/api/institutions"),
      api.get<User[]>("/api/users")
    ])

    institutions.value = (instRes as any)["hydra:member"] || instRes
    
    // Count students
    const allUsers = (uRes as any)["hydra:member"] || uRes
    students.value = allUsers.filter((u: User) => 
      u.institution && 
      !u.roles.includes("ROLE_TEACHER") && 
      !u.roles.includes("ROLE_SCHOOL_ADMIN") && 
      !u.roles.includes("ROLE_SUPER_ADMIN")
    )
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la récupération des établissements", "error")
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

function openCreateModal() {
  form.name = ""
  form.subscriptionFee = "0.00"
  form.costPerStudent = "0.00"
  createModalOpen.value = true
}

async function createInstitution() {
  if (!form.name.trim()) {
    showToast("Le nom de l'établissement est requis", "error")
    return
  }

  saving.value = true
  try {
    const payload = {
      name: form.name.trim(),
      subscriptionFee: String(Number(form.subscriptionFee || 0).toFixed(2)),
      costPerStudent: String(Number(form.costPerStudent || 0).toFixed(2)),
      emailDomains: [],
      invitationCode: null
    }

    const created = await api.post<Institution>("/api/institutions", payload)
    showToast("Établissement créé avec succès")
    createModalOpen.value = false
    // Redirect to detail configuration view immediately
    router.push(`/backoffice/institutions/${created.id}`)
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la création", "error")
  } finally {
    saving.value = false
  }
}

async function removeInstitution(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer cet établissement ?",
      description: "Toutes les formations, semestres et données associés seront définitivement supprimés.",
      confirmText: "Supprimer",
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/institutions/${id}`)
    showToast("Établissement supprimé")
    await loadData()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la suppression", "error")
  }
}
</script>
