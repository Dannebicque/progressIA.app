<template>
  <BackofficeLayout>
    <!-- Switcher Bar for Super Admin -->
    <Card v-if="auth.isSuperAdmin() && institutions.length" class="mb-6">
      <CardContent class="pt-5 flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-4">
          <Label for="quickSwitch" class="font-semibold text-sm">Établissement géré :</Label>
          <Select id="quickSwitch" :model-value="String(activeId)" @update:model-value="onQuickSwitch">
            <SelectTrigger class="w-[250px]"><SelectValue /></SelectTrigger>
            <SelectContent>
              <SelectItem v-for="inst in institutions" :key="inst.id" :value="String(inst.id)">
                {{ inst.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </div>
        <Button variant="outline" size="sm" @click="router.push('/backoffice/institutions')">
          Retour à la liste globale
        </Button>
      </CardContent>
    </Card>

    <div v-if="loading" class="py-12 text-center text-sm text-muted-foreground">
      Chargement des détails de l'établissement...
    </div>
    <div v-else-if="!institution" class="py-12 text-center text-sm text-muted-foreground">
      Établissement introuvable ou accès non autorisé.
    </div>
    <div v-else class="space-y-6">
      <!-- Title & Summary Header -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-bold tracking-tight">{{ institution.name }}</h1>
          <p class="text-sm text-muted-foreground">Détail des accès, de la facturation et de l'intelligence artificielle.</p>
        </div>
        <Button variant="outline" size="sm" @click="printInvoice">
          <IconPrinter class="mr-1.5 size-4" /> Imprimer la facture
        </Button>
      </div>

      <!-- Financial Dashboard Cards -->
      <div class="grid gap-6 md:grid-cols-4">
        <Card class="bg-gradient-to-br from-indigo-50/50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-indigo-950/5">
          <CardHeader class="pb-2">
            <CardDescription class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
              Abonnement Fixe
            </CardDescription>
          </CardHeader>
          <CardContent>
            <span class="text-3xl font-extrabold tracking-tight">{{ Number(institution.subscriptionFee).toFixed(2) }} €</span>
            <p class="text-[10px] text-muted-foreground mt-1 font-medium">Licence fixe</p>
          </CardContent>
        </Card>

        <Card class="bg-gradient-to-br from-indigo-50/50 to-indigo-100/30 dark:from-indigo-950/20 dark:to-indigo-950/5">
          <CardHeader class="pb-2">
            <CardDescription class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
              Coût Étudiants
            </CardDescription>
          </CardHeader>
          <CardContent>
            <span class="text-3xl font-extrabold tracking-tight">{{ (Number(institution.costPerStudent) * studentCount).toFixed(2) }} €</span>
            <p class="text-[10px] text-muted-foreground mt-1 font-medium">
              {{ studentCount }} étudiants × {{ Number(institution.costPerStudent).toFixed(2) }} €
            </p>
          </CardContent>
        </Card>

        <Card class="bg-gradient-to-br from-purple-50/50 to-purple-100/30 dark:from-purple-950/20 dark:to-purple-950/5">
          <CardHeader class="pb-2">
            <CardDescription class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400">
              Coût IA Platform
            </CardDescription>
          </CardHeader>
          <CardContent>
            <span class="text-3xl font-extrabold tracking-tight">
              {{ form.aiConfigType === 'global' ? totalAiCost.toFixed(2) : '0.00' }} €
            </span>
            <p class="text-[10px] text-muted-foreground mt-1 font-medium">
              {{ totalAiCalls }} appels IA
              <span v-if="form.aiConfigType === 'custom'"> (Propre clé)</span>
            </p>
          </CardContent>
        </Card>

        <Card class="bg-gradient-to-br from-primary/10 to-primary/5 border-primary/20">
          <CardHeader class="pb-2">
            <CardDescription class="text-xs font-semibold uppercase tracking-wider text-primary">
              Total Facturé
            </CardDescription>
          </CardHeader>
          <CardContent>
            <span class="text-3xl font-extrabold tracking-tight text-primary">{{ totalCost.toFixed(2) }} €</span>
            <p class="text-[10px] text-muted-foreground mt-1 font-medium">Abonnement + Étudiants + Usage IA</p>
          </CardContent>
        </Card>
      </div>

      <!-- MAIN TABS Layout: Left pane (Config, Domains, IA), Right Pane (Students, AI Logs) -->
      <div class="grid gap-6 lg:grid-cols-3">
        <!-- LEFT: Settings -->
        <div class="space-y-6 lg:col-span-1">
          <!-- Allowed Domains -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm">Domaines d'emails autorisés</CardTitle>
              <CardDescription class="text-[11px]">
                Association automatique à l'inscription.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex gap-2">
                <Input v-model="newDomain" placeholder="Ex: etu.univ-bordeaux.fr" @keydown.enter.prevent="addDomain" />
                <Button size="icon" @click="addDomain">
                  <IconPlus class="size-4" />
                </Button>
              </div>
              <div class="space-y-1.5 max-h-[120px] overflow-y-auto pr-1">
                <div v-for="domain in form.emailDomains" :key="domain" class="flex items-center justify-between rounded border p-1.5 text-xs bg-muted/40">
                  <span class="font-mono">@{{ domain }}</span>
                  <Button variant="ghost" size="icon" class="size-6 text-destructive hover:bg-destructive/10" @click="removeDomain(domain)">
                    <IconTrash class="size-3.5" />
                  </Button>
                </div>
                <p v-if="!form.emailDomains.length" class="text-center py-4 text-xs text-muted-foreground italic">
                  Aucun domaine actif.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Invitation Code -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm">Code d'invitation</CardTitle>
              <CardDescription class="text-[11px]">
                Pour le rattachement manuel d'étudiants.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex gap-2">
                <Input v-model="form.invitationCode" placeholder="Aucun code" class="font-mono uppercase font-bold text-center tracking-wider text-xs" />
                <Button variant="outline" size="sm" @click="generateCode">Générer</Button>
              </div>
            </CardContent>
          </Card>

          <!-- AI Configuration -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm">Paramètres Pédagogiques & IA</CardTitle>
              <CardDescription class="text-[11px]">
                Gérez les outils d'IA pour cet établissement.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- AI Switch -->
              <div class="flex items-center justify-between">
                <Label for="aiToggle" class="text-xs cursor-pointer font-semibold">Activer l'évaluation par IA</Label>
                <input
                  id="aiToggle"
                  type="checkbox"
                  v-model="form.aiEnabled"
                  class="size-5 accent-primary cursor-pointer rounded"
                />
              </div>

              <!-- AI Advanced settings conditional -->
              <div v-if="form.aiEnabled" class="space-y-3 border-t pt-3 border-border">
                <div class="space-y-1.5">
                  <Label class="text-xs">Formule / Clé API</Label>
                  <Select v-model="form.aiConfigType">
                    <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                    <SelectContent>
                      <SelectItem value="global">Clé globale ProgressIA (Facturé)</SelectItem>
                      <SelectItem value="custom">Propre Clé API (Clé custom)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Custom API Details -->
                <div v-if="form.aiConfigType === 'custom'" class="space-y-3 bg-muted/40 p-2.5 rounded border border-border/60">
                  <div class="space-y-1.5">
                    <Label class="text-xs">Fournisseur</Label>
                    <Select v-model="form.aiProvider">
                      <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                      <SelectContent>
                        <SelectItem value="groq">Groq</SelectItem>
                        <SelectItem value="openai">OpenAI (GPT)</SelectItem>
                        <SelectItem value="anthropic">Anthropic (Claude)</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <div class="space-y-1.5">
                    <Label class="text-xs">Modèle d'IA</Label>
                    <Input v-model="form.aiModel" placeholder="Ex: gpt-4o-mini" class="h-8 text-xs" />
                  </div>

                  <div class="space-y-1.5">
                    <Label class="text-xs">Clé API Privée</Label>
                    <Input type="password" v-model="form.aiApiKey" placeholder="sk-..." class="h-8 text-xs font-mono" />
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Price Config (Super Admin only) -->
          <Card v-if="auth.isSuperAdmin()">
            <CardHeader class="pb-3">
              <CardTitle class="text-sm">Tarification Financière</CardTitle>
              <CardDescription class="text-[11px]">
                Modifiez les frais appliqués à cet établissement.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="space-y-1.5">
                <Label for="subFee" class="text-xs">Abonnement Annuel Fixe (€)</Label>
                <Input id="subFee" type="number" step="0.01" min="0" v-model="form.subscriptionFee" class="h-8 text-xs" />
              </div>
              <div class="space-y-1.5">
                <Label for="studCost" class="text-xs">Coût par étudiant (€)</Label>
                <Input id="studCost" type="number" step="0.01" min="0" v-model="form.costPerStudent" class="h-8 text-xs" />
              </div>
            </CardContent>
          </Card>

          <Button class="w-full" @click="saveSettings" :disabled="saving">
            {{ saving ? "Enregistrement..." : "Enregistrer la configuration" }}
          </Button>
        </div>

        <!-- RIGHT: Students list & AI Log lists -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Active students List -->
          <Card>
            <CardHeader class="pb-3">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <CardTitle class="text-sm">Étudiants inscrits ({{ studentCount }})</CardTitle>
                  <CardDescription class="text-[11px]">
                    Liste nominative des comptes étudiants.
                  </CardDescription>
                </div>
                <Input v-model="searchStudent" placeholder="Rechercher un étudiant..." class="max-w-[200px] h-8 text-xs" />
              </div>
            </CardHeader>
            <CardContent class="p-0 max-h-[300px] overflow-y-auto border-t">
              <div class="divide-y">
                <div
                  v-for="student in filteredStudents"
                  :key="student.id"
                  class="p-3 flex items-center justify-between text-xs hover:bg-muted/10"
                >
                  <div>
                    <p class="font-semibold">{{ student.name }}</p>
                    <p class="text-muted-foreground text-[10px]">{{ student.email }}</p>
                    <div class="flex gap-2 text-[10px] text-muted-foreground pt-0.5">
                      <span v-if="student.studentSemester">Semestre : {{ student.studentSemester.name }}</span>
                      <span v-if="student.studentFormation">· Formation : {{ student.studentFormation.name }}</span>
                    </div>
                  </div>
                  <Badge variant="secondary">{{ student.points }} Pts</Badge>
                </div>
                <p v-if="!filteredStudents.length" class="text-center py-6 text-xs text-muted-foreground italic">
                  Aucun étudiant trouvé.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- AI Usage Logs List -->
          <Card>
            <CardHeader class="pb-3">
              <CardTitle class="text-sm">Suivi des appels et de la consommation IA</CardTitle>
              <CardDescription class="text-[11px]">
                Historique des jetons consommés par l'évaluation IA.
              </CardDescription>
            </CardHeader>
            <CardContent class="p-0 border-t max-h-[300px] overflow-y-auto">
              <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px] border-collapse">
                  <thead>
                    <tr class="border-b bg-muted/40 font-semibold text-muted-foreground text-[9px] uppercase tracking-wider">
                      <th class="p-2">Date</th>
                      <th class="p-2">Utilisateur</th>
                      <th class="p-2 text-right">Prompt (tk)</th>
                      <th class="p-2 text-right">Completion (tk)</th>
                      <th class="p-2 text-right">Frais</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y">
                    <tr v-for="log in aiLogs" :key="log.id" class="hover:bg-muted/10">
                      <td class="p-2 text-muted-foreground">{{ formatDate(log.createdAt) }}</td>
                      <td class="p-2 font-medium">{{ log.user?.name || 'Inconnu' }}</td>
                      <td class="p-2 text-right font-mono">{{ log.promptTokens }}</td>
                      <td class="p-2 text-right font-mono">{{ log.completionTokens }}</td>
                      <td class="p-2 text-right font-semibold text-primary">
                        {{ form.aiConfigType === 'global' ? Number(log.estimatedCost).toFixed(4) : '0.0000' }} €
                      </td>
                    </tr>
                    <tr v-if="!aiLogs.length">
                      <td colspan="5" class="p-6 text-center text-muted-foreground italic">
                        Aucun appel IA enregistré.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
    <!-- Printable Invoice Template (only visible during print) -->
    <div class="hidden print:block font-sans text-slate-800 p-8 space-y-6 max-w-3xl mx-auto text-sm">
      <!-- Invoice Header -->
      <div class="flex justify-between items-start border-b pb-6 border-slate-300">
        <div>
          <h2 class="text-3xl font-extrabold tracking-tight text-indigo-950">ProgressIA</h2>
          <p class="text-[10px] text-muted-foreground mt-1">Plateforme Pédagogique Révolutionnaire</p>
        </div>
        <div class="text-right">
          <h3 class="text-lg font-bold uppercase tracking-wider text-slate-500">Facture de Frais</h3>
          <p class="text-xs text-muted-foreground mt-1">Réf: INV-{{ activeId }}-{{ new Date().getFullYear() }}</p>
          <p class="text-xs text-muted-foreground mt-0.5">Date : {{ new Date().toLocaleDateString('fr-FR') }}</p>
        </div>
      </div>

      <!-- Bill to / client details -->
      <div class="grid grid-cols-2 gap-8 pt-4">
        <div>
          <p class="text-[10px] uppercase font-bold text-slate-400">Établissement facturé</p>
          <p class="font-bold text-base text-slate-900 mt-1">{{ institution?.name }}</p>
          <p class="text-xs text-slate-500 mt-1" v-if="form.emailDomains.length">
            Domaines : {{ form.emailDomains.join(', ') }}
          </p>
        </div>
        <div class="text-right">
          <p class="text-[10px] uppercase font-bold text-slate-400">Émetteur</p>
          <p class="font-bold text-slate-900 mt-1">Progressia SaaS Group</p>
          <p class="text-xs text-slate-500 mt-0.5">billing@progressia.test</p>
        </div>
      </div>

      <!-- Invoice Items Table -->
      <table class="w-full text-left mt-8 border-collapse">
        <thead>
          <tr class="border-b-2 border-slate-300 text-[10px] uppercase font-bold text-slate-500 bg-slate-50">
            <th class="p-3">Description du service</th>
            <th class="p-3 text-right">Quantité</th>
            <th class="p-3 text-right">Tarif Unitaire</th>
            <th class="p-3 text-right">Total HT</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr>
            <td class="p-3 py-4">
              <p class="font-semibold text-slate-900">Abonnement annuel fixe (ProgressIA Licence)</p>
              <p class="text-[10px] text-muted-foreground mt-0.5">Accès à l'espace d'administration et hébergement</p>
            </td>
            <td class="p-3 py-4 text-right font-mono">1</td>
            <td class="p-3 py-4 text-right font-mono">{{ Number(form.subscriptionFee).toFixed(2) }} €</td>
            <td class="p-3 py-4 text-right font-mono font-semibold">{{ Number(form.subscriptionFee).toFixed(2) }} €</td>
          </tr>
          <tr>
            <td class="p-3 py-4">
              <p class="font-semibold text-slate-900">Frais d'inscriptions étudiants (BUT/Licence/Master)</p>
              <p class="text-[10px] text-muted-foreground mt-0.5">Licences actives de comptes apprenants</p>
            </td>
            <td class="p-3 py-4 text-right font-mono">{{ studentCount }}</td>
            <td class="p-3 py-4 text-right font-mono">{{ Number(form.costPerStudent).toFixed(2) }} €</td>
            <td class="p-3 py-4 text-right font-mono font-semibold">{{ (Number(form.costPerStudent) * studentCount).toFixed(2) }} €</td>
          </tr>
          <tr v-if="form.aiConfigType === 'global'">
            <td class="p-3 py-4">
              <p class="font-semibold text-slate-900">Consommation d'appels d'évaluation IA (Clé ProgressIA)</p>
              <p class="text-[10px] text-muted-foreground mt-0.5">Appels API Groq Llama 3 avec coût variable</p>
            </td>
            <td class="p-3 py-4 text-right font-mono">{{ totalAiCalls }}</td>
            <td class="p-3 py-4 text-right font-mono">Variable</td>
            <td class="p-3 py-4 text-right font-mono font-semibold">{{ totalAiCost.toFixed(2) }} €</td>
          </tr>
        </tbody>
      </table>

      <!-- Total due -->
      <div class="flex justify-end pt-6 border-t-2 border-slate-300">
        <div class="w-[250px] space-y-2 text-right">
          <div class="flex justify-between text-xs text-slate-500">
            <span>Total Partiel HT :</span>
            <span class="font-mono">{{ totalCost.toFixed(2) }} €</span>
          </div>
          <div class="flex justify-between text-xs text-slate-500">
            <span>TVA (0% - Franchise de TVA) :</span>
            <span class="font-mono">0.00 €</span>
          </div>
          <div class="flex justify-between border-t pt-2 font-bold text-base text-slate-950">
            <span>Total à payer :</span>
            <span class="font-mono">{{ totalCost.toFixed(2) }} €</span>
          </div>
        </div>
      </div>

      <!-- Payment details & terms -->
      <div class="pt-8 border-t border-slate-200 text-[10px] text-slate-500 leading-relaxed">
        <p class="font-semibold uppercase tracking-wider text-slate-400">Conditions de règlement</p>
        <p class="mt-1">Paiement exigible à 30 jours à réception de facture. Règlement par virement bancaire sur le compte ProgressIA.</p>
        <p class="mt-0.5">IBAN: FR76 3000 2012 3456 7890 1234 567 · BIC: PRGSIAPXXX</p>
      </div>
    </div>
  </BackofficeLayout>
</template>

<style>
@media print {
  /* Hide everything except the print-friendly invoice block */
  body * {
    visibility: hidden;
  }
  .print\:block, .print\:block * {
    visibility: visible;
  }
  .print\:block {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, watch } from "vue"
import { useRoute, useRouter } from "vue-router"
import { IconPlus, IconTrash, IconPrinter } from "@tabler/icons-vue"
import BackofficeLayout from "@/components/BackofficeLayout.vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { showToast } from "@/composables/useToast"
import { useAuthStore } from "@/stores/auth"
import { api } from "@/api/client"

interface Institution {
  id: number
  name: string
  subscriptionFee: string
  costPerStudent: string
  emailDomains: string[]
  invitationCode: string | null
  aiEnabled: boolean
  aiConfigType: string
  aiProvider: string
  aiModel: string
  aiApiKey: string | null
}

interface AiLog {
  id: number
  createdAt: string
  promptTokens: number
  completionTokens: number
  estimatedCost: string
  user?: { name: string }
}

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)
const newDomain = ref("")
const searchStudent = ref("")

const institutions = ref<Institution[]>([])
const institution = ref<Institution | null>(null)
const students = ref<any[]>([])
const aiLogs = ref<AiLog[]>([])

const form = reactive({
  emailDomains: [] as string[],
  invitationCode: "",
  subscriptionFee: "0.00",
  costPerStudent: "0.00",
  aiEnabled: false,
  aiConfigType: "global",
  aiProvider: "groq",
  aiModel: "llama-3.1-70b-versatile",
  aiApiKey: ""
})

const activeId = computed(() => {
  return Number(route.params.id)
})

const hasAccess = computed(() => {
  if (auth.isSuperAdmin()) return true
  return String(auth.user?.institution?.id) === String(activeId.value)
})

const studentCount = computed(() => students.value.length)

const totalAiCalls = computed(() => aiLogs.value.length)
const totalAiCost = computed(() => {
  return aiLogs.value.reduce((acc, log) => acc + Number(log.estimatedCost || 0), 0)
})

const totalCost = computed(() => {
  if (!institution.value) return 0
  const fixed = Number(form.subscriptionFee || 0)
  const perStudent = Number(form.costPerStudent || 0)
  const aiCost = form.aiConfigType === 'global' ? totalAiCost.value : 0
  return fixed + (studentCount.value * perStudent) + aiCost
})

const filteredStudents = computed(() => {
  const q = searchStudent.value.trim().toLowerCase()
  if (!q) return students.value
  return students.value.filter(s => 
    s.name.toLowerCase().includes(q) || 
    s.email.toLowerCase().includes(q)
  )
})

async function loadData() {
  if (!hasAccess.value) {
    showToast("Accès non autorisé à cet établissement.", "error")
    router.push("/")
    return
  }

  loading.value = true
  try {
    if (auth.isSuperAdmin()) {
      const listRes = await api.get<Institution[]>("/api/institutions")
      institutions.value = (listRes as any)["hydra:member"] || listRes
    }

    const inst = await api.get<Institution>(`/api/institutions/${activeId.value}`)
    institution.value = inst
    form.emailDomains = [...(inst.emailDomains || [])]
    form.invitationCode = inst.invitationCode || ""
    form.subscriptionFee = inst.subscriptionFee
    form.costPerStudent = inst.costPerStudent
    form.aiEnabled = inst.aiEnabled
    form.aiConfigType = inst.aiConfigType || "global"
    form.aiProvider = inst.aiProvider || "groq"
    form.aiModel = inst.aiModel || "llama-3.1-70b-versatile"
    form.aiApiKey = inst.aiApiKey || ""

    const [uRes, logRes] = await Promise.all([
      api.get<any[]>("/api/users"),
      api.get<AiLog[]>("/api/ai_usage_logs")
    ])

    const allUsers = (uRes as any)["hydra:member"] || uRes
    students.value = allUsers.filter((u: any) => 
      u.institution && 
      String(u.institution.id) === String(activeId.value) && 
      !u.roles.includes("ROLE_TEACHER") &&
      !u.roles.includes("ROLE_SCHOOL_ADMIN") &&
      !u.roles.includes("ROLE_SUPER_ADMIN")
    )

    const rawLogs = (logRes as any)["hydra:member"] || logRes
    // Scoped logs are automatically filtered by doctrine extension if School Admin
    // If Super Admin, we filter them to matching institution in frontend
    aiLogs.value = rawLogs.filter((log: any) => 
      log.institution && 
      String(log.institution.id) === String(activeId.value)
    )
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

watch(() => route.params.id, () => {
  loadData()
})

function onQuickSwitch(val: any) {
  if (val) {
    router.push(`/backoffice/institutions/${val}`)
  }
}

function addDomain() {
  const domain = newDomain.value.trim().toLowerCase().replace(/^@/, "")
  if (!domain) return

  const domainRegex = /^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}$/
  if (!domainRegex.test(domain)) {
    showToast("Format de domaine invalide.", "error")
    return
  }

  if (form.emailDomains.includes(domain)) {
    showToast("Ce domaine est déjà configuré.", "error")
    return
  }

  form.emailDomains.push(domain)
  newDomain.value = ""
}

function removeDomain(domain: string) {
  form.emailDomains = form.emailDomains.filter(d => d !== domain)
}

function generateCode() {
  const length = 8
  const chars = "ABCDEFGHJKLMNOPQRSTUVWXYZ23456789"
  let result = ""
  for (let i = 0; i < length; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  form.invitationCode = result
}

async function saveSettings() {
  if (!institution.value) return

  saving.value = true
  try {
    const payload: any = {
      emailDomains: form.emailDomains,
      invitationCode: form.invitationCode.trim().toUpperCase() || null,
      aiEnabled: form.aiEnabled,
      aiConfigType: form.aiConfigType,
      aiProvider: form.aiProvider,
      aiModel: form.aiModel,
      aiApiKey: form.aiApiKey.trim() || null
    }

    if (auth.isSuperAdmin()) {
      payload.subscriptionFee = String(Number(form.subscriptionFee || 0).toFixed(2))
      payload.costPerStudent = String(Number(form.costPerStudent || 0).toFixed(2))
    }

    await api.patch(`/api/institutions/${activeId.value}`, payload)
    showToast("Configuration enregistrée avec succès !")
    await loadData()
  } catch (e: any) {
    console.error(e)
    showToast(e.body?.detail || "Erreur lors de l'enregistrement.", "error")
  } finally {
    saving.value = false
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

function printInvoice() {
  window.print()
}
</script>
