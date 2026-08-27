<template>
    <div class="min-h-screen grid place-items-center bg-gradient-to-b from-indigo-50 via-background to-background px-4 py-10">
        <div class="w-full max-w-md">
            <RouterLink to="/" class="mb-6 flex items-center justify-center gap-3">
                <div class="grid size-11 place-items-center rounded-xl bg-brand-gradient font-bold text-white">PR</div>
                <span class="text-xl font-semibold tracking-tight">ProgressIA</span>
            </RouterLink>

            <Card>
                <CardHeader>
                    <CardTitle class="text-xl">Créer un compte</CardTitle>
                    <CardDescription>Rejoignez ProgressIA en tant qu'étudiant.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="name">Nom</Label>
                            <Input id="name" v-model="name" placeholder="Votre nom" autocomplete="name"
                                :aria-invalid="!!fieldErrors.name || undefined" />
                            <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="email" type="email" placeholder="vous@exemple.fr"
                                autocomplete="username" :aria-invalid="!!fieldErrors.email || undefined" />
                            <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="password">Mot de passe</Label>
                            <Input id="password" v-model="password" type="password" placeholder="6 caractères minimum"
                                autocomplete="new-password" :aria-invalid="!!fieldErrors.password || undefined" />
                            <p v-if="fieldErrors.password" class="text-xs text-destructive">{{ fieldErrors.password }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="invitationCode">Code d'invitation (Optionnel)</Label>
                            <Input id="invitationCode" v-model="invitationCode" placeholder="Ex: BORD2026"
                                :aria-invalid="!!fieldErrors.invitationCode || undefined" />
                            <p v-if="fieldErrors.invitationCode" class="text-xs text-destructive">{{ fieldErrors.invitationCode }}</p>
                        </div>
                        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
                        <Button type="submit" class="w-full" :disabled="loading">
                            <IconLoader2 v-if="loading" class="size-4 animate-spin" />
                            {{ loading ? 'Création…' : 'Créer mon compte' }}
                        </Button>
                    </form>
                </CardContent>
                <CardFooter class="justify-center text-sm text-muted-foreground">
                    Déjà inscrit ?
                    <RouterLink to="/login" class="ml-1 font-medium text-primary hover:underline">Se connecter</RouterLink>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { IconLoader2 } from '@tabler/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { ApiError } from '@/api/client'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const name = ref('')
const email = ref('')
const password = ref('')
const invitationCode = ref('')
const error = ref('')
const fieldErrors = ref<Record<string, string>>({})
const loading = ref(false)
const router = useRouter()
const auth = useAuthStore()

async function submit() {
    error.value = ''
    fieldErrors.value = {}
    loading.value = true
    try {
        await auth.register(name.value, email.value, password.value, invitationCode.value.trim())
        router.push('/')
    } catch (e) {
        if (e instanceof ApiError && (e.status === 422 || e.status === 409)) {
            const body = e.body as { errors?: Record<string, string> }
            fieldErrors.value = body?.errors || {}
            if (!Object.keys(fieldErrors.value).length) error.value = 'Inscription impossible.'
        } else {
            error.value = 'Inscription impossible. Réessayez.'
        }
    } finally {
        loading.value = false
    }
}
</script>
