<template>
    <AppLayout>
        <h2 class="text-2xl font-bold">Back-office (édition)</h2>
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 bg-white rounded shadow flex items-center gap-3">
                <div class="text-3xl font-bold text-indigo-600">{{ totalCourses }}</div>
                <div>
                    <div class="text-sm text-gray-500">Cours</div>
                    <div class="text-sm text-gray-600">Gestion des formations</div>
                </div>
            </div>
            <div class="p-4 bg-white rounded shadow flex items-center gap-3">
                <div class="text-3xl font-bold text-green-600">{{ totalStudents }}</div>
                <div>
                    <div class="text-sm text-gray-500">Étudiants</div>
                    <div class="text-sm text-gray-600">Avec progression</div>
                </div>
            </div>
            <div class="p-4 bg-white rounded shadow flex items-center gap-3">
                <div class="text-3xl font-bold text-yellow-500">{{ totalPoints }}</div>
                <div>
                    <div class="text-sm text-gray-500">Points</div>
                    <div class="text-sm text-gray-600">Total distribué</div>
                </div>
            </div>
        </div>
        <div class="mt-4 grid md:grid-cols-3 gap-4">
            <aside>
                <div class="flex items-center justify-between">
                    <h4 class="font-semibold">Cours</h4>
                    <button @click="createCourse"
                        class="text-sm px-2 py-1 bg-green-600 text-white rounded">Nouveau</button>
                </div>
                <ul class="space-y-2 mt-2">
                    <li v-for="c in courses" :key="c.id">
                        <div class="flex items-center gap-2">
                            <button @click="selectCourse(c)" class="w-full text-left p-2 rounded hover:bg-gray-100">{{
                                c.title }}</button>
                            <button @click="removeCourse(c.id)"
                                class="text-sm px-2 py-1 bg-red-100 text-red-700 rounded">Suppr</button>
                        </div>
                    </li>
                </ul>
            </aside>
            <section class="md:col-span-2 bg-white p-4 rounded shadow">
                <div v-if="selected">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold">Edition — {{ selected.title }}</h3>
                        <div class="flex gap-2">
                            <button @click="saveCourse"
                                class="px-3 py-2 bg-indigo-600 text-white rounded">Sauvegarder</button>
                            <button @click="duplicateCourse" class="px-3 py-2 bg-gray-100 rounded">Dupliquer</button>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <input v-model="editTitle" class="border p-2 rounded" placeholder="Titre du cours" />
                        <input v-model="editTheme" class="border p-2 rounded" placeholder="Thème" />
                        <input v-model="editLevel" class="border p-2 rounded" placeholder="Niveau" />
                        <input v-model="editAccent" class="border p-2 rounded" placeholder="Couleur accent (#hex)" />
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm">Séances</label>
                        <div class="flex gap-2 mt-2 mb-3">
                            <select v-model="selectedSessionId" class="border p-2 rounded flex-1">
                                <option v-for="s in selected.sessions" :key="s.id" :value="s.id">{{ s.title }}</option>
                            </select>
                            <button @click="createSession" class="px-3 py-2 bg-green-600 text-white rounded">Nouvelle
                                séance</button>
                            <button v-if="selectedSessionId" @click="removeSession"
                                class="px-3 py-2 bg-red-100 text-red-700 rounded">Suppr séance</button>
                        </div>
                        <div v-if="selectedSessionId" class="bg-gray-50 p-3 rounded">
                            <h5 class="font-medium mb-2">Options de rendu</h5>
                            <div class="flex items-center gap-3 mb-2">
                                <label class="inline-flex items-center gap-2"><input type="checkbox"
                                        v-model="editRender_allowUpload" /> Autoriser les dépôts</label>
                            </div>
                            <div class="mb-2">
                                <div class="text-sm mb-1">Types autorisés</div>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        class="inline-flex items-center gap-2 px-2 py-1 border rounded cursor-pointer"
                                        :class="editRender_allowedTypes.includes('file') ? 'bg-white' : 'bg-gray-100'">
                                        <input type="checkbox" :checked="editRender_allowedTypes.includes('file')"
                                            @change.prevent="toggleRenderType('file')" /> Fichier
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2 px-2 py-1 border rounded cursor-pointer"
                                        :class="editRender_allowedTypes.includes('image') ? 'bg-white' : 'bg-gray-100'">
                                        <input type="checkbox" :checked="editRender_allowedTypes.includes('image')"
                                            @change.prevent="toggleRenderType('image')" /> Image
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2 px-2 py-1 border rounded cursor-pointer"
                                        :class="editRender_allowedTypes.includes('code') ? 'bg-white' : 'bg-gray-100'">
                                        <input type="checkbox" :checked="editRender_allowedTypes.includes('code')"
                                            @change.prevent="toggleRenderType('code')" /> Code
                                    </label>
                                    <label
                                        class="inline-flex items-center gap-2 px-2 py-1 border rounded cursor-pointer"
                                        :class="editRender_allowedTypes.includes('link') ? 'bg-white' : 'bg-gray-100'">
                                        <input type="checkbox" :checked="editRender_allowedTypes.includes('link')"
                                            @change.prevent="toggleRenderType('link')" /> Lien
                                    </label>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="text-sm">Nombre max de fichiers</label>
                                <input type="number" v-model.number="editRender_maxFiles" min="1"
                                    class="border p-1 rounded w-24" />
                            </div>
                            <div class="flex gap-2 mt-2">
                                <button @click="saveSessionRenderConfig"
                                    class="px-3 py-2 bg-indigo-600 text-white rounded">Sauvegarder options</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm">Chapitre à éditer</label>
                        <div class="flex gap-2 mt-2 mb-3">
                            <select v-model="selectedChapterId" class="border p-2 rounded flex-1">
                                <option v-for="ch in currentSession.chapters" :key="ch.id" :value="ch.id">{{ ch.title }}
                                </option>
                            </select>
                            <button @click="createChapter"
                                class="px-3 py-2 bg-green-600 text-white rounded">Nouveau</button>
                            <button v-if="selectedChapterId" @click="removeChapter"
                                class="px-3 py-2 bg-red-100 text-red-700 rounded">Suppr</button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <MarkdownEditor v-model:modelValue="currentContent" />
                        <div class="mt-3 flex gap-2">
                            <button @click="saveChapter" class="px-3 py-2 bg-green-600 text-white rounded">Enregistrer
                                chapitre</button>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <p>Sélectionnez un cours pour commencer l'édition.</p>
                </div>
            </section>
            <!-- Student tracking panel -->
            <section class="md:col-span-3 bg-white p-4 rounded shadow mt-4">
                <h3 class="font-semibold">Suivi des étudiants — {{ selected?.title || '' }}</h3>
                <div v-if="selected">
                    <div class="mt-3 flex items-center gap-3">
                        <label class="text-sm">Étudiant</label>
                        <select v-model="selectedStudent" class="border p-2 rounded">
                            <option v-for="s in students" :key="s" :value="s">{{ s }}</option>
                        </select>
                    </div>

                    <div v-if="selectedStudent" class="mt-4">
                        <h4 class="font-medium">Progression</h4>
                        <ul class="mt-2 space-y-2">
                            <li v-for="s in selected.sessions" :key="s.id"
                                class="p-3 border rounded flex items-start justify-between">
                                <div>
                                    <div class="font-medium">{{ s.title }}</div>
                                    <div class="text-sm text-gray-500">Statut: {{ progressFor(selectedStudent,
                                        selected.id)[s.id]?.done ? 'Validée' : (progressFor(selectedStudent,
                                            selected.id)[s.id]?.inProgress ? 'En cours' : 'À faire') }}</div>
                                    <div class="text-sm mt-2">Rendus:</div>
                                    <div class="mt-1 flex gap-2 flex-wrap">
                                        <div v-for="file in uploadsForSession(selected.id, s.id)[selectedStudent] || []"
                                            :key="file.name || file.data" @click="previewFile(file)"
                                            class="p-2 border rounded cursor-pointer hover:bg-gray-50">
                                            <div class="text-xs font-medium">{{ file.name || 'fichier' }}</div>
                                            <div v-if="file.type && file.type.startsWith('image/')">
                                                <img :src="file.data" class="w-32 h-20 object-cover rounded mt-1" />
                                            </div>
                                            <div v-else class="text-xs text-gray-500 mt-1">Type: {{ file.type ||
                                                'unknown' }}</div>
                                        </div>
                                        <div v-if="!(uploadsForSession(selected.id, s.id)[selectedStudent] || []).length"
                                            class="text-sm text-gray-500">Aucun rendu</div>
                                    </div>
                                </div>
                                <div class="w-64">
                                    <div class="text-sm font-medium">Évaluation</div>
                                    <div class="mt-2">
                                        <input v-model.number="evals[s.id].score" type="number" min="0" max="100"
                                            class="border p-1 rounded w-full" placeholder="Score" />
                                        <textarea v-model="evals[s.id].comment" class="border p-2 rounded w-full mt-2"
                                            rows="3" placeholder="Commentaire"></textarea>
                                        <div class="flex gap-2 mt-2">
                                            <button @click="saveEval(s.id)"
                                                class="px-3 py-2 bg-indigo-600 text-white rounded">Sauvegarder</button>
                                            <div v-if="savedMsg[s.id]" class="text-sm text-green-600">{{ savedMsg[s.id]
                                                }}</div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-else class="text-sm text-gray-500 mt-2">Sélectionnez un cours pour voir le suivi des étudiants.
                </div>
            </section>
        </div>
        <!-- Preview modal -->
        <div v-if="preview.visible" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closePreview"></div>
            <div class="bg-white rounded shadow-lg p-4 z-10 max-w-3xl w-full mx-4">
                <div class="flex justify-between items-center mb-2">
                    <div class="font-semibold">{{ preview.file?.name || 'Preview' }}</div>
                    <button @click="closePreview" class="px-2 py-1 rounded bg-gray-100">Fermer</button>
                </div>
                <div v-if="preview.file?.type && preview.file.type.startsWith('image/')">
                    <img :src="preview.file.data" class="w-full h-auto rounded" />
                </div>
                <pre v-else class="max-h-80 overflow-auto bg-gray-50 p-3 rounded text-sm">{{ preview.file?.data }}</pre>
            </div>
        </div>

    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '../components/AppLayout.vue'
import MarkdownEditor from '../components/MarkdownEditor.vue'
import { useCoursesStore } from '../stores/courses'
import { showToast } from '../composables/useToast'

const store = useCoursesStore()
const courses = store.courses
const selected = ref<any | null>(null)
const selectedSessionId = ref('')
const selectedChapterId = ref('')
const currentContent = ref('')

// editable fields for course
const editTitle = ref('')
const editTheme = ref('')
const editLevel = ref('')
const editAccent = ref('')

async function createCourse() {
    const c = await store.createCourse({ title: 'Nouveau cours', theme: 'Général', accentColor: '#7c3aed', level: 'Débutant' })
    selectCourse(c)
}

async function removeCourse(id: string | number) {
    if (!confirm('Supprimer ce cours ?')) return
    await store.deleteCourse(id)
    if (selected.value?.id === id) selected.value = null
}

async function saveCourse() {
    if (!selected.value) return
    await store.updateCourse(selected.value.id, { title: editTitle.value, theme: editTheme.value, level: editLevel.value, accentColor: editAccent.value })
    // refresh selected reference
    selected.value = store.getCourse(selected.value.id)
    showToast('Cours sauvegardé')
}

async function duplicateCourse() {
    if (!selected.value) return
    const src = selected.value
    const newc = await store.createCourse({
        title: `${src.title} (copie)`, theme: src.theme, context: src.context,
        accentColor: src.accentColor, level: src.level, scenario: src.scenario,
    })
    for (const s of src.sessions) {
        const ns = await store.addSession(newc.id, { title: s.title, pitch: s.pitch, renderConfig: s.renderConfig })
        for (const ch of s.chapters) {
            await store.addChapter(newc.id, ns.id, { title: ch.title, content: ch.content })
        }
    }
    selectCourse(store.getCourse(newc.id))
}

async function createSession() {
    if (!selected.value) return
    const s = await store.addSession(selected.value.id, { title: 'Nouvelle séance' })
    selectedSessionId.value = s.id
    // refresh selected reference and load render config
    selected.value = store.getCourse(selected.value.id)
    loadCurrent()
}

async function removeSession() {
    if (!selected.value || !selectedSessionId.value) return
    if (!confirm('Supprimer cette séance ?')) return
    await store.deleteSession(selected.value.id, selectedSessionId.value)
    selectedSessionId.value = selected.value.sessions[0]?.id || ''
}

async function createChapter() {
    if (!selected.value || !selectedSessionId.value) return
    const ch = await store.addChapter(selected.value.id, selectedSessionId.value, { title: 'Nouveau chapitre', content: '# Titre' })
    selectedChapterId.value = ch.id
    loadCurrent()
}

async function removeChapter() {
    if (!selected.value || !selectedSessionId.value || !selectedChapterId.value) return
    if (!confirm('Supprimer ce chapitre ?')) return
    await store.deleteChapter(selected.value.id, selectedSessionId.value, selectedChapterId.value)
    selectedChapterId.value = currentSession.value.chapters[0]?.id || ''
    loadCurrent()
}

async function saveChapter() {
    if (!selected.value || !selectedSessionId.value || !selectedChapterId.value) return
    await store.updateChapter(selected.value.id, selectedSessionId.value, selectedChapterId.value, { content: currentContent.value })
    showToast('Chapitre sauvegardé')
}

function selectCourse(c: any) {
    selected.value = c
    selectedSessionId.value = c.sessions[0]?.id
    selectedChapterId.value = c.sessions[0]?.chapters[0]?.id
    loadCurrent()
}

const currentSession = computed(() => selected.value?.sessions.find((s: any) => s.id === selectedSessionId.value) || { chapters: [] })

// render config editable fields
const editRender_allowUpload = ref(true)
const editRender_allowedTypes = ref<Array<string>>([])
const editRender_maxFiles = ref<number | null>(1)

function toggleRenderType(type: string) {
    const idx = editRender_allowedTypes.value.indexOf(type)
    if (idx === -1) editRender_allowedTypes.value.push(type)
    else editRender_allowedTypes.value.splice(idx, 1)
}

function loadRenderConfig() {
    const rc = currentSession.value.renderConfig || { allowUpload: false, allowedTypes: [], maxFiles: 1 }
    editRender_allowUpload.value = !!rc.allowUpload
    editRender_allowedTypes.value = Array.isArray(rc.allowedTypes) ? [...rc.allowedTypes] : []
    editRender_maxFiles.value = rc.maxFiles ?? 1
}

async function saveSessionRenderConfig() {
    if (!selected.value || !selectedSessionId.value) return
    const cfg = { allowUpload: editRender_allowUpload.value, allowedTypes: editRender_allowedTypes.value, maxFiles: editRender_maxFiles.value }
    await store.updateSession(selected.value.id, selectedSessionId.value, { renderConfig: cfg })
    // refresh selected reference
    selected.value = store.getCourse(selected.value.id)
    showToast('Options de rendu sauvegardées')
}

function loadCurrent() {
    const ch = currentSession.value.chapters.find((ch: any) => ch.id === selectedChapterId.value)
    // prefer saved chapter content from store (persisted courses), else local edit key
    currentContent.value = ch?.content || localStorage.getItem(`pf:edit:${selected.value.id}:${selectedSessionId.value}:${selectedChapterId.value}`) || ''
    // populate edit fields
    editTitle.value = selected.value.title
    editTheme.value = selected.value.theme
    editLevel.value = selected.value.level
    editAccent.value = selected.value.accentColor
    // load render config for the selected session
    loadRenderConfig()
}

function save() {
    const key = `pf:edit:${selected.value.id}:${selectedSessionId.value}:${selectedChapterId.value}`
    localStorage.setItem(key, currentContent.value)
    showToast('Enregistré localement (prototype)')
}

// watch selections
import { watch } from 'vue'
watch([selectedSessionId, selectedChapterId], () => { if (selected.value) loadCurrent() })

// student tracking state
const students = ref<string[]>([])
const selectedStudent = ref<string | null>(null)
const evals = ref<Record<string, { score: number | null; comment: string }>>({})
const savedMsg = ref<Record<string, string>>({})

function loadStudentsForCourse() {
    if (!selected.value) { students.value = []; selectedStudent.value = null; return }
    students.value = store.getStudentsForCourse(selected.value.id)
    if (students.value.length > 0) selectedStudent.value = students.value[0]
    else selectedStudent.value = null
    loadEvalsForSelectedStudent()
}

watch(selected, () => loadStudentsForCourse())

function uploadsForSession(courseId: string, sessionId: string) {
    return store.getUploadsForSession(courseId, sessionId)
}

function progressFor(studentId: string, courseId: string) {
    return store.getProgress(studentId, courseId)
}

function loadEvalsForSelectedStudent() {
    if (!selected.value || !selectedStudent.value) return
    const data = store.getEvaluations(selectedStudent.value, selected.value.id)
    // init evals per session
    const obj: Record<string, { score: number | null; comment: string }> = {}
    for (const s of selected.value.sessions) {
        const e = data[s.id] || {}
        obj[s.id] = { score: e.score ?? null, comment: e.comment ?? '' }
    }
    evals.value = obj
}

watch(selectedStudent, () => loadEvalsForSelectedStudent())

function saveEval(sessionId: string) {
    if (!selected.value || !selectedStudent.value) return
    const e = evals.value[sessionId] || { score: null, comment: '' }
    store.saveEvaluation(selectedStudent.value, selected.value.id, sessionId, { score: e.score, comment: e.comment })
    savedMsg.value[sessionId] = 'Sauvegardé'
    setTimeout(() => { savedMsg.value[sessionId] = '' }, 2000)
}

// using global showToast composable

// preview modal for uploads
const preview = ref<{ visible: boolean; file: any }>({ visible: false, file: null })
function previewFile(file: any) {
    preview.value = { visible: true, file }
}
function closePreview() { preview.value = { visible: false, file: null } }

// dashboard metrics
const totalCourses = computed(() => (store.courses || []).length)
const totalStudents = computed(() => Object.keys(store.progress || {}).length)
const totalPoints = computed(() => {
    const pts = store.points || {}
    return Object.values(pts).reduce((a: any, b: any) => a + (b || 0), 0)
})
</script>

<style scoped>
/* small tweaks for modal and toast */
.max-h-80 {
    max-height: 20rem
}

.cursor-pointer {
    cursor: pointer
}
</style>
