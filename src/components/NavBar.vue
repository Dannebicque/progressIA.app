<template>
    <header class="bg-brand-gradient text-white shadow-sm">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
            <!-- Brand -->
            <RouterLink to="/" class="flex items-center gap-3 rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                <div class="grid size-10 place-items-center rounded-xl bg-white/20 font-bold backdrop-blur">PF</div>
                <div class="hidden sm:block leading-tight">
                    <div class="font-semibold tracking-tight">PedagoFlow</div>
                    <div class="text-xs text-white/80">Concevez. Suivez. Gamifiez.</div>
                </div>
            </RouterLink>

            <!-- Search -->
            <form class="relative hidden md:flex flex-1 max-w-lg" @submit.prevent="submitSearch">
                <IconSearch class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-white/70" />
                <input v-model="q" type="search" placeholder="Rechercher un cours, une séance…"
                    class="w-full rounded-full bg-white/15 py-2 pl-9 pr-4 text-sm text-white placeholder-white/70 outline-none ring-1 ring-white/20 transition focus:bg-white/25 focus:ring-2 focus:ring-white/70" />
            </form>

            <!-- Nav + account -->
            <div class="flex items-center gap-2">
                <RouterLink to="/courses" :class="navLink">Catalogue</RouterLink>
                <RouterLink v-if="auth.isTeacher()" to="/backoffice" :class="navLink">Back-office</RouterLink>
                <RouterLink to="/dashboard/student" :class="navLink">Mon tableau</RouterLink>

                <template v-if="auth.user">
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="rounded-full outline-none focus-visible:ring-2 focus-visible:ring-white/70">
                            <Avatar class="size-9 ring-2 ring-white/40">
                                <AvatarImage v-if="auth.user?.avatar" :src="`${apiBaseUrl}/${auth.user.avatar}`" alt="Avatar" class="object-cover" />
                                <AvatarFallback class="bg-white font-semibold text-indigo-700">
                                    {{ initials }}
                                </AvatarFallback>
                            </Avatar>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <DropdownMenuLabel class="flex flex-col">
                                <span class="truncate">{{ auth.user.name }}</span>
                                <span class="text-xs font-normal text-muted-foreground">
                                    {{ auth.isTeacher() ? 'Enseignant' : 'Étudiant' }}
                                </span>
                            </DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem as-child>
                                <RouterLink to="/dashboard/student" class="cursor-pointer">
                                    <IconLayoutDashboard class="size-4" /> Mon tableau
                                </RouterLink>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <RouterLink :to="auth.isTeacher() ? '/stats/teacher' : '/stats/student'" class="cursor-pointer">
                                    <IconChartBar class="size-4" /> Statistiques
                                </RouterLink>
                            </DropdownMenuItem>
                            <DropdownMenuItem as-child>
                                <RouterLink to="/account" class="cursor-pointer">
                                    <IconUserCog class="size-4" /> Mon compte
                                </RouterLink>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem variant="destructive" class="cursor-pointer" @select="logout">
                                <IconLogout class="size-4" /> Se déconnecter
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>
                <template v-else>
                    <RouterLink to="/login">
                        <Button size="sm" class="rounded-full bg-white text-indigo-700 hover:bg-white/90">Connexion</Button>
                    </RouterLink>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { IconSearch, IconLogout, IconLayoutDashboard, IconChartBar, IconUserCog } from '@tabler/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'

const auth = useAuthStore()
const router = useRouter()
const q = ref('')
const apiBaseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')

const navLink =
    'hidden sm:inline-flex items-center rounded-full px-3 py-1.5 text-sm font-medium text-white/90 transition hover:bg-white/15 outline-none focus-visible:ring-2 focus-visible:ring-white/70'

const initials = computed(() => (auth.user?.name || '?').trim().charAt(0).toUpperCase())

function submitSearch() {
    router.push({ path: '/courses', query: q.value ? { q: q.value } : {} })
}

function logout() {
    auth.logout()
    router.push('/')
}
</script>

<style scoped></style>
