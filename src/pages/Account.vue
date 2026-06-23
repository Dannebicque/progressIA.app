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
                    <form class="space-y-6" @submit.prevent="submit">
                        <!-- Avatar section -->
                        <div class="flex flex-col items-center gap-4 sm:flex-row pb-6 border-b border-border/40">
                            <div class="relative size-24 shrink-0 overflow-hidden rounded-full border border-border bg-muted flex items-center justify-center">
                                <img v-if="avatarPreviewUrl" :src="avatarPreviewUrl" alt="Avatar" class="size-full object-cover" />
                                <div v-else class="grid size-full place-items-center text-2xl font-bold bg-indigo-50 text-indigo-700">
                                    {{ initials }}
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label class="text-sm font-medium">Image de profil</Label>
                                <div class="flex items-center gap-2">
                                    <Button type="button" variant="outline" size="sm" @click="triggerFileInput">
                                        Choisir une image
                                    </Button>
                                    <Button v-if="auth.user?.avatar || avatarBase64" type="button" variant="ghost" size="sm" class="text-destructive hover:bg-destructive/10" @click="removeAvatar">
                                        Supprimer
                                    </Button>
                                </div>
                                <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onAvatarChange" />
                                <p class="text-xs text-muted-foreground">Formats acceptés : PNG, JPG, JPEG, WEBP. Max 2Mo.</p>
                            </div>
                        </div>

                        <!-- Read-only Alert for Students -->
                        <div v-if="!auth.isTeacher()" class="rounded-lg bg-indigo-50/50 border border-indigo-100 p-3 text-xs text-indigo-800 flex items-start gap-2">
                            <svg class="size-4 shrink-0 fill-indigo-700 mt-0.5" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                            <span>Les informations de profil (nom, email), de groupe et d'établissement sont gérées par votre établissement et ne peuvent pas être modifiées directement.</span>
                        </div>

                        <div class="space-y-2">
                            <Label for="name">Nom</Label>
                            <Input id="name" v-model="name" autocomplete="name"
                                :disabled="!auth.isTeacher()"
                                :class="{ 'bg-muted/50 cursor-not-allowed': !auth.isTeacher() }"
                                :aria-invalid="!!fieldErrors.name || undefined" />
                            <p v-if="fieldErrors.name" class="text-xs text-destructive">{{ fieldErrors.name }}</p>
                        </div>
                        
                        <div class="space-y-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="email" type="email" autocomplete="username"
                                :disabled="!auth.isTeacher()"
                                :class="{ 'bg-muted/50 cursor-not-allowed': !auth.isTeacher() }"
                                :aria-invalid="!!fieldErrors.email || undefined" />
                            <p v-if="fieldErrors.email" class="text-xs text-destructive">{{ fieldErrors.email }}</p>
                        </div>

                        <!-- Student group, year, institution read-only fields -->
                        <div v-if="!auth.isTeacher()" class="grid gap-4 sm:grid-cols-3 pt-2">
                            <div class="space-y-2">
                                <Label>Groupe</Label>
                                <Input :model-value="auth.user?.studentGroup || 'Non assigné'" disabled class="bg-muted/50 cursor-not-allowed" />
                            </div>
                            <div class="space-y-2">
                                <Label>Année</Label>
                                <Input :model-value="auth.user?.studentYear || 'Non assigné'" disabled class="bg-muted/50 cursor-not-allowed" />
                            </div>
                            <div class="space-y-2">
                                <Label>Établissement</Label>
                                <Input :model-value="auth.user?.studentInstitution || 'Non renseigné'" disabled class="bg-muted/50 cursor-not-allowed" />
                            </div>
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
import { ref, computed } from 'vue'
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

const apiBaseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')
const avatarBase64 = ref<string | null>(null)
const fileInput = ref<HTMLInputElement | null>(null)

const avatarPreviewUrl = computed(() => {
    if (avatarBase64.value === '') return null // explicitly removed
    if (avatarBase64.value) return avatarBase64.value // new base64 preview
    if (auth.user?.avatar) return `${apiBaseUrl}/${auth.user.avatar}` // existing from server
    return null
})

const initials = computed(() => {
    if (!auth.user?.name) return '?'
    return auth.user.name.split(' ').map((n) => n[0]).join('').toUpperCase().slice(0, 2)
})

function triggerFileInput() {
    fileInput.value?.click()
}

function onAvatarChange(e: Event) {
    const target = e.target as HTMLInputElement
    if (target.files && target.files[0]) {
        const file = target.files[0]
        if (file.size > 2 * 1024 * 1024) {
            showToast('L\'image dépasse la taille maximale de 2Mo', 'error')
            return
        }
        const reader = new FileReader()
        reader.onload = (event) => {
            avatarBase64.value = event.target?.result as string
        }
        reader.readAsDataURL(file)
    }
}

function removeAvatar() {
    avatarBase64.value = '' // empty string denotes avatar deletion in backend
    if (fileInput.value) fileInput.value.value = ''
}

async function submit() {
    error.value = ''
    fieldErrors.value = {}
    loading.value = true
    try {
        const payload: { name?: string; email?: string; password?: string; avatar?: string | null } = {}
        
        if (auth.isTeacher()) {
            payload.name = name.value
            payload.email = email.value
        }
        
        if (avatarBase64.value !== null) {
            payload.avatar = avatarBase64.value
        }
        
        if (password.value) {
            payload.password = password.value
        }

        await auth.updateAccount(payload)
        avatarBase64.value = null // reset preview state
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
