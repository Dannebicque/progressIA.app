<template>
    <BackofficeLayout>
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Tableau de bord Back-office</h1>
                <p class="text-sm text-muted-foreground">Administration globale des cours, utilisateurs et étudiants inscrits.</p>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <Card class="flex flex-col border-t-4 border-t-indigo-500 shadow-sm transition hover:shadow-md">
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2">
                        <IconBook class="size-5 text-indigo-500" /> Cours & Séances
                    </CardTitle>
                    <CardDescription>Gérez les chapitres, pages de cours, évaluations et quiz interactifs.</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <RouterLink to="/backoffice/courses">
                        <Button class="w-full bg-indigo-600 hover:bg-indigo-700">Gérer les cours</Button>
                    </RouterLink>
                </CardContent>
            </Card>

            <Card class="flex flex-col border-t-4 border-t-sky-500 shadow-sm transition hover:shadow-md">
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2">
                        <IconUsers class="size-5 text-sky-500" /> Étudiants & Effectifs
                    </CardTitle>
                    <CardDescription>Visualisez la progression, attribuez des points, et importez des listes via fichier CSV.</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <RouterLink to="/backoffice/students">
                        <Button class="w-full bg-sky-600 hover:bg-sky-700">Gérer les effectifs</Button>
                    </RouterLink>
                </CardContent>
            </Card>

            <Card class="flex flex-col border-t-4 border-t-emerald-500 shadow-sm transition hover:shadow-md">
                <CardHeader>
                    <CardTitle class="text-base flex items-center gap-2">
                        <IconUserShield class="size-5 text-emerald-500" /> Comptes Utilisateurs
                    </CardTitle>
                    <CardDescription>Gérez les comptes, rôles et statuts d'accès de la plateforme.</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <RouterLink to="/backoffice/users">
                        <Button class="w-full bg-emerald-600 hover:bg-emerald-700">Gérer les utilisateurs</Button>
                    </RouterLink>
                </CardContent>
            </Card>
        </div>
    </BackofficeLayout>
</template>

<script setup lang="ts">
import { IconBook, IconUsers, IconUserShield } from '@tabler/icons-vue'
import BackofficeLayout from '@/components/BackofficeLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
</script>

