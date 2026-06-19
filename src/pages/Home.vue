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

        <!-- Recent Courses -->
        <section v-if="auth.isAuthenticated && recentCourses.length" class="mt-8">
            <h2 class="mb-4 text-xl font-semibold tracking-tight">
                {{ auth.isTeacher() ? 'Mes derniers cours édités' : 'Continuer mon apprentissage' }}
            </h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <CourseCard v-for="c in recentCourses" :key="c.id" :course="c" />
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
                            <CardTitle class="text-base">Pourquoi ProgressIA ?</CardTitle>
                        </div>
                        <CardDescription>Interface simple, édition Markdown, suivi pédagogique clair.</CardDescription>
                    </CardHeader>
                </Card>
            </aside>
        </section>

        <!-- Institution Marketing Section -->
        <section class="mt-16 border-t border-border/60 pt-16">
            <div class="text-center max-w-3xl mx-auto space-y-4">
                <Badge variant="outline" class="border-indigo-600/30 text-indigo-600 bg-indigo-50/50 dark:bg-indigo-950/20 dark:text-indigo-400">Pour les établissements</Badge>
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl">
                    Déployez ProgressIA au sein de votre école ou université
                </h2>
                <p class="text-muted-foreground text-base sm:text-lg">
                    Offrez à vos enseignants et étudiants une expérience d'apprentissage moderne, immersive et entièrement scénarisée.
                </p>
            </div>

            <!-- Features Grid -->
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <Card class="hover:border-primary/30 transition-all hover:shadow-md group">
                    <CardHeader>
                        <div class="size-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
                            <IconSchool class="size-6" />
                        </div>
                        <CardTitle class="mt-4 text-lg">Multi-Établissements</CardTitle>
                        <CardDescription class="mt-2 text-sm leading-relaxed">
                            Gerez plusieurs départements, promotions (BUT, Licence, Master) et groupes de TD/TP avec des accès cloisonnés et sécurisés.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card class="hover:border-primary/30 transition-all hover:shadow-md group">
                    <CardHeader>
                        <div class="size-12 rounded-xl bg-purple-50 dark:bg-purple-950/40 flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
                            <IconPalette class="size-6" />
                        </div>
                        <CardTitle class="mt-4 text-lg">Identité Visuelle Propre</CardTitle>
                        <CardDescription class="mt-2 text-sm leading-relaxed">
                            Adaptez l'interface aux couleurs de votre charte graphique et permettez la personnalisation complète des cours par les enseignants.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card class="hover:border-primary/30 transition-all hover:shadow-md group">
                    <CardHeader>
                        <div class="size-12 rounded-xl bg-amber-50 dark:bg-amber-950/40 flex items-center justify-center text-amber-600 dark:text-amber-300 group-hover:scale-110 transition-transform">
                            <IconChartBar class="size-6" />
                        </div>
                        <CardTitle class="mt-4 text-lg">Suivi & Statistiques Globales</CardTitle>
                        <CardDescription class="mt-2 text-sm leading-relaxed">
                            Visualisez la progression moyenne, les taux d'engagement et le taux de validation des séances à l'échelle de votre établissement.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card class="hover:border-primary/30 transition-all hover:shadow-md group">
                    <CardHeader>
                        <div class="size-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform">
                            <IconShieldCheck class="size-6" />
                        </div>
                        <CardTitle class="mt-4 text-lg">Souveraineté des Données</CardTitle>
                        <CardDescription class="mt-2 text-sm leading-relaxed">
                            Hébergement RGPD, authentification SSO (CAS, Shibboleth, OAuth2) pour connecter ProgressIA à votre ENT en toute simplicité.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card class="hover:border-primary/30 transition-all hover:shadow-md group">
                    <CardHeader>
                        <div class="size-12 rounded-xl bg-blue-50 dark:bg-blue-950/40 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
                            <IconDevices class="size-6" />
                        </div>
                        <CardTitle class="mt-4 text-lg">Multi-supports & LTI</CardTitle>
                        <CardDescription class="mt-2 text-sm leading-relaxed">
                            Compatible avec les ordinateurs, tablettes et smartphones. Intégration LTI transparente avec Moodle, Canvas ou Blackboard.
                        </CardDescription>
                    </CardHeader>
                </Card>

                <Card class="hover:border-primary/30 transition-all hover:shadow-md group">
                    <CardHeader>
                        <div class="size-12 rounded-xl bg-rose-50 dark:bg-rose-950/40 flex items-center justify-center text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform">
                            <IconBuildingCommunity class="size-6" />
                        </div>
                        <CardTitle class="mt-4 text-lg">Espace Collaboratif</CardTitle>
                        <CardDescription class="mt-2 text-sm leading-relaxed">
                            Partagez des ressources pédagogiques, co-éditez des cours à plusieurs enseignants et créez des banques de questions communes.
                        </CardDescription>
                    </CardHeader>
                </Card>
            </div>

            <!-- Demo Form / Call to Action -->
            <Card class="mt-12 overflow-hidden border-0 bg-gradient-to-r from-indigo-900 via-indigo-950 to-slate-900 text-white shadow-xl relative">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-indigo-500/20 via-transparent to-transparent pointer-events-none"></div>
                <CardContent class="grid items-center gap-8 p-8 lg:grid-cols-2 lg:p-12">
                    <div class="space-y-4">
                        <h3 class="text-2xl font-bold tracking-tight sm:text-3xl">Prêt à transformer l'apprentissage ?</h3>
                        <p class="text-indigo-200/90 leading-relaxed text-sm sm:text-base">
                            Contactez notre équipe pédagogique pour obtenir une démonstration personnalisée, configurer un espace de test gratuit pour vos enseignants, ou discuter d'un déploiement sur-mesure.
                        </p>
                        <div class="flex flex-wrap items-center gap-6 pt-2">
                            <div class="flex items-center gap-2 text-xs text-indigo-200">
                                <svg class="size-4 shrink-0 text-emerald-400 fill-emerald-400" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.8-11.2a1 1 0 0 0-1.4-1.4L9 8.6 7.6 7.2a1 1 0 0 0-1.4 1.4l2.1 2.1a1 1 0 0 0 1.4 0l3.8-3.9Z" clip-rule="evenodd" /></svg>
                                Démo gratuite & sans engagement
                            </div>
                            <div class="flex items-center gap-2 text-xs text-indigo-200">
                                <svg class="size-4 shrink-0 text-emerald-400 fill-emerald-400" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.8-11.2a1 1 0 0 0-1.4-1.4L9 8.6 7.6 7.2a1 1 0 0 0-1.4 1.4l2.1 2.1a1 1 0 0 0 1.4 0l3.8-3.9Z" clip-rule="evenodd" /></svg>
                                Installation en moins de 48 heures
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-white/5 p-6 backdrop-blur-sm border border-white/10 space-y-4">
                        <h4 class="font-semibold text-base text-white">Demander une présentation</h4>
                        <form @submit.prevent="handleContactSubmit" class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-bold text-indigo-200">Nom complet</label>
                                    <input required v-model="contactForm.name" type="text" placeholder="Ex: Jean Dupont" class="w-full h-8 rounded-md bg-white/10 border border-white/10 px-3 py-1 text-xs text-white placeholder-indigo-200/50 focus:outline-none focus:ring-1 focus:ring-indigo-400" />
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] uppercase font-bold text-indigo-200">Email professionnel</label>
                                    <input required v-model="contactForm.email" type="email" placeholder="jean.dupont@univ.fr" class="w-full h-8 rounded-md bg-white/10 border border-white/10 px-3 py-1 text-xs text-white placeholder-indigo-200/50 focus:outline-none focus:ring-1 focus:ring-indigo-400" />
                                </div>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-indigo-200">Établissement</label>
                                <input required v-model="contactForm.institution" type="text" placeholder="Ex: IUT de Bordeaux" class="w-full h-8 rounded-md bg-white/10 border border-white/10 px-3 py-1 text-xs text-white placeholder-indigo-200/50 focus:outline-none focus:ring-1 focus:ring-indigo-400" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-[10px] uppercase font-bold text-indigo-200">Message / Besoins</label>
                                <textarea v-model="contactForm.message" rows="2" placeholder="Décrivez votre projet pédagogique..." class="w-full rounded-md bg-white/10 border border-white/10 px-3 py-1.5 text-xs text-white placeholder-indigo-200/50 focus:outline-none focus:ring-1 focus:ring-indigo-400"></textarea>
                            </div>
                            <Button type="submit" class="w-full bg-white text-indigo-950 hover:bg-white/90 text-xs h-9 font-semibold mt-2">
                                Envoyer ma demande
                            </Button>
                        </form>
                    </div>
                </CardContent>
            </Card>
        </section>
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { 
    IconArrowRight, 
    IconTrophy, 
    IconSparkles,
    IconSchool,
    IconPalette,
    IconChartBar,
    IconShieldCheck,
    IconDevices,
    IconBuildingCommunity
} from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import CourseCard from '@/components/CourseCard.vue'
import { useCoursesStore } from '@/stores/courses'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { api } from '@/api/client'
import { showToast } from '@/composables/useToast'

const store = useCoursesStore()
const auth = useAuthStore()
const featured = computed(() => store.courses.slice(0, 4))

const recentCourses = ref<any[]>([])
const loadingRecent = ref(false)

const contactForm = ref({
    name: '',
    email: '',
    institution: '',
    message: ''
})

function handleContactSubmit() {
    showToast('Demande envoyée ! Notre équipe vous contactera sous 24h.', 'success')
    contactForm.value = {
        name: '',
        email: '',
        institution: '',
        message: ''
    }
}

onMounted(async () => {
    if (auth.isAuthenticated) {
        loadingRecent.value = true
        try {
            recentCourses.value = await api.get<any[]>('/api/me/recent-courses')
        } catch (e) {
            console.error('Failed to fetch recent courses', e)
        } finally {
            loadingRecent.value = false
        }
    }
})
</script>
