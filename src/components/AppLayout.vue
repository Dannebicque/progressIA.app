<template>
    <div class="min-h-screen flex flex-col">
        <NavBar />
        <main class="flex-1 bg-gradient-to-b from-white/60 to-white/40 py-8">
            <div class="w-full px-6">
                <slot />
            </div>
        </main>
        <Toast />
        <footer class="text-center text-sm text-gray-500 py-6">
            © PedagoFlow — Prototype
        </footer>
    </div>
</template>

<script setup lang="ts">
import NavBar from './NavBar.vue'
import Toast from './Toast.vue'
</script>

<style scoped></style>
