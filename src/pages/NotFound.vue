<template>
    <AppLayout>
        <div class="grid place-items-center py-20 text-center">
            <div class="text-7xl font-extrabold text-brand-gradient">404</div>
            <h1 class="mt-4 text-2xl font-bold tracking-tight">Page introuvable</h1>
            <p class="mt-2 max-w-md text-muted-foreground">La page que vous cherchez n'existe pas ou a été déplacée.</p>
            <div class="mt-6 flex gap-3">
                <RouterLink to="/"><Button><IconHome class="size-4" /> Accueil</Button></RouterLink>
                <RouterLink to="/courses"><Button variant="outline">Voir le catalogue</Button></RouterLink>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { IconHome } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import { Button } from '@/components/ui/button'
</script>
