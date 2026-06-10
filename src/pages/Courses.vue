<template>
    <AppLayout>
        <section class="py-10 rounded-lg bg-gradient-to-b from-white/60 via-blue-50 to-white/60 mb-6">
            <div class="w-full text-center">
                <div class="inline-block text-sm bg-indigo-50 text-indigo-600 px-3 py-1 rounded mb-3">Catalogue de cours
                </div>
                <h1 class="text-3xl font-bold mt-2">Explorez nos cours</h1>
                <p class="text-gray-600 mt-2">Des cours structurés pour apprendre efficacement avec un système de
                    récompenses motivant.</p>

                <div class="mt-6 max-w-3xl mx-auto flex items-center gap-3">
                    <input v-model="q" placeholder="Rechercher un cours..."
                        class="flex-1 rounded-full py-3 px-4 shadow-sm border bg-white" />
                    <select v-model="level" class="rounded-full py-2 px-3 border bg-white">
                        <option value="">Tous niveaux</option>
                        <option>Débutant</option>
                        <option>Intermédiaire</option>
                        <option>Avancé</option>
                    </select>
                </div>
            </div>
        </section>

        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <template v-if="loading">
                <SkeletonCard v-for="n in 6" :key="n" />
            </template>
            <template v-else>
                <CourseCard v-for="c in filtered" :key="c.id" :course="c" />
            </template>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '../components/AppLayout.vue'
import CourseCard from '../components/CourseCard.vue'
import { useCoursesStore } from '../stores/courses'
import { ref, computed, onMounted } from 'vue'
import SkeletonCard from '../components/SkeletonCard.vue'

const store = useCoursesStore()
const courses = store.courses
const q = ref('')
const level = ref('')
const loading = ref(true)

onMounted(() => { setTimeout(() => loading.value = false, 700) })

const filtered = computed(() => {
    return courses.filter((c: any) => {
        if (q.value && !`${c.title} ${c.scenario}`.toLowerCase().includes(q.value.toLowerCase())) return false
        if (level.value) return true // demo: levels not in data
        return true
    })
})
</script>

<style scoped></style>
