<template>
  <BackofficeLayout>
    <div class="mb-6 flex items-center justify-between flex-wrap gap-4 border-b pb-4">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">Configuration & Vitrine</h1>
        <p class="text-sm text-muted-foreground">
          Gérez l'accroche publique de la plateforme, la grille tarifaire et les demandes de démo reçues.
        </p>
      </div>
      
      <!-- Tab Controls -->
      <div class="flex items-center gap-1 bg-muted p-1 rounded-lg border text-xs">
        <Button
          size="sm"
          :variant="activeTab === 'vitrine' ? 'secondary' : 'ghost'"
          class="h-8 text-xs font-semibold px-4"
          @click="activeTab = 'vitrine'"
        >
          Vitrine & Tarifs
        </Button>
        <Button
          size="sm"
          :variant="activeTab === 'messages' ? 'secondary' : 'ghost'"
          class="h-8 text-xs font-semibold px-4 relative"
          @click="activeTab = 'messages'"
        >
          Demandes de Démo
          <Badge v-if="pendingMessagesCount > 0" variant="destructive" class="ml-1.5 px-1 py-0 size-4 flex items-center justify-center text-[9px] font-bold rounded-full">
            {{ pendingMessagesCount }}
          </Badge>
        </Button>
      </div>
    </div>

    <!-- TAB 1: VITRINE CONFIG -->
    <div v-if="activeTab === 'vitrine'">
      <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground">
        Chargement de la configuration vitrine...
      </div>
      <div v-else class="space-y-6">
        <!-- HERO TEXTS CARD -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Section Héro d'Accueil</CardTitle>
            <CardDescription>Modifiez les textes d'accroche principaux visibles par les visiteurs publics.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label for="heroTitle">Titre principal</Label>
              <Input id="heroTitle" v-model="form.heroTitle" placeholder="Ex: Créez des cours engageants et gamifiés" />
            </div>
            <div class="space-y-1.5">
              <Label for="heroSubtitle">Sous-titre / Description</Label>
              <textarea
                id="heroSubtitle"
                v-model="form.heroSubtitle"
                rows="3"
                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                placeholder="Ex: Rédigez en Markdown, suivez la progression et récompensez..."
              ></textarea>
            </div>
          </CardContent>
        </Card>

        <!-- PLANS & PRICING CONFIG CARD -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-semibold tracking-tight">Formules d'Abonnement ({{ form.plans.length }})</h2>
            <Button size="sm" @click="addPlan">
              <IconPlus class="mr-1.5 size-4" /> Ajouter une formule
            </Button>
          </div>

          <div class="grid gap-6 md:grid-cols-3">
            <Card v-for="(plan, planIdx) in form.plans" :key="planIdx" class="relative flex flex-col justify-between">
              <!-- Delete button -->
              <Button
                variant="ghost"
                size="icon"
                class="absolute top-2 right-2 size-7 text-destructive hover:bg-destructive/10"
                @click="removePlan(planIdx)"
              >
                <IconTrash class="size-4" />
              </Button>

              <CardContent class="pt-6 space-y-4 flex-1">
                <div class="space-y-1.5">
                  <Label class="text-xs">Nom de l'offre</Label>
                  <Input v-model="plan.name" placeholder="Ex: Pack Standard" class="h-8 text-xs font-bold" />
                </div>

                <div class="space-y-1.5">
                  <Label class="text-xs">Description</Label>
                  <textarea
                    v-model="plan.description"
                    rows="2"
                    class="flex min-h-[50px] w-full rounded-md border border-input bg-background px-2.5 py-1.5 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    placeholder="Courte description de l'offre..."
                  ></textarea>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div class="space-y-1">
                    <Label class="text-[10px]">Frais fixe (€/an)</Label>
                    <Input type="number" step="1" v-model="plan.priceFixed" class="h-8 text-xs text-center" />
                  </div>
                  <div class="space-y-1">
                    <Label class="text-[10px]">Par étudiant (€/an)</Label>
                    <Input type="number" step="0.01" v-model="plan.pricePerStudent" class="h-8 text-xs text-center" />
                  </div>
                </div>

                <!-- Features checklist editor -->
                <div class="space-y-2 border-t pt-3">
                  <div class="flex items-center justify-between">
                    <Label class="text-[10px] uppercase font-bold text-muted-foreground">Avantages inclus</Label>
                    <Button size="xs" variant="ghost" class="h-5 px-1 text-[10px] text-primary" @click="addFeatureToPlan(planIdx)">
                      + Ajouter
                    </Button>
                  </div>

                  <div class="space-y-1 max-h-[150px] overflow-y-auto pr-1">
                    <div
                      v-for="(feat, featIdx) in plan.features"
                      :key="featIdx"
                      class="flex items-center gap-1.5"
                    >
                      <Input v-model="plan.features[featIdx]" class="h-7 text-[11px] py-0 px-2" placeholder="Avantage..." />
                      <Button
                        variant="ghost"
                        size="icon"
                        class="size-6 text-destructive shrink-0 hover:bg-destructive/10"
                        @click="removeFeatureFromPlan(planIdx, featIdx)"
                      >
                        <IconTrash class="size-3" />
                      </Button>
                    </div>
                    <p v-if="!plan.features.length" class="text-center py-2 text-[10px] text-muted-foreground italic">
                      Aucun avantage configuré.
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- SAVE BUTTON -->
        <div class="flex justify-end gap-2 border-t pt-4">
          <Button size="lg" @click="saveConfig" :disabled="saving">
            {{ saving ? "Sauvegarde..." : "Publier les modifications" }}
          </Button>
        </div>
      </div>
    </div>

    <!-- TAB 2: CONTACT REQUESTS -->
    <div v-else-if="activeTab === 'messages'" class="space-y-4">
      <div v-if="loadingRequests" class="py-12 text-center text-sm text-muted-foreground">
        Chargement des demandes de démonstration...
      </div>
      <Card v-else>
        <CardHeader class="pb-3">
          <CardTitle class="text-base">Demandes de démonstration et contact</CardTitle>
          <CardDescription>Visualisez et suivez les messages de contact envoyés depuis la vitrine.</CardDescription>
        </CardHeader>
        <CardContent class="p-0 border-t">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
              <thead>
                <tr class="border-b bg-muted/40 font-semibold text-muted-foreground uppercase text-[9px] tracking-wider">
                  <th class="p-3">Date</th>
                  <th class="p-3">Contact</th>
                  <th class="p-3">Établissement</th>
                  <th class="p-3">Message</th>
                  <th class="p-3">Statut</th>
                  <th class="p-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="req in contactRequests" :key="req.id" class="hover:bg-muted/10 transition-colors">
                  <td class="p-3 text-muted-foreground whitespace-nowrap">{{ formatDate(req.createdAt) }}</td>
                  <td class="p-3">
                    <p class="font-semibold">{{ req.name }}</p>
                    <a :href="'mailto:' + req.email" class="text-primary hover:underline text-[10px]">{{ req.email }}</a>
                  </td>
                  <td class="p-3 font-medium">{{ req.institutionName }}</td>
                  <td class="p-3 max-w-[250px] truncate" :title="req.message">{{ req.message }}</td>
                  <td class="p-3 whitespace-nowrap">
                    <Badge :variant="req.status === 'processed' ? 'secondary' : 'default'">
                      {{ req.status === 'processed' ? 'Traité' : 'En attente' }}
                    </Badge>
                  </td>
                  <td class="p-3 text-right space-x-1 whitespace-nowrap">
                    <Button
                      size="xs"
                      :variant="req.status === 'processed' ? 'outline' : 'default'"
                      class="h-7 text-[10px]"
                      @click="toggleRequestStatus(req)"
                    >
                      {{ req.status === 'processed' ? 'Rouvrir' : 'Marquer traité' }}
                    </Button>
                    <Button
                      size="icon"
                      variant="ghost"
                      class="size-7 text-destructive hover:bg-destructive/10"
                      @click="deleteRequest(req.id)"
                    >
                      <IconTrash class="size-3.5" />
                    </Button>
                  </td>
                </tr>
                <tr v-if="!contactRequests.length">
                  <td colspan="6" class="p-12 text-center text-muted-foreground italic">
                    Aucune demande de démonstration reçue pour le moment.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from "vue"
import { IconPlus, IconTrash } from "@tabler/icons-vue"
import BackofficeLayout from "@/components/BackofficeLayout.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { showToast } from "@/composables/useToast"
import { confirmDialog } from "@/composables/useConfirm"
import { api } from "@/api/client"

interface Plan {
  name: string
  description: string
  priceFixed: string
  pricePerStudent: string
  features: string[]
}

interface ContactRequest {
  id: number
  name: string
  email: string
  institutionName: string
  message: string
  status: string
  createdAt: string
}

const activeTab = ref("vitrine")
const loading = ref(false)
const saving = ref(false)

const form = reactive({
  heroTitle: "",
  heroSubtitle: "",
  plans: [] as Plan[]
})

// Contact Requests state
const contactRequests = ref<ContactRequest[]>([])
const loadingRequests = ref(false)
const pendingMessagesCount = computed(() => {
  return contactRequests.value.filter(c => c.status === 'pending').length
})

async function loadConfig() {
  loading.value = true
  try {
    const config = await api.get<any>("/api/landing_configs/1")
    if (config) {
      form.heroTitle = config.heroTitle
      form.heroSubtitle = config.heroSubtitle
      form.plans = config.plansJson || []
    }
  } catch (e) {
    console.error(e)
    showToast("Erreur lors du chargement de la configuration", "error")
  } finally {
    loading.value = false
  }
}

async function loadRequests() {
  loadingRequests.value = true
  try {
    const res = await api.get<ContactRequest[]>("/api/contact_requests")
    contactRequests.value = (res as any)["hydra:member"] || res
  } catch (e) {
    console.error(e)
    showToast("Erreur lors du chargement des demandes de contact", "error")
  } finally {
    loadingRequests.value = false
  }
}

onMounted(() => {
  loadConfig()
  loadRequests()
})

function addPlan() {
  form.plans.push({
    name: "Nouvelle offre",
    description: "Description de l'offre.",
    priceFixed: "300.00",
    pricePerStudent: "5.00",
    features: ["Avantage 1"]
  })
}

async function removePlan(idx: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer cette offre ?",
      description: "Elle ne sera plus affichée sur la page publique.",
      confirmText: "Supprimer"
    }))
  ) {
    return
  }
  form.plans.splice(idx, 1)
}

function addFeatureToPlan(planIdx: number) {
  const plan = form.plans[planIdx]
  if (plan) {
    plan.features.push("")
  }
}

function removeFeatureFromPlan(planIdx: number, featIdx: number) {
  const plan = form.plans[planIdx]
  if (plan) {
    plan.features.splice(featIdx, 1)
  }
}

async function saveConfig() {
  if (!form.heroTitle.trim() || !form.heroSubtitle.trim()) {
    showToast("Les textes d'accroche héro sont obligatoires.", "error")
    return
  }

  for (const plan of form.plans) {
    if (!plan.name.trim()) {
      showToast("Le nom de chaque formule est obligatoire.", "error")
      return
    }
    plan.features = plan.features.map(f => f.trim()).filter(Boolean)
  }

  saving.value = true
  try {
    const payload = {
      heroTitle: form.heroTitle.trim(),
      heroSubtitle: form.heroSubtitle.trim(),
      plansJson: form.plans
    }

    await api.patch("/api/landing_configs/1", payload)
    showToast("Vitrine et tarifs mis à jour publiquement !")
    await loadConfig()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de l'enregistrement", "error")
  } finally {
    saving.value = false
  }
}

async function toggleRequestStatus(req: ContactRequest) {
  const newStatus = req.status === "processed" ? "pending" : "processed"
  try {
    await api.patch(`/api/contact_requests/${req.id}`, { status: newStatus })
    showToast("Statut de la demande mis à jour avec succès.")
    await loadRequests()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la mise à jour du statut", "error")
  }
}

async function deleteRequest(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer cette demande ?",
      description: "Cette action est définitive.",
      confirmText: "Supprimer"
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/contact_requests/${id}`)
    showToast("Demande supprimée avec succès.")
    await loadRequests()
  } catch (e) {
    console.error(e)
    showToast("Erreur lors de la suppression de la demande", "error")
  }
}

function formatDate(dateStr: string): string {
  if (!dateStr) return ""
  const date = new Date(dateStr)
  return date.toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
    hour: "2-digit",
    minute: "2-digit"
  })
}
</script>
