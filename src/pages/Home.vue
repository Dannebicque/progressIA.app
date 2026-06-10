<template>
    <AppLayout>
        <section class="rounded-xl overflow-hidden relative">
            <div :style="{ background: 'linear-gradient(135deg, rgba(124,58,237,0.95), rgba(236,72,153,0.9))' }"
                class="text-white p-12 lg:p-20">
                <div class="max-w-5xl mx-auto flex flex-col lg:flex-row items-center gap-8">
                    <div class="flex-1">
                        <h1 class="text-4xl lg:text-5xl font-bold">Créez des cours engageants et gamifiés</h1>
                        <p class="mt-4 text-lg opacity-90">Rédigez en Markdown, suivez la progression et récompensez
                            vos apprenants avec points & badges.</p>
                        <div class="mt-6 flex flex-wrap gap-4">
                            <router-link to="/courses"
                                class="px-6 py-3 bg-white text-indigo-700 rounded-full font-medium cta-anim">Explorer
                                les
                                cours</router-link>
                            <router-link to="/backoffice"
                                class="px-6 py-3 border border-white/30 rounded-full cta-anim">Éditer
                                un cours</router-link>
                        </div>
                    </div>
                    <div class="hidden lg:block w-80 h-48 bg-white/10 rounded-lg p-4">
                        <!-- small promo card -->
                        <div class="text-sm">Top cours</div>
                        <div class="mt-3">
                            <CourseCard :course="featured[0]" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <h2 class="text-2xl font-semibold mb-4">Cours en vedette</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <CourseCard v-for="c in featured" :key="c.id" :course="c" />
                </div>
            </div>

            <aside class="space-y-4">
                <div class="bg-white rounded-xl p-4 shadow">
                    <h3 class="font-semibold">Gamification rapide</h3>
                    <p class="text-sm text-gray-600 mt-2">Points, badges et défis pour garder l'engagement.</p>
                </div>
                <div class="bg-white rounded-xl p-4 shadow">
                    <h3 class="font-semibold">Pourquoi PedagoFlow ?</h3>
                    <p class="text-sm text-gray-600 mt-2">Interface simple, édition Markdown, suivi pédagogique clair.
                    </p>
                </div>
            </aside>
        </section>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '../components/AppLayout.vue'
import CourseCard from '../components/CourseCard.vue'
import { useCoursesStore } from '../stores/courses'

const store = useCoursesStore()
const courses = store.courses
const featured = courses.slice(0, 4)
</script>

<style scoped></style>
