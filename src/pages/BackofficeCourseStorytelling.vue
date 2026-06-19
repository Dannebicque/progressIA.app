<template>
  <BackofficeLayout>
    <div v-if="loading" class="py-12 text-center text-muted-foreground text-sm flex flex-col items-center gap-2">
      <IconLoader2 class="size-6 animate-spin text-primary" />
      <span>Chargement des données de scénarisation...</span>
    </div>
    
    <div v-else-if="course" class="space-y-6">
      <!-- Header with back button -->
      <Card class="overflow-hidden pt-0">
        <div class="h-2 w-full" :style="{ background: accent }"></div>
        <CardContent class="pt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-start gap-4">
            <div class="grid size-12 shrink-0 place-items-center rounded-xl text-white animate-pulse"
              :style="{ background: `linear-gradient(135deg, ${accent}, ${accent}99)` }">
              <IconMail class="size-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <RouterLink :to="`/backoffice/courses?courseId=${course.id}`">
                  <Button variant="outline" size="xs">← Retour au cours</Button>
                </RouterLink>
                <h1 class="text-xl font-bold tracking-tight">{{ course.title }}</h1>
              </div>
              <p class="text-xs text-muted-foreground mt-1">
                Scénarisation & Storytelling par Email
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Tabs Navigation -->
      <div class="flex border-b border-border gap-2">
        <button
          v-for="t in tabs"
          :key="t.id"
          class="px-4 py-2 text-sm font-semibold border-b-2 transition-all flex items-center gap-1.5 cursor-pointer"
          :class="activeTab === t.id
            ? 'border-primary text-primary'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
          :style="activeTab === t.id ? { borderColor: accent, color: accent } : {}"
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="size-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- Tab 1: Send email / campaign -->
      <div v-if="activeTab === 'send'" class="grid gap-6 lg:grid-cols-12 items-start">
        
        <!-- Left: Form Editor -->
        <Card class="lg:col-span-7">
          <CardHeader>
            <CardTitle class="text-base font-semibold">Préparer une transmission narrative</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4 text-sm">
            <!-- Template quick loader -->
            <div class="space-y-1.5">
              <Label>Charger un modèle existant</Label>
              <Select v-model="selectedTemplateId" @update:model-value="onTemplateChange">
                <SelectTrigger><SelectValue placeholder="Choisir un modèle..." /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Aucun (partir d'un mail vierge)</SelectItem>
                  <SelectItem v-for="t in templates" :key="t.id" :value="String(t.id)">
                    [{{ getSessionTitle(t.session) }}] {{ t.title }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <!-- Group Targets -->
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label>Groupe destinataire</Label>
                <Select v-model="targetGroup" @update:model-value="updateTargetRecipientsCount">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="ALL">Tous les étudiants ({{ allStudentsCount }} édu.)</SelectItem>
                    <SelectItem v-for="g in groupOptions" :key="g" :value="g">
                      Groupe : {{ g }} ({{ getGroupRecipientsCount(g) }} édu.)
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <!-- Associated Session -->
              <div class="space-y-1.5">
                <Label>Séance associée (pour les variables)</Label>
                <Select v-model="selectedSessionId">
                  <SelectTrigger><SelectValue placeholder="Aucune" /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Aucune</SelectItem>
                    <SelectItem v-for="s in course.sessions" :key="s.id" :value="String(s.id)">
                      {{ s.title }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <Separator />

            <!-- Email Subject -->
            <div class="space-y-1.5">
              <Label>Sujet du mail</Label>
              <Input v-model="emailSubject" placeholder="ex: 🚀 [Briefing] Préparez-vous pour le défi..." />
            </div>

            <!-- Email Body -->
            <div class="space-y-1.5">
              <div class="flex justify-between items-center">
                <Label>Contenu du mail (Storytelling)</Label>
                <span class="text-[10px] text-muted-foreground italic">Placeholders dispo : {nom_etudiant}, {titre_cours}, {titre_seance}</span>
              </div>
              <textarea
                v-model="emailContent"
                rows="8"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                placeholder="Écrivez le message narratif ici... Vous pouvez insérer des variables libres comme {date} ou {heure}."
              ></textarea>
            </div>

            <!-- Detected Custom Variables -->
            <div v-if="detectedVariables.length" class="space-y-3 rounded-lg border bg-muted/20 p-3">
              <Label class="text-xs font-bold text-amber-600 dark:text-amber-500 uppercase tracking-wide">
                Variables détectées à remplir :
              </Label>
              <div class="grid gap-3 sm:grid-cols-2">
                <div v-for="v in detectedVariables" :key="v" class="space-y-1">
                  <Label class="text-xs capitalize font-medium">{ {{ v }} }</Label>
                  <Input 
                    v-model="manualVariables[v]" 
                    placeholder="Saisir la valeur..." 
                    class="h-8 text-xs" 
                    @input="debouncedFetchPreview"
                  />
                </div>
              </div>
            </div>

            <div class="pt-2 flex justify-between items-center">
              <div class="text-xs text-muted-foreground flex items-center gap-1">
                <IconUsers class="size-4 text-primary" :style="{ color: accent }" />
                <span>Ce message ciblera <strong>{{ targetRecipientsCount }}</strong> étudiant(s).</span>
              </div>

              <Button
                :style="{ backgroundColor: accent }"
                class="gap-1.5"
                :disabled="sending || targetRecipientsCount === 0 || !emailSubject.trim() || !emailContent.trim()"
                @click="triggerSend"
              >
                <IconLoader2 v-if="sending" class="size-4 animate-spin" />
                <IconSend v-else class="size-4" />
                Envoyer le message
              </Button>
            </div>
          </CardContent>
        </Card>

        <!-- Right: Live Inbox Preview -->
        <Card class="lg:col-span-5 border-dashed">
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
              <IconEye class="size-4" />
              Aperçu en direct
            </CardTitle>
          </CardHeader>
          <CardContent class="text-sm">
            <!-- Simulated Mailbox -->
            <div class="rounded-xl border shadow-md bg-zinc-950 text-zinc-100 overflow-hidden font-sans">
              <!-- Header Bar -->
              <div class="bg-zinc-900 border-b border-zinc-800 px-4 py-3 flex items-center gap-2">
                <div class="flex gap-1.5 shrink-0">
                  <div class="size-3 rounded-full bg-red-500"></div>
                  <div class="size-3 rounded-full bg-yellow-500"></div>
                  <div class="size-3 rounded-full bg-green-500"></div>
                </div>
                <div class="text-[10px] text-zinc-500 mx-auto select-none font-mono">Transmission Sécurisée</div>
              </div>

              <!-- Metadata fields -->
              <div class="p-4 border-b border-zinc-800 space-y-2 text-xs text-zinc-400">
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-zinc-500 w-12 select-none">De :</span>
                  <span class="text-amber-500 font-medium">no-reply@pedagoflow.com</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-zinc-500 w-12 select-none">À :</span>
                  <span class="text-zinc-300">{{ previewData.sampleStudent.name }} ({{ previewData.sampleStudent.email }})</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="font-semibold text-zinc-500 w-12 select-none">Sujet :</span>
                  <span class="text-zinc-200 font-bold">{{ previewData.subject || '[Sans sujet]' }}</span>
                </div>
              </div>

              <!-- Body of Mail -->
              <div class="p-6 min-h-64 max-h-[400px] overflow-y-auto bg-zinc-900/40 text-zinc-300 leading-relaxed font-serif whitespace-pre-line text-sm"
                v-html="formattedPreviewContent">
              </div>
            </div>
            
            <p class="text-[10px] text-muted-foreground text-center mt-3 italic">
              L'aperçu compile les placeholders dynamiques pour un étudiant factice de la classe.
            </p>
          </CardContent>
        </Card>
      </div>

      <!-- Tab 2: Templates / Modèles CRUD -->
      <div v-else-if="activeTab === 'templates'" class="grid gap-6 lg:grid-cols-3">
        <!-- List of existing templates -->
        <div class="lg:col-span-2 space-y-4">
          <Card>
            <CardHeader class="pb-2">
              <CardTitle class="text-base font-semibold">Modèles de mails configurés</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div
                v-for="t in templates"
                :key="t.id"
                class="rounded-lg border p-4 hover:border-primary/40 transition-all flex flex-col sm:flex-row sm:items-start justify-between gap-4"
              >
                <div class="space-y-1.5 flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <h4 class="font-bold text-sm text-foreground truncate">{{ t.title }}</h4>
                    <Badge variant="outline" class="text-[10px] border-amber-200 dark:border-amber-900 bg-amber-50/20 text-amber-600 dark:text-amber-400">
                      {{ getSessionTitle(t.session) }}
                    </Badge>
                    <Badge v-if="t.defaultTarget && t.defaultTarget !== 'ALL'" variant="secondary" class="text-[9px]">
                      Destinataire : {{ t.defaultTarget }}
                    </Badge>
                  </div>
                  <p class="text-xs text-muted-foreground"><span class="font-semibold text-foreground">Sujet :</span> {{ t.subject }}</p>
                  <p class="text-xs text-muted-foreground/80 line-clamp-2 italic pt-1 border-t border-dashed mt-1 bg-muted/10 p-2 rounded">
                    {{ t.content }}
                  </p>
                </div>
                <div class="flex items-center gap-2 shrink-0 self-end sm:self-start">
                  <Button size="xs" variant="outline" @click="editTemplate(t)">
                    Modifier
                  </Button>
                  <Button size="xs" variant="destructive" @click="deleteTemplate(t.id)">
                    Supprimer
                  </Button>
                </div>
              </div>

              <p v-if="!templates.length" class="text-xs text-muted-foreground italic text-center py-8">
                Aucun modèle de mail créé. Utilisez le formulaire pour en ajouter un.
              </p>
            </CardContent>
          </Card>
        </div>

        <!-- Add / Edit Template Form -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base font-semibold">{{ isEditingTemplate ? 'Modifier le modèle' : 'Nouveau modèle de mail' }}</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3 text-sm">
            <div class="space-y-1.5">
              <Label>Titre du modèle (interne)</Label>
              <Input v-model="templateForm.title" placeholder="ex: Relance séance 2" />
            </div>

            <div class="space-y-1.5">
              <Label>Associer à la Séance</Label>
              <Select v-model="templateForm.session_id">
                <SelectTrigger><SelectValue placeholder="Aucune" /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Aucune</SelectItem>
                  <SelectItem v-for="s in course.sessions" :key="s.id" :value="String(s.id)">
                    {{ s.title }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-1.5">
              <Label>Cible par défaut</Label>
              <Select v-model="templateForm.defaultTarget">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="ALL">Tous les étudiants</SelectItem>
                  <SelectItem v-for="g in groupOptions" :key="g" :value="g">Groupe : {{ g }}</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div class="space-y-1.5">
              <Label>Sujet par défaut</Label>
              <Input v-model="templateForm.subject" placeholder="ex: 🚀 Briefing : Mission {date} !" />
            </div>

            <div class="space-y-1.5">
              <Label>Corps du message</Label>
              <textarea
                v-model="templateForm.content"
                rows="6"
                class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                placeholder="Texte du message avec variables libres {date}, {heure}..."
              ></textarea>
            </div>

            <div class="pt-2 flex gap-2">
              <Button v-if="isEditingTemplate" variant="outline" class="flex-1 text-xs" @click="cancelEditTemplate">
                Annuler
              </Button>
              <Button :style="{ backgroundColor: accent }" class="flex-1 text-xs" :disabled="!templateForm.title || !templateForm.subject || !templateForm.content" @click="saveTemplate">
                {{ isEditingTemplate ? 'Mettre à jour' : 'Créer le modèle' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Tab 3: History / Historique -->
      <div v-else-if="activeTab === 'history'" class="space-y-4">
        <Card>
          <CardHeader>
            <CardTitle class="text-base font-semibold">Historique des messages envoyés</CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <table class="w-full text-xs text-left border-collapse">
                <thead class="bg-muted text-[10px] uppercase font-bold text-muted-foreground">
                  <tr>
                    <th class="p-3 pl-6">Sujet</th>
                    <th class="p-3">Séance</th>
                    <th class="p-3">Groupe ciblé</th>
                    <th class="p-3">Destinataires</th>
                    <th class="p-3">Date d'envoi</th>
                    <th class="p-3 text-right pr-6">Action</th>
                  </tr>
                </thead>
                <tbody class="divide-y text-muted-foreground">
                  <tr v-for="h in history" :key="h.id" class="hover:bg-muted/40 transition-colors">
                    <td class="p-3 pl-6">
                      <div class="font-semibold text-sm text-foreground truncate max-w-xs">{{ h.subject }}</div>
                    </td>
                    <td class="p-3">
                      <Badge variant="outline" class="text-[10px]">{{ getSessionTitle(h.session) }}</Badge>
                    </td>
                    <td class="p-3 font-medium">{{ h.targetGroup === 'ALL' ? 'Tous' : h.targetGroup }}</td>
                    <td class="p-3 font-bold text-foreground">{{ h.recipientsCount }} étudiant(s)</td>
                    <td class="p-3">{{ formatDate(h.sentAt) }}</td>
                    <td class="p-3 text-right pr-6">
                      <Button size="xs" variant="outline" @click="viewHistoryDetail(h)">
                        Voir le contenu
                      </Button>
                    </td>
                  </tr>
                  <tr v-if="!history.length">
                    <td colspan="6" class="p-10 text-center text-muted-foreground italic">Aucune transmission enregistrée dans l'historique de ce cours.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- History Detail Dialog -->
      <Dialog :open="isHistoryDetailOpen" @update:open="(v: boolean) => (isHistoryDetailOpen = v)">
        <DialogContent class="max-w-2xl">
          <DialogHeader>
            <DialogTitle>Message envoyé dans l'historique</DialogTitle>
            <DialogDescription>
              Détail du message envoyé le {{ selectedHistoryItem ? formatDate(selectedHistoryItem.sentAt) : '' }}
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedHistoryItem" class="space-y-4 text-xs">
            <div class="grid grid-cols-2 gap-4 rounded-md border p-3 bg-muted/20">
              <div>
                <span class="font-semibold text-muted-foreground">Groupe cible :</span>
                <span class="ml-2 font-bold text-foreground">{{ selectedHistoryItem.targetGroup === 'ALL' ? 'Tous' : selectedHistoryItem.targetGroup }}</span>
              </div>
              <div>
                <span class="font-semibold text-muted-foreground">Volume d'envois :</span>
                <span class="ml-2 font-bold text-foreground">{{ selectedHistoryItem.recipientsCount }} étudiant(s)</span>
              </div>
              <div>
                <span class="font-semibold text-muted-foreground">Séance liée :</span>
                <span class="ml-2">{{ getSessionTitle(selectedHistoryItem.session) }}</span>
              </div>
              <div>
                <span class="font-semibold text-muted-foreground">Expéditeur :</span>
                <span class="ml-2 font-medium">{{ selectedHistoryItem.sender?.name || 'Enseignant' }}</span>
              </div>
            </div>

            <div class="space-y-1">
              <Label class="font-semibold">Sujet envoyé :</Label>
              <div class="p-2 border rounded bg-background font-bold text-sm text-foreground">
                {{ compileHistoryText(selectedHistoryItem.subject, selectedHistoryItem) }}
              </div>
            </div>

            <div class="space-y-1">
              <div class="flex justify-between items-center">
                <Label class="font-semibold">Corps du message envoyé :</Label>
                <span class="text-[10px] text-muted-foreground italic">Placeholders remplacés</span>
              </div>
              <div class="p-4 border rounded bg-background whitespace-pre-line leading-relaxed max-h-60 overflow-auto font-serif text-zinc-800 dark:text-zinc-200">
                {{ compileHistoryText(selectedHistoryItem.content, selectedHistoryItem) }}
              </div>
            </div>

            <div v-if="selectedHistoryItem.variables && Object.keys(selectedHistoryItem.variables).length" class="space-y-1.5 pt-2">
              <Label class="font-semibold text-muted-foreground text-[10px] uppercase">Variables d'époque utilisées :</Label>
              <div class="flex flex-wrap gap-2">
                <Badge v-for="(val, key) in selectedHistoryItem.variables" :key="key" variant="outline" class="text-[10px]">
                  {{ key }} : {{ val }}
                </Badge>
              </div>
            </div>
          </div>

          <DialogFooter>
            <Button @click="isHistoryDetailOpen = false">Fermer</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, reactive } from 'vue'
import { useRoute } from 'vue-router'
import {
  IconMail,
  IconSend,
  IconHistory,
  IconTemplate,
  IconUsers,
  IconLoader2,
  IconPlus,
  IconTrash,
  IconEye
} from '@tabler/icons-vue'
import BackofficeLayout from '@/components/BackofficeLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog'
import { useCoursesStore } from '@/stores/courses'
import { api } from '@/api/client'
import { showToast } from '@/composables/useToast'
import { confirmDialog } from '@/composables/useConfirm'

interface Student {
  id: number
  name: string
  email: string
  studentGroup: string
}

interface EmailTemplate {
  id: number
  title: string
  subject: string
  content: string
  session: any
  defaultTarget: string
}

interface SentEmail {
  id: number
  subject: string
  content: string
  sentAt: string
  targetGroup: string
  recipientsCount: number
  session: any
  sender: { id: number; name: string }
  variables?: any
}

const route = useRoute()
const store = useCoursesStore()

const loading = ref(true)
const sending = ref(false)

const course = computed(() => store.getCourse(route.params.id as string))
const accent = computed(() => course.value?.accentColor || '#7c3aed')

// Tabs definition
const tabs = [
  { id: 'send', label: 'Envoyer un mail', icon: IconSend },
  { id: 'templates', label: 'Modèles de mails', icon: IconTemplate },
  { id: 'history', label: 'Historique', icon: IconHistory }
]
const activeTab = ref('send')

// Students and targeting state
const students = ref<Student[]>([])
const templates = ref<EmailTemplate[]>([])
const history = ref<SentEmail[]>([])
const groups = ref<string[]>([])

// Sending form fields
const selectedTemplateId = ref('none')
const targetGroup = ref('ALL')
const selectedSessionId = ref('none')
const emailSubject = ref('')
const emailContent = ref('')
const manualVariables = ref<Record<string, string>>({})

// Preview state
const previewData = reactive({
  subject: '',
  content: '',
  sampleStudent: {
    name: 'Jean Dupont',
    email: 'jean.dupont@etu.univ.fr'
  }
})

// Modèle Form fields
const isEditingTemplate = ref(false)
const editingTemplateId = ref<number | null>(null)
const templateForm = reactive({
  title: '',
  subject: '',
  content: '',
  session_id: 'none',
  defaultTarget: 'ALL'
})

// History details dialog state
const isHistoryDetailOpen = ref(false)
const selectedHistoryItem = ref<SentEmail | null>(null)

// Computed groups options and student counts
const allStudentsCount = computed(() => students.value.length)

const groupOptions = computed(() => {
  return groups.value
})

function getGroupRecipientsCount(groupName: string): number {
  return students.value.filter(s => s.studentGroup && s.studentGroup.includes(groupName)).length
}

const targetRecipientsCount = ref(0)

function updateTargetRecipientsCount() {
  if (targetGroup.value === 'ALL') {
    targetRecipientsCount.value = allStudentsCount.value
  } else {
    targetRecipientsCount.value = getGroupRecipientsCount(targetGroup.value)
  }
}

// Detect variables dynamic placeholders in Subject and Content (excluding defaults)
const detectedVariables = computed(() => {
  const text = emailSubject.value + ' ' + emailContent.value
  const matches = text.match(/\{([a-zA-Z0-9_]+)\}/g) || []
  const keys = [...new Set(matches.map(m => m.slice(1, -1)))]
  const defaults = ['nom_etudiant', 'titre_cours', 'titre_seance']
  return keys.filter(k => !defaults.includes(k))
})

// Initialize manual variables structure
watch(detectedVariables, (newVars) => {
  const nextVars: Record<string, string> = {}
  for (const v of newVars) {
    nextVars[v] = manualVariables.value[v] || ''
  }
  manualVariables.value = nextVars
}, { immediate: true })

// Formatted email content for HTML preview container (keeping newlines)
const formattedPreviewContent = computed(() => {
  return previewData.content.replace(/\n/g, '<br/>')
})

// Load All Page Data
async function loadData() {
  loading.value = true
  try {
    if (!store.loaded) await store.fetchCourses()
    
    // Fetch unique student groups
    groups.value = await api.get<string[]>(`/api/teacher/courses/${route.params.id}/groups`)
    
    // Fetch students list (to count sizes)
    const allStudents = await api.get<any[]>('/api/teacher/students')
    students.value = allStudents.map(s => ({
      id: s.id,
      name: s.name,
      email: s.email,
      studentGroup: s.studentGroup
    }))
    
    // Fetch templates
    const allTemplates = await api.get<any[]>('/api/email_templates')
    templates.value = allTemplates
      .filter(t => {
        const cId = getRelationId(t.course)
        return String(cId) === String(route.params.id)
      })
      .map(t => ({
        id: t.id,
        title: t.title,
        subject: t.subject,
        content: t.content,
        session: t.session,
        defaultTarget: t.defaultTarget || 'ALL'
      }))
      
    // Fetch history
    const allHistory = await api.get<any[]>(`/api/sent_emails?course=/api/courses/${route.params.id}`)
    history.value = allHistory.map(h => ({
      id: h.id,
      subject: h.subject,
      content: h.content,
      sentAt: h.sentAt,
      targetGroup: h.targetGroup,
      recipientsCount: h.recipientsCount,
      session: h.session,
      sender: h.sender,
      variables: h.variables
    }))

    updateTargetRecipientsCount()
    debouncedFetchPreview()
  } catch (e) {
    console.error(e)
    showToast('Erreur lors du chargement des données de storytelling', 'error')
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})

// Template loading callback
function onTemplateChange() {
  if (selectedTemplateId.value === 'none') {
    emailSubject.value = ''
    emailContent.value = ''
    selectedSessionId.value = 'none'
    targetGroup.value = 'ALL'
    updateTargetRecipientsCount()
    debouncedFetchPreview()
    return
  }

  const selected = templates.value.find(t => String(t.id) === selectedTemplateId.value)
  if (selected) {
    emailSubject.value = selected.subject
    emailContent.value = selected.content
    targetGroup.value = selected.defaultTarget || 'ALL'
    selectedSessionId.value = selected.session ? String(getRelationId(selected.session) || 'none') : 'none'
    updateTargetRecipientsCount()
    debouncedFetchPreview()
  }
}

// Live preview fetching with slight debounce
let previewTimeout: number | null = null
function debouncedFetchPreview() {
  if (previewTimeout) window.clearTimeout(previewTimeout)
  previewTimeout = window.setTimeout(fetchPreview, 350)
}

watch([emailSubject, emailContent, selectedSessionId], () => {
  debouncedFetchPreview()
})

async function fetchPreview() {
  if (!course.value) return
  
  try {
    const res = await api.post<any>(`/api/teacher/courses/${route.params.id}/preview-email`, {
      subject: emailSubject.value,
      content: emailContent.value,
      session_id: selectedSessionId.value === 'none' ? null : Number(selectedSessionId.value),
      variables: manualVariables.value
    })
    
    previewData.subject = res.subject
    previewData.content = res.content
    previewData.sampleStudent = res.sampleStudent
  } catch (e) {
    console.error('Aperçu email indisponible', e)
  }
}

// Send Campaign Trigger
async function triggerSend() {
  if (!emailSubject.value.trim() || !emailContent.value.trim()) return
  
  if (
    !(await confirmDialog({
      title: "Confirmer l'envoi ?",
      description: `Vous allez envoyer ce message à ${targetRecipientsCount.value} étudiant(s) du groupe "${targetGroup.value === 'ALL' ? 'Tous' : targetGroup.value}". Cette action est irréversible.`,
      confirmText: "Envoyer le mail"
    }))
  ) {
    return
  }

  sending.value = true
  try {
    const res = await api.post<any>(`/api/teacher/courses/${route.params.id}/send-email`, {
      subject: emailSubject.value,
      content: emailContent.value,
      session_id: selectedSessionId.value === 'none' ? null : Number(selectedSessionId.value),
      targetGroup: targetGroup.value,
      variables: manualVariables.value
    })

    showToast(`Transmission réussie ! ${res.recipientsCount} mails envoyés.`, 'success')
    
    // Reset form & reload history
    selectedTemplateId.value = 'none'
    emailSubject.value = ''
    emailContent.value = ''
    manualVariables.value = {}
    
    await loadData()
    activeTab.value = 'history' // Switch to history to see the result
  } catch (e: any) {
    console.error(e)
    showToast(e?.message || "Erreur lors de l'envoi de la campagne.", 'error')
  } finally {
    sending.value = false
  }
}

// CRUD Template: Save (create or update)
async function saveTemplate() {
  if (!templateForm.title || !templateForm.subject || !templateForm.content) return
  
  const payload = {
    title: templateForm.title,
    subject: templateForm.subject,
    content: templateForm.content,
    course: `/api/courses/${route.params.id}`,
    session: templateForm.session_id === 'none' ? null : `/api/sessions/${templateForm.session_id}`,
    defaultTarget: templateForm.defaultTarget
  }

  try {
    if (isEditingTemplate.value && editingTemplateId.value) {
      await api.patch(`/api/email_templates/${editingTemplateId.value}`, payload)
      showToast('Modèle mis à jour avec succès.')
    } else {
      await api.post('/api/email_templates', payload)
      showToast('Modèle créé avec succès.')
    }

    cancelEditTemplate()
    await loadData()
  } catch (e) {
    console.error(e)
    showToast('Erreur lors de la sauvegarde du modèle.', 'error')
  }
}

// CRUD Template: Edit selection
function editTemplate(t: EmailTemplate) {
  isEditingTemplate.value = true
  editingTemplateId.value = t.id
  templateForm.title = t.title
  templateForm.subject = t.subject
  templateForm.content = t.content
  templateForm.session_id = t.session ? String(getRelationId(t.session) || 'none') : 'none'
  templateForm.defaultTarget = t.defaultTarget || 'ALL'
}

function cancelEditTemplate() {
  isEditingTemplate.value = false
  editingTemplateId.value = null
  templateForm.title = ''
  templateForm.subject = ''
  templateForm.content = ''
  templateForm.session_id = 'none'
  templateForm.defaultTarget = 'ALL'
}

// CRUD Template: Delete selection
async function deleteTemplate(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer ce modèle ?",
      description: "Ce modèle de mail ne sera plus disponible pour préparer des transmissions.",
      confirmText: "Supprimer"
    }))
  ) {
    return
  }

  try {
    await api.delete(`/api/email_templates/${id}`)
    showToast('Modèle supprimé.')
    if (editingTemplateId.value === id) {
      cancelEditTemplate()
    }
    await loadData()
  } catch (e) {
    console.error(e)
    showToast('Erreur lors de la suppression.', 'error')
  }
}

// History Detail helper
function viewHistoryDetail(item: SentEmail) {
  selectedHistoryItem.value = item
  isHistoryDetailOpen.value = true
}

// Helpers
function getRelationId(val: any): string | null {
  if (!val) return null
  if (typeof val === 'number') return String(val)
  if (typeof val === 'string') {
    return val.split('/').pop() || null
  }
  if (typeof val === 'object') {
    if (val.id !== undefined && val.id !== null) {
      return String(val.id)
    }
    if (val['@id']) {
      return val['@id'].split('/').pop() || null
    }
  }
  return null
}

function getSessionTitle(sessionVal: any): string {
  if (!sessionVal) return 'Général'
  const sId = getRelationId(sessionVal)
  if (!sId) return 'Séance'
  
  const found = course.value?.sessions?.find((s: any) => String(s.id) === String(sId))
  return found ? found.title : 'Séance'
}

function formatDate(dateStr: string): string {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function compileHistoryText(text: string, item: SentEmail): string {
  if (!text) return ''
  const replacements: Record<string, string> = {
    '{nom_etudiant}': '[Nom de l\'Étudiant]',
    '{titre_cours}': course.value?.title || '',
    '{titre_seance}': getSessionTitle(item.session)
  }
  
  if (item.variables) {
    for (const [key, val] of Object.entries(item.variables)) {
      replacements[`{${key}}`] = String(val)
    }
  }
  
  return text.replace(/\{([a-zA-Z0-9_]+)\}/g, (match, key) => {
    return replacements[match] !== undefined ? replacements[match] : match
  })
}
</script>
