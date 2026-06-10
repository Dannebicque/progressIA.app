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

        <footer class="border-t border-border/60">
            <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 py-6 text-center text-sm text-muted-foreground">
                © PedagoFlow — Prototype
            </div>
        </footer>

        <Toaster rich-colors position="bottom-right" />
    </div>
</template>

<script setup lang="ts">
import NavBar from './NavBar.vue'
import { Toaster } from '@/components/ui/sonner'
</script>

<style scoped></style>
