<template>
    <AppLayout>
        <div class="max-w-md mx-auto bg-white rounded-xl p-6 shadow mt-12">
            <h2 class="text-xl font-bold mb-4">Connexion</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <input v-model="email" type="email" placeholder="Email" autocomplete="username"
                    class="w-full border rounded px-3 py-2" />
                <input v-model="password" type="password" placeholder="Mot de passe" autocomplete="current-password"
                    class="w-full border rounded px-3 py-2" />
                <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
                <div class="flex gap-2">
                    <button type="submit" :disabled="loading"
                        class="px-4 py-2 bg-indigo-600 text-white rounded disabled:opacity-60">
                        {{ loading ? 'Connexion…' : 'Se connecter' }}
                    </button>
                    <router-link to="/" class="px-4 py-2 border rounded">Annuler</router-link>
                </div>
            </form>
            <div class="mt-6 text-xs text-gray-500 space-y-1">
                <p class="font-medium text-gray-600">Comptes de démonstration :</p>
                <button type="button" @click="fill('teacher')" class="block hover:text-indigo-600">
                    Enseignant — teacher@pedagoflow.test / teacher
                </button>
                <button type="button" @click="fill('student')" class="block hover:text-indigo-600">
                    Étudiant — student@pedagoflow.test / student
                </button>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/AppLayout.vue'
import { useAuthStore } from '../stores/auth'
import { ApiError } from '../api/client'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const router = useRouter()
const auth = useAuthStore()

function fill(role: 'teacher' | 'student') {
    email.value = `${role}@pedagoflow.test`
    password.value = role
}

async function submit() {
    error.value = ''
    if (!email.value || !password.value) {
        error.value = 'Renseignez votre email et votre mot de passe.'
        return
    }
    loading.value = true
    try {
        await auth.login(email.value, password.value)
        router.push('/')
    } catch (e) {
        error.value = e instanceof ApiError && e.status === 401
            ? 'Identifiants invalides.'
            : 'Connexion impossible. Réessayez.'
    } finally {
        loading.value = false
    }
}
</script>

<style scoped></style>
