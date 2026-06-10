<template>
    <AppLayout>
        <section class="mb-8 text-center">
            <Badge variant="secondary" class="mb-3">Catalogue de cours</Badge>
            <h1 class="text-3xl font-bold tracking-tight">Explorez nos cours</h1>
            <p class="mx-auto mt-2 max-w-2xl text-muted-foreground">
                Des cours structurés pour apprendre efficacement, avec un système de récompenses motivant.
            </p>

            <div class="mx-auto mt-6 flex max-w-2xl flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <IconSearch class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="q" placeholder="Rechercher un cours…" class="pl-9" />
                </div>
                <Select v-model="level">
                    <SelectTrigger class="sm:w-48"><SelectValue placeholder="Tous niveaux" /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Tous niveaux</SelectItem>
                        <SelectItem value="Débutant">Débutant</SelectItem>
                        <SelectItem value="Intermédiaire">Intermédiaire</SelectItem>
                        <SelectItem value="Avancé">Avancé</SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </section>

        <!-- loading -->
        <div v-if="!store.loaded" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <SkeletonCard v-for="n in 6" :key="n" />
        </div>

        <!-- results -->
        <div v-else-if="filtered.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <CourseCard v-for="c in filtered" :key="c.id" :course="c" />
        </div>

        <!-- empty -->
        <Card v-else class="grid place-items-center gap-2 py-16 text-center">
            <IconSearchOff class="size-10 text-muted-foreground" />
            <p class="font-medium">Aucun cours ne correspond</p>
            <p class="text-sm text-muted-foreground">Essayez d'autres mots-clés ou réinitialisez les filtres.</p>
            <Button variant="outline" size="sm" class="mt-2" @click="reset">Réinitialiser</Button>
        </Card>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { IconSearch, IconSearchOff } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import CourseCard from '@/components/CourseCard.vue'
import SkeletonCard from '@/components/SkeletonCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const store = useCoursesStore()
const route = useRoute()
const router = useRouter()

const q = ref((route.query.q as string) || '')
const level = ref('all')

// keep the URL ?q= in sync with the search box
watch(q, (val) => {
    router.replace({ query: val ? { q: val } : {} })
})
watch(() => route.query.q, (val) => {
    if ((val as string || '') !== q.value) q.value = (val as string) || ''
})

const filtered = computed(() => {
    const term = q.value.trim().toLowerCase()
    return store.courses.filter((c: any) => {
        if (level.value !== 'all' && (c.level || '') !== level.value) return false
        if (term && !`${c.title} ${c.theme} ${c.scenario}`.toLowerCase().includes(term)) return false
        return true
    })
})

function reset() {
    q.value = ''
    level.value = 'all'
}
</script>
