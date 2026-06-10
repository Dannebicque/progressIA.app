<template>
    <AppLayout>
        <!-- Hero -->
        <section class="overflow-hidden rounded-2xl bg-brand-gradient text-white shadow-sm">
            <div class="grid items-center gap-8 p-8 lg:grid-cols-2 lg:p-14">
                <div>
                    <Badge class="mb-4 bg-white/15 text-white hover:bg-white/20">Plateforme pédagogique</Badge>
                    <h1 class="text-3xl font-bold leading-tight lg:text-5xl">Créez des cours engageants et gamifiés</h1>
                    <p class="mt-4 max-w-xl text-lg text-white/90">Rédigez en Markdown, suivez la progression et récompensez vos apprenants avec points & badges.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <RouterLink to="/courses">
                            <Button size="lg" class="rounded-full bg-white text-indigo-700 hover:bg-white/90">
                                Explorer les cours <IconArrowRight class="size-4" />
                            </Button>
                        </RouterLink>
                        <RouterLink v-if="auth.isTeacher()" to="/backoffice">
                            <Button size="lg" variant="outline" class="rounded-full border-white/40 bg-white/10 text-white hover:bg-white/20">
                                Éditer un cours
                            </Button>
                        </RouterLink>
                    </div>
                </div>

                <div class="hidden lg:block">
                    <div v-if="featured[0]" class="rounded-2xl bg-white/10 p-3 backdrop-blur">
                        <p class="px-2 pb-2 text-sm text-white/80">Cours en vedette</p>
                        <CourseCard :course="featured[0]" class="text-foreground" />
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured + side -->
        <section class="mt-8 grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <h2 class="mb-4 text-xl font-semibold tracking-tight">Cours en vedette</h2>
                <div v-if="featured.length" class="grid gap-4 sm:grid-cols-2">
                    <CourseCard v-for="c in featured" :key="c.id" :course="c" />
                </div>
                <Card v-else class="grid place-items-center py-12 text-center text-muted-foreground">
                    Aucun cours pour le moment.
                </Card>
            </div>

            <aside class="space-y-4">
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <IconTrophy class="size-5 text-amber-500" />
                            <CardTitle class="text-base">Gamification</CardTitle>
                        </div>
                        <CardDescription>Points, badges et défis pour garder l'engagement des apprenants.</CardDescription>
                    </CardHeader>
                </Card>
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <IconSparkles class="size-5 text-primary" />
                            <CardTitle class="text-base">Pourquoi PedagoFlow ?</CardTitle>
                        </div>
                        <CardDescription>Interface simple, édition Markdown, suivi pédagogique clair.</CardDescription>
                    </CardHeader>
                </Card>
            </aside>
        </section>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconArrowRight, IconTrophy, IconSparkles } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import CourseCard from '@/components/CourseCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const store = useCoursesStore()
const auth = useAuthStore()
const featured = computed(() => store.courses.slice(0, 4))
</script>
