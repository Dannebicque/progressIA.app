<template>
    <AppLayout>
        <div class="max-w-md mx-auto bg-white rounded-xl p-6 shadow mt-12">
            <h2 class="text-xl font-bold mb-4">Connexion (prototype)</h2>
            <form @submit.prevent="submit" class="space-y-4">
                <input v-model="name" placeholder="Votre nom" class="w-full border rounded px-3 py-2" />
                <div>
                    <label class="mr-3">
                        <input type="radio" value="student" v-model="role" /> Étudiant
                    </label>
                    <label>
                        <input type="radio" value="teacher" v-model="role" /> Enseignant
                    </label>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded">Se connecter</button>
                    <router-link to="/" class="px-4 py-2 border rounded">Annuler</router-link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '../components/AppLayout.vue'
import { useAuthStore } from '../stores/auth'

const name = ref('')
const role = ref('student')
const router = useRouter()
const auth = useAuthStore()

function submit() {
    if (!name.value) return alert('Entrez un nom')
    auth.login(name.value, role.value)
    router.push('/')
}
</script>

<style scoped></style>
