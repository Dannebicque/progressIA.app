<template>
    <AppLayout>
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Back-office</h1>
                <p class="text-sm text-muted-foreground">Administration des cours, utilisateurs et étudiants inscrits.</p>
            </div>
            <RouterLink to="/stats/teacher">
                <Button variant="outline" size="sm"><IconChartBar class="size-4" /> Statistiques</Button>
            </RouterLink>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card class="flex flex-col">
                <CardHeader>
                    <CardTitle class="text-base">Cours</CardTitle>
                    <CardDescription>Gérez les cours, séances, chapitres, pages et évaluations.</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <RouterLink to="/backoffice/courses">
                        <Button class="w-full">Ouvrir</Button>
                    </RouterLink>
                </CardContent>
            </Card>

            <Card class="flex flex-col">
                <CardHeader>
                    <CardTitle class="text-base">Utilisateurs</CardTitle>
                    <CardDescription>Gérez les comptes, rôles et statuts des utilisateurs.</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <RouterLink to="/backoffice/users">
                        <Button class="w-full">Ouvrir</Button>
                    </RouterLink>
                </CardContent>
            </Card>

            <Card class="flex flex-col">
                <CardHeader>
                    <CardTitle class="text-base">Étudiants / inscrits</CardTitle>
                    <CardDescription>Filtrez les inscrits et importez un CSV avec suivi de progression.</CardDescription>
                </CardHeader>
                <CardContent class="mt-auto">
                    <RouterLink to="/backoffice/students">
                        <Button class="w-full">Ouvrir</Button>
                    </RouterLink>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { IconChartBar } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
</script>
