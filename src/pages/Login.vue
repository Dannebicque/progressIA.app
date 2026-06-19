<template>
    <div class="min-h-screen grid place-items-center bg-gradient-to-b from-indigo-50 via-background to-background px-4">
        <div class="w-full max-w-md">
            <RouterLink to="/" class="mb-6 flex items-center justify-center">
                <div class="grid place-items-center font-bold text-white">
                    <img src="@/assets/logos/logo_full.png" alt="ProgressIA" />

                </div>
            </RouterLink>

            <Card>
                <CardHeader>
                    <CardTitle class="text-xl">Connexion</CardTitle>
                    <CardDescription>Accédez à vos cours et à votre progression.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="email" type="email" autocomplete="username"
                                placeholder="vous@exemple.fr" :aria-invalid="!!error || undefined" />
                        </div>
                        <div class="space-y-2">
                            <Label for="password">Mot de passe</Label>
                            <Input id="password" v-model="password" type="password" autocomplete="current-password"
                                placeholder="••••••••" :aria-invalid="!!error || undefined" />
                        </div>
                        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
                        <Button type="submit" class="w-full" :disabled="loading">
                            <IconLoader2 v-if="loading" class="size-4 animate-spin" />
                            {{ loading ? 'Connexion…' : 'Se connecter' }}
                        </Button>
                    </form>

                    <div class="mt-6 rounded-lg bg-muted/60 p-3 text-xs text-muted-foreground">
                        <p class="mb-1 font-medium text-foreground">Comptes de démonstration</p>
                        <Button type="button" variant="ghost" size="sm"
                            class="h-auto w-full justify-start px-2 py-1 font-normal text-muted-foreground" @click="fill('teacher')">
                            Enseignant — teacher@progressia.test / teacher
                        </Button>
                        <Button type="button" variant="ghost" size="sm"
                            class="h-auto w-full justify-start px-2 py-1 font-normal text-muted-foreground" @click="fill('student')">
                            Étudiant — student@progressia.test / student
                        </Button>
                    </div>
                </CardContent>
                <CardFooter class="justify-center text-sm text-muted-foreground">
                    Pas encore de compte ?
                    <RouterLink to="/register" class="ml-1 font-medium text-primary hover:underline">Créer un compte</RouterLink>
                </CardFooter>
            </Card>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { IconLoader2 } from '@tabler/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { ApiError } from '@/api/client'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

function fill(role: 'teacher' | 'student') {
    email.value = `${role}@progressia.test`
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
        const redirect = (route.query.redirect as string) || '/'
        router.push(redirect)
    } catch (e) {
        error.value = e instanceof ApiError && e.status === 401
            ? 'Identifiants invalides.'
            : 'Connexion impossible. Réessayez.'
    } finally {
        loading.value = false
    }
}
</script>
