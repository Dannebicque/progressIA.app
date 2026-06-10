<template>
    <AppLayout>
        <div v-if="course && session" class="grid lg:grid-cols-3 gap-6">
            <main class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl p-6 shadow-lg"
                    :style="{ borderTop: '6px solid ' + (course.accentColor || '#7c3aed') }">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-bold">{{ session.title }}</h2>
                            <div class="text-sm text-gray-500">{{ course.title }}</div>
                            <div class="text-sm text-gray-700 mt-2">{{ session.description || course.scenario }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-sm text-gray-600 text-center">
                                <div>Progression séance</div>
                                <div class="w-40 mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div :style="{ width: sessionPct + '%', background: course.accentColor }"
                                            class="h-3 rounded-full"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden lg:flex gap-2">
                                <router-link v-if="prevSession" :to="`/course/${course.id}/session/${prevSession.id}`"
                                    class="px-3 py-2 rounded-full text-sm"
                                    :style="{ border: '1px solid ' + course.accentColor, color: course.accentColor }">←
                                    Précédent</router-link>
                                <router-link v-if="nextSession" :to="`/course/${course.id}/session/${nextSession.id}`"
                                    class="px-4 py-2 rounded-full text-white text-sm"
                                    :style="{ background: `linear-gradient(90deg, ${course.accentColor} 0%, ${course.accentColor}bb 100%)` }">Suivant
                                    →</router-link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="session.chapters.length > 1" class="bg-white rounded p-4 shadow">
                    <h4 class="font-medium mb-2">Plan de la séance</h4>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="ch in session.chapters" :key="ch.id" @click="scrollTo(ch.id)"
                            :style="{ borderColor: course.accentColor, color: course.accentColor }"
                            class="text-sm border px-3 py-1 rounded">{{ ch.title }}</button>
                    </div>
                </div>

                <div class="space-y-6">
                    <article v-for="ch in session.chapters" :key="ch.id" :id="ch.id"
                        class="bg-white p-6 rounded shadow prose max-w-none">
                        <div class="flex items-center justify-between">
                            <h4 class="font-semibold text-lg">{{ ch.title }}</h4>
                            <a @click.prevent="scrollToTop" href="#" class="text-sm text-gray-500">Haut ↑</a>
                        </div>
                        <MarkdownViewer :source="ch.content" />
                    </article>
                </div>

                <div v-if="session.renderConfig?.allowUpload" class="bg-white p-4 rounded shadow">
                    <div>
                        <h4 class="font-semibold">Rendu</h4>
                        <p class="text-sm text-gray-600">Déposez votre fichier ici.</p>
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <label
                            class="inline-flex items-center px-4 py-2 text-white rounded-full cursor-pointer relative overflow-hidden shadow-sm"
                            :style="{ background: `linear-gradient(90deg, ${course.accentColor} 0%, ${course.accentColor}bb 100%)` }">
                            <input :accept="uploadAccept" :multiple="uploadMultiple" type="file" class="hidden"
                                @change="onFile" />
                            <span>Déposer un fichier</span>
                        </label>
                        <button @click="completeSession" class="px-4 py-2 text-white rounded-full"
                            :style="{ background: '#10b981' }">Marquer comme terminé</button>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        <div v-if="session.renderConfig && session.renderConfig.allowedTypes">
                            Types autorisés:
                            <span v-for="t in session.renderConfig.allowedTypes" :key="t"
                                class="inline-block px-2 py-0.5 mr-2 bg-gray-100 rounded text-xs">{{ t }}</span>
                        </div>
                    </div>
                </div>
            </main>

            <aside class="lg:col-span-1">
                <div class="bg-white rounded-xl p-4 shadow sticky top-24">
                    <div class="flex items-center justify-between">
                        <h4 class="font-semibold">Contenu du cours</h4>
                        <button class="lg:hidden text-sm px-2 py-1 rounded border" @click="tocOpen = !tocOpen"
                            :style="{ borderColor: course.accentColor, color: course.accentColor }">{{ tocOpen ?
                                'Masquer' : 'Sommaire' }}</button>
                    </div>
                    <div class="mt-3 space-y-2">
                        <div v-show="tocOpen" class="mb-3">
                            <h5 class="text-sm font-bold mb-2">Sommaire</h5>
                            <ul class="text-sm space-y-1">
                                <li v-for="ch in session.chapters" :key="ch.id">
                                    <a href="#" @click.prevent="scrollTo(ch.id)"
                                        :class="activeChapter === ch.id ? 'font-semibold' : ''"
                                        :style="activeChapter === ch.id ? { color: course.accentColor } : {}">{{
                                            ch.title }}</a>
                                </li>
                            </ul>
                        </div>



                        <div class="mt-2">
                            <h5 class="text-sm font-bold mb-2">Sections</h5>
                        </div>
                        <div v-for="s in course.sessions" :key="s.id" class="p-2 rounded-lg"
                            :class="s.id === session.id ? '' : 'hover:bg-gray-50'"
                            :style="s.id === session.id ? { backgroundColor: (course.accentColor + '1A'), borderLeft: '4px solid ' + course.accentColor } : {}">
                            <router-link :to="`/course/${course.id}/session/${s.id}`" class="block">
                                <div class="text-sm font-medium"
                                    :style="s.id === session.id ? { color: course.accentColor } : {}">{{ s.title }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ s.chapters.length }} chapitres · {{ 20 }} pts
                                </div>
                            </router-link>
                        </div>
                    </div>
                    <div class="mt-4">
                        <h5 class="font-semibold">Actions</h5>
                        <div class="mt-2 flex gap-2">
                            <button @click="givePoints(10)" class="px-3 py-2 text-white rounded-full cta-anim"
                                :style="{ background: course.accentColor }">+10 pts</button>
                            <button @click="giveBadge" class="px-3 py-2 border rounded-full cta-anim"
                                :style="{ borderColor: course.accentColor, color: course.accentColor }">Badge</button>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
        <div v-else>
            <p>Session introuvable</p>
        </div>
        <!-- Toasts are now global via AppLayout -->
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from '../components/AppLayout.vue'
import MarkdownViewer from '../components/MarkdownViewer.vue'
import { useCoursesStore } from '../stores/courses'
import { showToast } from '../composables/useToast'

const route = useRoute()
const store = useCoursesStore()
const course = store.getCourse(route.params.id as string)
const session = course?.sessions.find((s: any) => s.id === route.params.sid)
const uploaded = ref(false)
const tocOpen = ref(false)
const activeChapter = ref<string | null>(null)
let _observer: IntersectionObserver | null = null

const studentId = 'student1'

const sessionProgress = computed(() => {
    if (!session) return {}
    const p = store.getProgress(studentId, course.id)
    return p[session.id] || {}
})

const sessionPct = computed(() => {
    if (!session) return 0
    // simple heuristic: if sessionProgress.done true => 100
    return sessionProgress.value.done ? 100 : 0
})

const uploadAccept = computed(() => {
    if (!session || !session.renderConfig || !session.renderConfig.allowedTypes) return undefined
    const types = session.renderConfig.allowedTypes as string[]
    const map: Record<string, string> = {
        image: 'image/*',
        file: '',
        code: '.php,.js,.py,.java,.txt',
        link: ''
    }
    const accepts = types.map((t) => map[t]).filter(Boolean)
    return accepts.length ? accepts.join(',') : undefined
})

const uploadMultiple = computed(() => {
    if (!session || !session.renderConfig) return false
    return (session.renderConfig.maxFiles || 1) > 1
})

function onFile(e: Event) {
    const input = e.target as HTMLInputElement
    const files = input.files
    if (!files || files.length === 0 || !course || !session) return
    const arr: any[] = []
    let remaining = files.length
    for (let i = 0; i < files.length; i++) {
        const f = files[i]
        const reader = new FileReader()
        reader.onload = () => {
            arr.push({ name: f.name, type: f.type, data: String(reader.result) })
            remaining--
            if (remaining === 0) {
                const key = `pf:upload:${course.id}:${session.id}:${studentId}`
                try {
                    localStorage.setItem(key, JSON.stringify(arr))
                } catch (e) {
                    localStorage.setItem(key, String(arr[0]?.data || ''))
                }
                uploaded.value = true
            }
        }
        reader.readAsDataURL(f)
    }
}

function givePoints(n = 10) {
    store.awardPoints(studentId, n)
    showToast(`+${n} points`)
}

function giveBadge() {
    if (!session) return
    store.awardBadge(studentId, { id: session.id + '-badge', title: 'Badge séance' })
    showToast('Badge accordé')
}

function completeSession() {
    if (!course || !session) return
    store.saveProgress(studentId, course.id, session.id, { done: true, at: Date.now() })
    showToast('Séance marquée comme terminée')
}

function scrollTo(id: string) {
    const el = document.getElementById(id)
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
    // observe chapter articles to set activeChapter
    try {
        const options = { root: null, rootMargin: '0px 0px -60% 0px', threshold: 0 }
        _observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeChapter.value = (entry.target as HTMLElement).id || null
                }
            })
        }, options)
        const els = document.querySelectorAll('article[id]')
        els.forEach((el) => _observer && _observer.observe(el))
    } catch (e) {
        // ignore
    }
    // set initial TOC open state based on viewport
    try {
        tocOpen.value = window.innerWidth >= 1024
    } catch (e) { }
})

onBeforeUnmount(() => {
    if (_observer) {
        _observer.disconnect()
        _observer = null
    }
})

// computed prev/next
const sessionIndex = computed(() => course?.sessions.findIndex((s: any) => s.id === session.id) ?? -1)
const prevSession = computed(() => (sessionIndex.value > 0 ? course.sessions[sessionIndex.value - 1] : null))
const nextSession = computed(() => (sessionIndex.value >= 0 && sessionIndex.value < course.sessions.length - 1 ? course.sessions[sessionIndex.value + 1] : null))

// expose toast and preview to template

</script>

<style scoped>
.rounded-full {
    border-radius: 9999px
}

.shadow-lg {
    box-shadow: 0 10px 25px rgba(2, 6, 23, 0.08)
}
</style>
