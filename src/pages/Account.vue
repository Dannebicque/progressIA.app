<template>
    <AppLayout>
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">Mon compte</h1>
                <p class="text-sm text-muted-foreground">Gérez vos informations personnelles et votre mot de passe.</p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Profil</CardTitle>
                    <CardDescription>Ces informations sont visibles dans l'application.</CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="space-y-2">
                            <Label for="name">Nom</Label>
                            <Input id="name" v-model="name" autocomplete="name"
                                :aria-invalid="!!fieldErrors.name || undefined" />
                            <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
                        </div>
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="email" type="email" autocomplete="username"
                                :aria-invalid="!!fieldErrors.email || undefined" />
                            <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email }}</p>
                        </div>

                        <Separator />

                        <div class="space-y-2">
                            <Label for="password">Nouveau mot de passe</Label>
                            <Input id="password" v-model="password" type="password" autocomplete="new-password"
                                placeholder="Laisser vide pour ne pas changer"
                                :aria-invalid="!!fieldErrors.password || undefined" />
                            <p v-if="fieldErrors.password" class="text-xs text-destructive">{{ fieldErrors.password }}</p>
                        </div>

                        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>

                        <div class="flex items-center gap-3">
                            <Button type="submit" :disabled="loading">
                                <IconLoader2 v-if="loading" class="size-4 animate-spin" />
                                Enregistrer
                            </Button>
                            <Badge v-if="!auth.isTeacher()" variant="secondary">Étudiant</Badge>
                            <Badge v-else variant="default">Enseignant</Badge>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { IconLoader2 } from '@tabler/icons-vue'
import AppLayout from '@/components/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'
import { ApiError } from '@/api/client'
import { showToast } from '@/composables/useToast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

const auth = useAuthStore()
const name = ref(auth.user?.name ?? '')
const email = ref(auth.user?.email ?? '')
const password = ref('')
const error = ref('')
const fieldErrors = ref<Record<string, string>>({})
const loading = ref(false)

async function submit() {
    error.value = ''
    fieldErrors.value = {}
    loading.value = true
    try {
        const payload: { name?: string; email?: string; password?: string } = {
            name: name.value,
            email: email.value,
        }
        if (password.value) payload.password = password.value
        await auth.updateAccount(payload)
        password.value = ''
        showToast('Compte mis à jour')
    } catch (e) {
        if (e instanceof ApiError && (e.status === 422 || e.status === 409)) {
            const body = e.body as { errors?: Record<string, string> }
            fieldErrors.value = body?.errors || {}
            if (!Object.keys(fieldErrors.value).length) error.value = 'Mise à jour impossible.'
        } else {
            error.value = 'Mise à jour impossible. Réessayez.'
        }
    } finally {
        loading.value = false
    }
}
</script>
