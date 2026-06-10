<template>
    <AppLayout>
        <div v-if="course && session" class="grid gap-6 lg:grid-cols-3">
            <main class="space-y-6 lg:col-span-2">
                <!-- header -->
                <Card class="overflow-hidden pt-0">
                    <div class="h-1.5 w-full" :style="{ background: accent }"></div>
                    <CardContent class="flex flex-col gap-4 pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <RouterLink :to="`/course/${course.id}`" class="text-xs text-muted-foreground hover:text-primary">← {{ course.title }}</RouterLink>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ session.title }}</h1>
                            <p v-if="session.pitch" class="mt-1 text-sm text-muted-foreground">{{ session.pitch }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <RouterLink v-if="prevSession" :to="`/course/${course.id}/session/${prevSession.id}`">
                                <Button variant="outline" size="sm"><IconArrowLeft class="size-4" /> Préc.</Button>
                            </RouterLink>
                            <RouterLink v-if="nextSession" :to="`/course/${course.id}/session/${nextSession.id}`">
                                <Button size="sm">Suivant <IconArrowRight class="size-4" /></Button>
                            </RouterLink>
                        </div>
                    </CardContent>
                </Card>

                <!-- chapters -->
                <Card v-for="ch in session.chapters" :id="`chapter-${ch.id}`" :key="ch.id">
                    <CardHeader><CardTitle class="text-lg">{{ ch.title }}</CardTitle></CardHeader>
                    <CardContent>
                        <MarkdownViewer :source="ch.content" />
                    </CardContent>
                </Card>

                <!-- upload / complete -->
                <Card v-if="session.renderConfig?.allowUpload">
                    <CardHeader>
                        <CardTitle class="text-base">Rendu</CardTitle>
                        <CardDescription>Déposez votre travail pour cette séance.</CardDescription>
                    </CardHeader>
                    <CardContent class="flex flex-wrap items-center gap-3">
                        <label>
                            <input :accept="uploadAccept" :multiple="uploadMultiple" type="file" class="hidden" @change="onFile" />
                            <span :class="buttonVariants({ variant: 'outline' })"><IconUpload class="size-4" /> Déposer un fichier</span>
                        </label>
                        <Button variant="default" class="bg-emerald-600 hover:bg-emerald-600/90" @click="completeSession">
                            <IconCircleCheck class="size-4" /> Marquer comme terminé
                        </Button>
                        <Badge v-if="uploaded" variant="secondary" class="gap-1"><IconCheck class="size-3.5" /> Fichier déposé</Badge>
                        <div v-if="session.renderConfig?.allowedTypes?.length" class="flex flex-wrap items-center gap-1 text-xs text-muted-foreground">
                            Types :
                            <Badge v-for="t in session.renderConfig.allowedTypes" :key="t" variant="outline">{{ t }}</Badge>
                        </div>
                    </CardContent>
                </Card>
            </main>

            <!-- sidebar -->
            <aside class="lg:col-span-1">
                <Card class="sticky top-24">
                    <CardHeader><CardTitle class="text-base">Contenu de la séance</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <ul v-if="session.chapters.length" class="space-y-1 text-sm">
                            <li v-for="ch in session.chapters" :key="ch.id">
                                <button class="text-left transition hover:text-primary"
                                    :class="activeChapter === `chapter-${ch.id}` ? 'font-semibold text-primary' : 'text-muted-foreground'"
                                    @click="scrollTo(`chapter-${ch.id}`)">{{ ch.title }}</button>
                            </li>
                        </ul>
                        <Separator />
                        <div>
                            <div class="mb-2 text-sm font-medium">Séances du cours</div>
                            <RouterLink v-for="s in course.sessions" :key="s.id" :to="`/course/${course.id}/session/${s.id}`"
                                class="block rounded-md px-2 py-1.5 text-sm transition"
                                :class="s.id === session.id ? 'bg-accent font-medium text-accent-foreground' : 'text-muted-foreground hover:bg-muted'">
                                {{ s.title }}
                            </RouterLink>
                        </div>
                        <Separator />
                        <div class="flex gap-2">
                            <Button size="sm" variant="outline" class="flex-1" @click="givePoints(10)"><IconStar class="size-4" /> +10 pts</Button>
                            <Button size="sm" variant="outline" class="flex-1" @click="giveBadge"><IconAward class="size-4" /> Badge</Button>
                        </div>
                    </CardContent>
                </Card>
            </aside>
        </div>

        <Card v-else class="grid place-items-center py-16 text-center text-muted-foreground">Séance introuvable.</Card>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { IconArrowLeft, IconArrowRight, IconUpload, IconCircleCheck, IconCheck, IconStar, IconAward } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import MarkdownViewer from '@/components/MarkdownViewer.vue'
import { useCoursesStore } from '@/stores/courses'
import { showToast } from '@/composables/useToast'
import { Button, buttonVariants } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const route = useRoute()
const store = useCoursesStore()
const studentId = 'student1'

const course = computed(() => store.getCourse(route.params.id as string))
const session = computed(() => course.value?.sessions.find((s: any) => String(s.id) === String(route.params.sid)))
const accent = computed(() => course.value?.accentColor || '#7c3aed')
const uploaded = ref(false)
const activeChapter = ref<string | null>(null)
let observer: IntersectionObserver | null = null

const sessionIndex = computed(() => course.value?.sessions.findIndex((s: any) => s.id === session.value?.id) ?? -1)
const prevSession = computed(() => (sessionIndex.value > 0 ? course.value!.sessions[sessionIndex.value - 1] : null))
const nextSession = computed(() => (sessionIndex.value >= 0 && sessionIndex.value < (course.value?.sessions.length ?? 0) - 1 ? course.value!.sessions[sessionIndex.value + 1] : null))

const uploadAccept = computed(() => {
    const types = session.value?.renderConfig?.allowedTypes as string[] | undefined
    if (!types) return undefined
    const map: Record<string, string> = { image: 'image/*', file: '', code: '.php,.js,.py,.java,.txt', link: '' }
    const accepts = types.map((t) => map[t]).filter(Boolean)
    return accepts.length ? accepts.join(',') : undefined
})
const uploadMultiple = computed(() => (session.value?.renderConfig?.maxFiles || 1) > 1)

function onFile(e: Event) {
    const files = (e.target as HTMLInputElement).files
    if (!files || !files.length || !course.value || !session.value) return
    const arr: any[] = []
    let remaining = files.length
    for (const f of Array.from(files)) {
        const reader = new FileReader()
        reader.onload = () => {
            arr.push({ name: f.name, type: f.type, data: String(reader.result) })
            if (--remaining === 0) {
                const key = `pf:upload:${course.value!.id}:${session.value!.id}:${studentId}`
                try { localStorage.setItem(key, JSON.stringify(arr)) } catch { /* quota */ }
                uploaded.value = true
                showToast('Fichier déposé')
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
    if (!session.value) return
    store.awardBadge(studentId, { id: `${session.value.id}-badge`, title: 'Badge séance' })
    showToast('Badge accordé')
}
function completeSession() {
    if (!course.value || !session.value) return
    store.saveProgress(studentId, course.value.id, session.value.id, { done: true, at: Date.now() })
    showToast('Séance marquée comme terminée')
}
function scrollTo(id: string) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) activeChapter.value = (en.target as HTMLElement).id })
    }, { rootMargin: '0px 0px -60% 0px' })
    document.querySelectorAll('[id^="chapter-"]').forEach((el) => observer?.observe(el))
})
onBeforeUnmount(() => { observer?.disconnect(); observer = null })
</script>
