<template>
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Back-office</h1>
                <p class="text-sm text-muted-foreground">Création et édition des cours, séances et chapitres.</p>
            </div>
            <RouterLink to="/stats/teacher"><Button variant="outline" size="sm"><IconChartBar class="size-4" /> Statistiques</Button></RouterLink>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Courses list -->
            <Card class="lg:col-span-1">
                <CardHeader class="flex-row items-center justify-between space-y-0">
                    <CardTitle class="text-base">Cours</CardTitle>
                    <Button size="sm" @click="createCourse"><IconPlus class="size-4" /> Nouveau</Button>
                </CardHeader>
                <CardContent class="space-y-1">
                    <div v-for="c in courses" :key="c.id"
                        class="flex items-center gap-1 rounded-md transition"
                        :class="selected?.id === c.id ? 'bg-accent' : 'hover:bg-muted'">
                        <button class="flex-1 truncate px-3 py-2 text-left text-sm" @click="selectCourse(c)">{{ c.title }}</button>
                        <Button variant="ghost" size="icon-sm" class="text-destructive" @click="removeCourse(c.id)"><IconTrash class="size-4" /></Button>
                    </div>
                    <p v-if="!courses.length" class="px-3 py-6 text-center text-sm text-muted-foreground">Aucun cours. Créez-en un.</p>
                </CardContent>
            </Card>

            <!-- Editor -->
            <div class="space-y-6 lg:col-span-2">
                <Card v-if="!selected" class="grid place-items-center py-16 text-center text-muted-foreground">
                    Sélectionnez un cours pour commencer l'édition.
                </Card>

                <template v-else>
                    <!-- Course fields -->
                    <Card>
                        <CardHeader class="flex-row items-center justify-between space-y-0">
                            <CardTitle class="text-base">Cours — {{ selected.title }}</CardTitle>
                            <div class="flex gap-2">
                                <Button variant="outline" size="sm" @click="duplicateCourse"><IconCopy class="size-4" /> Dupliquer</Button>
                                <Button size="sm" @click="saveCourse">Enregistrer</Button>
                            </div>
                        </CardHeader>
                        <CardContent class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5"><Label>Titre</Label><Input v-model="editTitle" /></div>
                            <div class="space-y-1.5"><Label>Thème</Label><Input v-model="editTheme" /></div>
                            <div class="space-y-1.5"><Label>Niveau</Label><Input v-model="editLevel" placeholder="Débutant / Intermédiaire / Avancé" /></div>
                            <div class="space-y-1.5">
                                <Label>Couleur d'accent</Label>
                                <div class="flex items-center gap-2">
                                    <input type="color" v-model="editAccent" class="size-9 cursor-pointer rounded border bg-transparent" />
                                    <Input v-model="editAccent" class="flex-1" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Sessions -->
                    <Card>
                        <CardHeader><CardTitle class="text-base">Séances</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Select v-model="selectedSessionId">
                                    <SelectTrigger class="min-w-56 flex-1"><SelectValue placeholder="Choisir une séance" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in selected.sessions" :key="s.id" :value="String(s.id)">{{ s.title }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline" size="sm" @click="createSession"><IconPlus class="size-4" /> Nouvelle séance</Button>
                                <Button v-if="selectedSessionId" variant="ghost" size="sm" class="text-destructive" @click="removeSession"><IconTrash class="size-4" /> Suppr.</Button>
                            </div>

                            <div v-if="currentSession.id" class="space-y-3 rounded-lg border bg-muted/40 p-4">
                                <div class="space-y-1.5"><Label>Titre de la séance</Label><Input v-model="editSessionTitle" /></div>
                                <div class="flex items-center justify-between">
                                    <Label for="allowUpload">Autoriser les dépôts</Label>
                                    <Switch id="allowUpload" v-model="editRender_allowUpload" />
                                </div>
                                <div v-if="editRender_allowUpload" class="space-y-2">
                                    <Label>Types autorisés</Label>
                                    <div class="flex flex-wrap gap-3">
                                        <label v-for="t in allTypes" :key="t.value" class="flex items-center gap-2 text-sm">
                                            <Checkbox :model-value="editRender_allowedTypes.includes(t.value)" @update:model-value="() => toggleType(t.value)" />
                                            {{ t.label }}
                                        </label>
                                    </div>
                                    <div class="space-y-1.5">
                                        <Label>Nombre max de fichiers</Label>
                                        <Input type="number" min="1" v-model.number="editRender_maxFiles" class="w-28" />
                                    </div>
                                </div>
                                <Button size="sm" @click="saveSession">Enregistrer la séance</Button>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Chapters -->
                    <Card v-if="selectedSessionId">
                        <CardHeader><CardTitle class="text-base">Chapitres</CardTitle></CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <Select v-model="selectedChapterId">
                                    <SelectTrigger class="min-w-56 flex-1"><SelectValue placeholder="Choisir un chapitre" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="ch in currentSession.chapters" :key="ch.id" :value="String(ch.id)">{{ ch.title }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button variant="outline" size="sm" @click="createChapter"><IconPlus class="size-4" /> Nouveau</Button>
                                <Button v-if="selectedChapterId" variant="ghost" size="sm" class="text-destructive" @click="removeChapter"><IconTrash class="size-4" /> Suppr.</Button>
                            </div>

                            <div v-if="selectedChapterId" class="space-y-3">
                                <div class="space-y-1.5"><Label>Titre du chapitre</Label><Input v-model="editChapterTitle" /></div>
                                <MarkdownEditor v-model="currentContent" />
                                <Button size="sm" @click="saveChapter">Enregistrer le chapitre</Button>
                            </div>
                        </CardContent>
                    </Card>
                </template>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { IconPlus, IconTrash, IconCopy, IconChartBar } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import MarkdownEditor from '@/components/MarkdownEditor.vue'
import { useCoursesStore } from '@/stores/courses'
import { showToast } from '@/composables/useToast'
import { confirmDialog } from '@/composables/useConfirm'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Checkbox } from '@/components/ui/checkbox'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const store = useCoursesStore()
const courses = computed(() => store.courses)
const selected = ref<any | null>(null)
const selectedSessionId = ref('')
const selectedChapterId = ref('')
const currentContent = ref('')

const editTitle = ref('')
const editTheme = ref('')
const editLevel = ref('')
const editAccent = ref('#7c3aed')
const editSessionTitle = ref('')
const editChapterTitle = ref('')

const allTypes = [
    { value: 'file', label: 'Fichier' },
    { value: 'image', label: 'Image' },
    { value: 'code', label: 'Code' },
    { value: 'link', label: 'Lien' },
]
const editRender_allowUpload = ref(true)
const editRender_allowedTypes = ref<string[]>([])
const editRender_maxFiles = ref(1)

const currentSession = computed(() => selected.value?.sessions.find((s: any) => String(s.id) === String(selectedSessionId.value)) || { chapters: [] })

async function createCourse() {
    const c = await store.createCourse({ title: 'Nouveau cours', theme: 'Général', accentColor: '#7c3aed', level: 'Débutant' })
    selectCourse(c)
    showToast('Cours créé')
}
async function removeCourse(id: string | number) {
    if (!(await confirmDialog({ title: 'Supprimer ce cours ?', description: 'Cette action est irréversible et supprimera aussi ses séances et chapitres.', confirmText: 'Supprimer' }))) return
    await store.deleteCourse(id)
    if (selected.value?.id === id) selected.value = null
    showToast('Cours supprimé')
}
async function saveCourse() {
    if (!selected.value) return
    await store.updateCourse(selected.value.id, { title: editTitle.value, theme: editTheme.value, level: editLevel.value, accentColor: editAccent.value })
    selected.value = store.getCourse(selected.value.id)
    showToast('Cours enregistré')
}
async function duplicateCourse() {
    if (!selected.value) return
    const src = selected.value
    const newc = await store.createCourse({ title: `${src.title} (copie)`, theme: src.theme, context: src.context, accentColor: src.accentColor, level: src.level, scenario: src.scenario })
    for (const s of src.sessions) {
        const ns = await store.addSession(newc.id, { title: s.title, pitch: s.pitch, renderConfig: s.renderConfig })
        for (const ch of s.chapters) await store.addChapter(newc.id, ns.id, { title: ch.title, content: ch.content })
    }
    selectCourse(store.getCourse(newc.id))
    showToast('Cours dupliqué')
}

function selectCourse(c: any) {
    selected.value = c
    selectedSessionId.value = c.sessions[0] ? String(c.sessions[0].id) : ''
    editTitle.value = c.title; editTheme.value = c.theme || ''; editLevel.value = c.level || ''; editAccent.value = c.accentColor || '#7c3aed'
    syncSession()
}

async function createSession() {
    if (!selected.value) return
    const s = await store.addSession(selected.value.id, { title: 'Nouvelle séance' })
    selected.value = store.getCourse(selected.value.id)
    selectedSessionId.value = String(s.id)
    syncSession()
    showToast('Séance créée')
}
async function removeSession() {
    if (!selected.value || !selectedSessionId.value) return
    if (!(await confirmDialog({ title: 'Supprimer cette séance ?', description: 'Les chapitres de cette séance seront également supprimés.', confirmText: 'Supprimer' }))) return
    await store.deleteSession(selected.value.id, selectedSessionId.value)
    selected.value = store.getCourse(selected.value.id)
    selectedSessionId.value = selected.value.sessions[0] ? String(selected.value.sessions[0].id) : ''
    syncSession()
    showToast('Séance supprimée')
}
async function saveSession() {
    if (!selected.value || !selectedSessionId.value) return
    await store.updateSession(selected.value.id, selectedSessionId.value, {
        title: editSessionTitle.value,
        renderConfig: { allowUpload: editRender_allowUpload.value, allowedTypes: editRender_allowedTypes.value, maxFiles: editRender_maxFiles.value },
    })
    selected.value = store.getCourse(selected.value.id)
    showToast('Séance enregistrée')
}
function toggleType(t: string) {
    const i = editRender_allowedTypes.value.indexOf(t)
    if (i === -1) editRender_allowedTypes.value.push(t)
    else editRender_allowedTypes.value.splice(i, 1)
}

async function createChapter() {
    if (!selected.value || !selectedSessionId.value) return
    const ch = await store.addChapter(selected.value.id, selectedSessionId.value, { title: 'Nouveau chapitre', content: '# Titre' })
    selected.value = store.getCourse(selected.value.id)
    selectedChapterId.value = String(ch.id)
    syncChapter()
    showToast('Chapitre créé')
}
async function removeChapter() {
    if (!selected.value || !selectedSessionId.value || !selectedChapterId.value) return
    if (!(await confirmDialog({ title: 'Supprimer ce chapitre ?', confirmText: 'Supprimer' }))) return
    await store.deleteChapter(selected.value.id, selectedSessionId.value, selectedChapterId.value)
    selected.value = store.getCourse(selected.value.id)
    selectedChapterId.value = currentSession.value.chapters[0] ? String(currentSession.value.chapters[0].id) : ''
    syncChapter()
    showToast('Chapitre supprimé')
}
async function saveChapter() {
    if (!selected.value || !selectedSessionId.value || !selectedChapterId.value) return
    await store.updateChapter(selected.value.id, selectedSessionId.value, selectedChapterId.value, { title: editChapterTitle.value, content: currentContent.value })
    selected.value = store.getCourse(selected.value.id)
    showToast('Chapitre enregistré')
}

function syncSession() {
    const s = currentSession.value
    editSessionTitle.value = s.title || ''
    const rc = s.renderConfig || { allowUpload: true, allowedTypes: ['file', 'image'], maxFiles: 1 }
    editRender_allowUpload.value = !!rc.allowUpload
    editRender_allowedTypes.value = Array.isArray(rc.allowedTypes) ? [...rc.allowedTypes] : []
    editRender_maxFiles.value = rc.maxFiles ?? 1
    selectedChapterId.value = s.chapters?.[0] ? String(s.chapters[0].id) : ''
    syncChapter()
}
function syncChapter() {
    const ch = currentSession.value.chapters?.find((c: any) => String(c.id) === String(selectedChapterId.value))
    editChapterTitle.value = ch?.title || ''
    currentContent.value = ch?.content || ''
}

watch(selectedSessionId, syncSession)
watch(selectedChapterId, syncChapter)
</script>
