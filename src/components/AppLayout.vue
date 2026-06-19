<template>
    <div class="min-h-screen flex flex-col bg-background">
        <!-- soft brand-tinted backdrop -->
        <div class="pointer-events-none fixed inset-0 -z-10 bg-gradient-to-b from-indigo-50/70 via-background to-background"></div>

        <NavBar />

        <main class="flex-1 py-8">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6">
                <slot />
            </div>
        </main>

        <footer class="border-t border-border/60 bg-slate-50/50 dark:bg-zinc-950/20 select-none">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 py-12">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-8 border-b border-border/50 pb-8">
                    <!-- Brand Section -->
                    <div class="sm:col-span-2 space-y-4">
                        <RouterLink to="/" class="flex items-center gap-3 outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg w-fit">
                            <img src="@/assets/logos/logo_full.png" alt="ProgressIA" /><br>
                        </RouterLink>
                        <p class="text-xs text-muted-foreground max-w-sm leading-relaxed">
                            Concevez des parcours d'apprentissage immersifs, suivez la progression de vos étudiants en temps réel et gamifiez vos cours pour stimuler l'engagement.
                        </p>
                        <div class="flex items-center gap-3.5 pt-1">
                            <a href="https://github.com" target="_blank" rel="noopener noreferrer" class="text-muted-foreground hover:text-foreground hover:scale-110 transition" aria-label="GitHub">
                                <IconBrandGithub class="size-5" />
                            </a>
                            <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="text-muted-foreground hover:text-foreground hover:scale-110 transition" aria-label="Twitter">
                                <IconBrandTwitter class="size-5" />
                            </a>
                            <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="text-muted-foreground hover:text-foreground hover:scale-110 transition" aria-label="LinkedIn">
                                <IconBrandLinkedin class="size-5" />
                            </a>
                        </div>
                    </div>

                    <!-- Column 1: Platform -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-foreground mb-4">Plateforme</h3>
                        <ul class="space-y-2.5 text-xs">
                            <li><RouterLink to="/courses" class="text-muted-foreground hover:text-primary transition-colors">Catalogue de cours</RouterLink></li>
                            <li><RouterLink to="/dashboard/student" class="text-muted-foreground hover:text-primary transition-colors">Tableau de bord</RouterLink></li>
                            <li><RouterLink to="/login" class="text-muted-foreground hover:text-primary transition-colors">Espace Enseignant</RouterLink></li>
                        </ul>
                    </div>

                    <!-- Column 2: Ressources -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-foreground mb-4">Ressources</h3>
                        <ul class="space-y-2.5 text-xs">
                            <li><a href="#" class="text-muted-foreground hover:text-primary transition-colors">Documentation</a></li>
                            <li><a href="#" class="text-muted-foreground hover:text-primary transition-colors">Guide pédagogique</a></li>
                            <li><a href="#" class="text-muted-foreground hover:text-primary transition-colors">Statut plateforme</a></li>
                        </ul>
                    </div>

                    <!-- Column 3: Légal -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-foreground mb-4">Légal</h3>
                        <ul class="space-y-2.5 text-xs">
                            <li><a href="#" class="text-muted-foreground hover:text-primary transition-colors">Mentions légales</a></li>
                            <li><a href="#" class="text-muted-foreground hover:text-primary transition-colors">Politique de confidentialité</a></li>
                            <li><a href="#" class="text-muted-foreground hover:text-primary transition-colors">Conditions générales (CGU)</a></li>
                        </ul>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 text-xs text-muted-foreground">
                    <p>© {{ currentYear }} ProgressIA. Tous droits réservés.</p>
                    <p class="flex items-center gap-1">Conçu avec passion pour l'éducation moderne ✨</p>
                </div>
            </div>
        </footer>

        <Toaster rich-colors position="bottom-right" />
        <ConfirmDialog />
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconBrandGithub, IconBrandTwitter, IconBrandLinkedin } from '@tabler/icons-vue'
import NavBar from './NavBar.vue'
import ConfirmDialog from './ConfirmDialog.vue'
import { Toaster } from '@/components/ui/sonner'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped></style>
