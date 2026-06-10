<template>
    <header class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white">
        <div class="w-full px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center font-bold">PF</div>
                    <div class="hidden sm:block">
                        <div class="font-semibold">PedagoFlow</div>
                        <div class="text-xs opacity-80">Concevez. Suivez. Gamifiez.</div>
                    </div>
                </router-link>
            </div>

            <div class="flex-1 mx-6 hidden md:flex items-center">
                <div class="relative w-full max-w-lg">
                    <input placeholder="Rechercher un cours, une séance..."
                        class="w-full rounded-full py-2 px-4 text-sm bg-white/20 placeholder-white/70 focus:outline-none" />
                    <button
                        class="absolute right-1 top-1/2 -translate-y-1/2 bg-white/10 px-3 py-1 rounded-full text-sm">Rechercher</button>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <router-link to="/courses"
                    class="hidden sm:inline text-sm bg-white/10 hover:bg-white/20 px-3 py-1 rounded">Catalogue</router-link>
                <router-link v-if="auth.user?.role==='teacher'" to="/backoffice"
                    class="hidden sm:inline text-sm bg-white/10 hover:bg-white/20 px-3 py-1 rounded">Back-office</router-link>
                <router-link to="/dashboard/student"
                    class="hidden sm:inline text-sm bg-white/10 hover:bg-white/20 px-3 py-1 rounded">Mon
                    tableau</router-link>

                <div class="relative">
                    <template v-if="auth.user">
                        <button @click="toggleMenu"
                            class="w-9 h-9 rounded-full bg-white text-indigo-700 flex items-center justify-center">{{ auth.user.name.charAt(0).toUpperCase() }}</button>
                        <div v-if="menu" class="absolute right-0 mt-2 w-48 bg-white text-gray-800 rounded shadow py-2 z-20">
                            <div class="px-3 py-2 text-sm">Connecté · {{ auth.user.role }}</div>
                            <a class="block px-3 py-2 text-sm hover:bg-gray-100" href="#">Mon profil</a>
                            <a @click.prevent="logout" class="block px-3 py-2 text-sm hover:bg-gray-100" href="#">Se déconnecter</a>
                        </div>
                    </template>
                    <template v-else>
                        <router-link to="/login" class="px-3 py-1 bg-white text-indigo-700 rounded">Connexion</router-link>
                    </template>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const menu = ref(false)
function toggleMenu() { menu.value = !menu.value }
function logout() { auth.logout(); location.href = '/' }
</script>

<style scoped></style>
