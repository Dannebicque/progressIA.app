<template>
  <div class="min-h-screen flex bg-background text-foreground transition-colors duration-300">
    <!-- Backdrop for mobile drawer -->
    <div
      v-if="isMobileOpen"
      class="fixed inset-0 z-40 bg-black/40 backdrop-blur-xs md:hidden"
      @click="isMobileOpen = false"
    ></div>

    <!-- Sidebar Panel -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-50 flex flex-col bg-card border-r border-border transition-all duration-300 md:static md:translate-x-0',
        isSidebarCollapsed ? 'w-16' : 'w-64',
        isMobileOpen ? 'translate-x-0' : '-translate-x-full'
      ]"
    >
      <!-- Brand Header -->
      <div class="h-14 border-b border-border flex items-center justify-between px-4">
        <RouterLink to="/backoffice" class="flex items-center gap-3 outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg">
          <div class="grid size-8 place-items-center rounded-lg bg-primary text-primary-foreground font-bold shrink-0">
                                <img src="@/assets/logos/logo_icone.png" alt="ProgressIA" class="w-8 h-8" />

          </div>
          <div v-if="!isSidebarCollapsed" class="leading-tight select-none">
            <span class="font-bold tracking-tight text-sm block">ProgressIA</span>
            <span class="text-[10px] text-muted-foreground block">Admin / Profs</span>
          </div>
        </RouterLink>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 py-4 px-2 space-y-6 overflow-y-auto select-none">
        <!-- Main Navigation Group -->
        <div class="space-y-1">
          <div v-if="!isSidebarCollapsed" class="px-3 text-[10px] font-bold text-muted-foreground uppercase tracking-wider mb-2">
            Tableau de Bord
          </div>
          <RouterLink
            to="/backoffice"
            :class="[
              'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200 outline-none focus-visible:ring-1 focus-visible:ring-primary',
              isRouteActive('/backoffice', true)
                ? 'bg-primary/10 text-primary font-semibold'
                : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
            ]"
            title="Accueil du Back-office"
            @click="isMobileOpen = false"
          >
            <IconLayoutDashboard class="size-5 shrink-0" />
            <span v-if="!isSidebarCollapsed" class="truncate">Accueil</span>
          </RouterLink>
        </div>

        <!-- Pedagogy Group -->
        <div class="space-y-1">
          <div v-if="!isSidebarCollapsed" class="px-3 text-[10px] font-bold text-muted-foreground uppercase tracking-wider mb-2">
            Gestion Pédagogique
          </div>
          
          <!-- Collapsible Courses Submenu -->
          <div class="space-y-1">
            <div class="flex items-center justify-between group/header">
              <button
                @click="isCoursesExpanded = !isCoursesExpanded"
                class="flex-1 flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent/50 hover:text-foreground transition-all outline-none focus-visible:ring-1 focus-visible:ring-primary text-left cursor-pointer"
              >
                <IconBook class="size-5 shrink-0" />
                <span v-if="!isSidebarCollapsed" class="truncate flex-1">Cours</span>
                <IconChevronDown
                  v-if="!isSidebarCollapsed"
                  :class="['size-4 shrink-0 transition-transform duration-200', isCoursesExpanded ? 'rotate-180' : '']"
                />
              </button>
              <button
                v-if="!isSidebarCollapsed"
                @click.stop="openCreateModal"
                class="opacity-0 group-hover/header:opacity-100 focus:opacity-100 inline-flex size-7 items-center justify-center rounded-md hover:bg-accent text-muted-foreground hover:text-foreground transition-all mr-1 cursor-pointer"
                title="Nouveau cours"
              >
                <IconPlus class="size-4" />
              </button>
            </div>

            <!-- Course items list -->
            <div
              v-if="isCoursesExpanded && !isSidebarCollapsed && courses.length"
              class="pl-4 ml-3 border-l border-border space-y-1 py-1"
            >
              <div
                v-for="c in courses"
                :key="c.id"
                class="group/item flex items-center justify-between rounded-md transition"
                :class="isCourseSelected(c.id) ? 'bg-primary/10 font-semibold' : 'hover:bg-accent/50'"
              >
                <button
                  class="flex-1 flex items-center gap-2 truncate px-3 py-1.5 text-left text-xs text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                  @click="selectCourseAndNavigate(c.id)"
                >
                  <span
                    class="inline-block size-2 rounded-full shrink-0 animate-pulse"
                    :style="{ backgroundColor: c.accentColor || '#7c3aed' }"
                  ></span>
                  <span class="truncate flex-1" :class="isCourseSelected(c.id) ? 'text-primary font-semibold' : ''" :title="c.title">{{ c.title }}</span>
                </button>
                <button
                  @click.stop="openConfigModal(c)"
                  class="opacity-0 group-hover/item:opacity-100 focus:opacity-100 inline-flex size-6 items-center justify-center rounded text-muted-foreground hover:text-foreground hover:bg-accent/80 transition-all mr-1 cursor-pointer"
                  title="Configurer le cours"
                >
                  <IconSettings class="size-3.5" />
                </button>
              </div>
            </div>
          </div>

          <RouterLink
            to="/stats/teacher"
            :class="[
              'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200 outline-none focus-visible:ring-1 focus-visible:ring-primary',
              isRouteActive('/stats/teacher')
                ? 'bg-primary/10 text-primary font-semibold'
                : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
            ]"
            title="Statistiques de cours"
            @click="isMobileOpen = false"
          >
            <IconChartBar class="size-5 shrink-0" />
            <span v-if="!isSidebarCollapsed" class="truncate">Statistiques</span>
          </RouterLink>
        </div>

        <!-- Administration Group -->
        <div class="space-y-1">
          <div v-if="!isSidebarCollapsed" class="px-3 text-[10px] font-bold text-muted-foreground uppercase tracking-wider mb-2">
            Administration
          </div>

          <RouterLink
            to="/backoffice/students"
            :class="[
              'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200 outline-none focus-visible:ring-1 focus-visible:ring-primary',
              isRouteActive('/backoffice/students')
                ? 'bg-primary/10 text-primary font-semibold'
                : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
            ]"
            title="Gérer les étudiants"
            @click="isMobileOpen = false"
          >
            <IconUsers class="size-5 shrink-0" />
            <span v-if="!isSidebarCollapsed" class="truncate">Étudiants</span>
          </RouterLink>

          <RouterLink
            to="/backoffice/users"
            :class="[
              'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200 outline-none focus-visible:ring-1 focus-visible:ring-primary',
              isRouteActive('/backoffice/users')
                ? 'bg-primary/10 text-primary font-semibold'
                : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground'
            ]"
            title="Gérer les comptes utilisateurs"
            @click="isMobileOpen = false"
          >
            <IconUserShield class="size-5 shrink-0" />
            <span v-if="!isSidebarCollapsed" class="truncate">Utilisateurs</span>
          </RouterLink>
        </div>
      </nav>

      <!-- Sidebar Footer -->
      <div class="p-2 border-t border-border bg-card/50 select-none space-y-1">
        <!-- Return to Main Site Button -->
        <RouterLink
          to="/"
          class="flex items-center gap-3 rounded-lg px-3 py-2 text-xs font-semibold text-amber-600 dark:text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-950/20 transition-all outline-none focus-visible:ring-1 focus-visible:ring-amber-500"
          title="Retour au site public"
        >
          <IconArrowLeft class="size-4 shrink-0" />
          <span v-if="!isSidebarCollapsed" class="truncate">Retour au site</span>
        </RouterLink>

        <!-- User Brief info -->
        <div v-if="!isSidebarCollapsed && auth.user" class="flex items-center gap-2 p-2 rounded-lg bg-accent/30 border border-border/40 mt-1">
          <Avatar class="size-8 ring-1 ring-border">
            <AvatarImage v-if="auth.user?.avatar" :src="`${apiBaseUrl}/${auth.user.avatar}`" alt="Avatar" class="object-cover" />
            <AvatarFallback class="bg-primary text-primary-foreground text-xs font-bold">
              {{ initials }}
            </AvatarFallback>
          </Avatar>
          <div class="min-w-0 flex-1 leading-tight">
            <div class="text-xs font-semibold truncate">{{ auth.user.name }}</div>
            <div class="text-[9px] text-muted-foreground truncate">{{ auth.user.email }}</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Content Workspace -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
      <!-- Top header bar -->
      <header class="h-14 border-b border-border bg-card flex items-center justify-between px-6 shrink-0 z-10 select-none">
        <div class="flex items-center gap-4">
          <!-- Sidebar Toggle (hamburger on mobile, chevron on desktop) -->
          <button
            @click="isMobileOpen = !isMobileOpen"
            class="md:hidden inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent outline-none focus-visible:ring-2 focus-visible:ring-primary cursor-pointer"
            aria-label="Menu"
          >
            <IconMenu class="size-5" />
          </button>
          
          <button
            @click="toggleSidebar"
            class="hidden md:inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground hover:bg-accent outline-none focus-visible:ring-2 focus-visible:ring-primary cursor-pointer"
            :aria-label="isSidebarCollapsed ? 'Développer' : 'Réduire'"
          >
            <IconLayoutSidebarLeftCollapse v-if="!isSidebarCollapsed" class="size-5" />
            <IconLayoutSidebarLeftExpand v-else class="size-5" />
          </button>

          <!-- Breadcrumbs / Fil d'Ariane -->
          <nav aria-label="Breadcrumb" class="hidden sm:flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
            <RouterLink to="/backoffice" class="hover:text-foreground transition-colors">Admin</RouterLink>
            <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
              <span class="text-muted-foreground/50">/</span>
              <RouterLink
                v-if="idx < breadcrumbs.length - 1"
                :to="crumb.path"
                class="hover:text-foreground transition-colors"
              >
                {{ crumb.label }}
              </RouterLink>
              <span v-else class="text-foreground font-semibold truncate max-w-40">{{ crumb.label }}</span>
            </template>
          </nav>
        </div>

        <div class="flex items-center gap-2">
          <!-- Light/Dark Mode switch -->
          <button
            @click="toggleTheme"
            class="inline-flex size-9 items-center justify-center rounded-lg text-muted-foreground transition hover:bg-accent outline-none focus-visible:ring-2 focus-visible:ring-primary cursor-pointer"
            aria-label="Changer le thème"
          >
            <IconSun v-if="isDark" class="size-5" />
            <IconMoon v-else class="size-5" />
          </button>

          <!-- Profile dropdown menu -->
          <template v-if="auth.user">
            <DropdownMenu>
              <DropdownMenuTrigger class="rounded-full outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <Avatar class="size-8 ring-2 ring-primary/20">
                  <AvatarImage v-if="auth.user?.avatar" :src="`${apiBaseUrl}/${auth.user.avatar}`" alt="Avatar" class="object-cover" />
                  <AvatarFallback class="bg-primary text-primary-foreground font-semibold text-xs">
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
                    <IconLayoutDashboard class="size-4 mr-2" /> Mon tableau
                  </RouterLink>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <RouterLink to="/account" class="cursor-pointer">
                    <IconUserCog class="size-4 mr-2" /> Mon compte
                  </RouterLink>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <RouterLink to="/" class="cursor-pointer text-amber-600 dark:text-amber-500">
                    <IconArrowLeft class="size-4 mr-2" /> Retour au site
                  </RouterLink>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem variant="destructive" class="cursor-pointer" @select="logout">
                  <IconLogout class="size-4 mr-2" /> Se déconnecter
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </template>
        </div>
      </header>

      <!-- Scrollable workspace content -->
      <main class="flex-1 overflow-y-auto bg-slate-50/40 dark:bg-zinc-950/20 p-6 md:p-8">
        <slot />
      </main>
    </div>

    <!-- Create Course Dialog -->
    <Dialog :open="isCreateOpen" @update:open="(v: boolean) => (isCreateOpen = v)">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Créer un nouveau cours</DialogTitle>
          <DialogDescription>
            Saisissez les informations et la scénarisation globale de votre nouveau cours.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2 text-sm">
          <div class="space-y-1">
            <Label for="createTitle" class="text-xs font-semibold">Titre du cours</Label>
            <Input id="createTitle" v-model="courseForm.title" placeholder="Ex: Algorithmique et structures de données" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <Label for="createSemester" class="text-xs font-semibold">Semestre</Label>
              <Input id="createSemester" v-model="courseForm.semester" placeholder="Ex: S1" />
            </div>
            <div class="space-y-1">
              <Label for="createLevel" class="text-xs font-semibold">Niveau</Label>
              <Input id="createLevel" v-model="courseForm.level" placeholder="Ex: Débutant" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <Label for="createTheme" class="text-xs font-semibold">Thème</Label>
              <Input id="createTheme" v-model="courseForm.theme" placeholder="Ex: Général" />
            </div>
            <div class="space-y-1">
              <Label for="createCategory" class="text-xs font-semibold">Catégorie</Label>
              <Select id="createCategory" v-model="courseForm.category">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="other">Autre</SelectItem>
                  <SelectItem value="back">Back</SelectItem>
                  <SelectItem value="front">Front</SelectItem>
                  <SelectItem value="fullstack">Fullstack</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-1">
            <Label for="createAccent" class="text-xs font-semibold">Couleur d'accent</Label>
            <div class="flex items-center gap-2">
              <input
                id="createAccent"
                type="color"
                v-model="courseForm.accentColor"
                class="size-8 cursor-pointer rounded border bg-transparent shrink-0"
              />
              <Input v-model="courseForm.accentColor" class="font-mono text-xs h-8" />
            </div>
          </div>

          <div class="space-y-1">
            <Label for="createContext" class="text-xs font-semibold">Pitch / Description courte</Label>
            <textarea
              id="createContext"
              v-model="courseForm.context"
              rows="2"
              placeholder="Description rapide..."
              class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
          </div>

          <div class="space-y-1">
            <Label for="createScenario" class="text-xs font-semibold">Scénario de cours (Histoire / Univers)</Label>
            <textarea
              id="createScenario"
              v-model="courseForm.scenario"
              rows="3"
              placeholder="Saisissez la scénarisation ou le contexte narratif du cours..."
              class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <Checkbox id="createVisible" :checked="courseForm.visible" @update:checked="(val: boolean) => { courseForm.visible = val; }" />
            <Label for="createVisible" class="text-xs font-semibold cursor-pointer">Visible pour les étudiants</Label>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="isCreateOpen = false">Annuler</Button>
          <Button @click="submitCreateCourse">Créer le cours</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Configure Course Dialog -->
    <Dialog :open="isConfigOpen" @update:open="(v: boolean) => (isConfigOpen = v)">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Paramètres du cours</DialogTitle>
          <DialogDescription>
            Configurez les détails généraux, dupliquez ou supprimez ce cours.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2 text-sm">
          <div class="space-y-1">
            <Label for="configTitle" class="text-xs font-semibold">Titre du cours</Label>
            <Input id="configTitle" v-model="courseForm.title" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <Label for="configSemester" class="text-xs font-semibold">Semestre</Label>
              <Input id="configSemester" v-model="courseForm.semester" />
            </div>
            <div class="space-y-1">
              <Label for="configLevel" class="text-xs font-semibold">Niveau</Label>
              <Input id="configLevel" v-model="courseForm.level" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <Label for="configTheme" class="text-xs font-semibold">Thème</Label>
              <Input id="configTheme" v-model="courseForm.theme" />
            </div>
            <div class="space-y-1">
              <Label for="configCategory" class="text-xs font-semibold">Catégorie</Label>
              <Select id="configCategory" v-model="courseForm.category">
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                  <SelectItem value="other">Autre</SelectItem>
                  <SelectItem value="back">Back</SelectItem>
                  <SelectItem value="front">Front</SelectItem>
                  <SelectItem value="fullstack">Fullstack</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-1">
            <Label for="configAccent" class="text-xs font-semibold">Couleur d'accent</Label>
            <div class="flex items-center gap-2">
              <input
                id="configAccent"
                type="color"
                v-model="courseForm.accentColor"
                class="size-8 cursor-pointer rounded border bg-transparent shrink-0"
              />
              <Input v-model="courseForm.accentColor" class="font-mono text-xs h-8" />
            </div>
          </div>

          <div class="space-y-1">
            <Label for="configContext" class="text-xs font-semibold">Pitch / Description courte</Label>
            <textarea
              id="configContext"
              v-model="courseForm.context"
              rows="2"
              class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
          </div>

          <div class="space-y-1">
            <Label for="configScenario" class="text-xs font-semibold">Scénario de cours</Label>
            <textarea
              id="configScenario"
              v-model="courseForm.scenario"
              rows="3"
              class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <Checkbox id="configVisible" :checked="courseForm.visible" @update:checked="(val: boolean) => { courseForm.visible = val; }" />
            <Label for="configVisible" class="text-xs font-semibold cursor-pointer">Visible pour les étudiants</Label>
          </div>

          <Separator class="my-4" />

          <div class="flex gap-2">
            <Button variant="outline" class="flex-1 text-xs" @click="duplicateConfigCourse">
              <IconCopy class="size-3.5 mr-1" /> Dupliquer
            </Button>
            <Button variant="destructive" class="flex-1 text-xs" @click="deleteConfigCourse">
              <IconTrash class="size-3.5 mr-1" /> Supprimer
            </Button>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="isConfigOpen = false">Annuler</Button>
          <Button @click="submitConfigCourse">Enregistrer</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- UI Globals (Toasts & Confirms) -->
    <Toaster rich-colors position="bottom-right" />
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  IconLayoutDashboard,
  IconBook,
  IconChartBar,
  IconUsers,
  IconUserShield,
  IconArrowLeft,
  IconMenu,
  IconLayoutSidebarLeftCollapse,
  IconLayoutSidebarLeftExpand,
  IconSun,
  IconMoon,
  IconLogout,
  IconUserCog,
  IconChevronDown,
  IconPlus,
  IconSettings,
  IconCopy,
  IconTrash
} from '@tabler/icons-vue'
import { useAuthStore } from '@/stores/auth'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import { Toaster } from '@/components/ui/sonner'
import ConfirmDialog from './ConfirmDialog.vue'
import { useCoursesStore } from '@/stores/courses'
import { showToast } from '@/composables/useToast'
import { confirmDialog } from '@/composables/useConfirm'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Separator } from '@/components/ui/separator'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const isSidebarCollapsed = ref(localStorage.getItem('bo-sidebar-collapsed') === 'true')
const isMobileOpen = ref(false)
const isDark = ref(false)

const apiBaseUrl = (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000').replace(/\/$/, '')

onMounted(() => {
  isDark.value = document.documentElement.classList.contains('dark')
})

function toggleSidebar() {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
  localStorage.setItem('bo-sidebar-collapsed', String(isSidebarCollapsed.value))
}

function toggleTheme() {
  isDark.value = !isDark.value
  if (isDark.value) {
    document.documentElement.classList.add('dark')
    localStorage.setItem('theme', 'dark')
  } else {
    document.documentElement.classList.remove('dark')
    localStorage.setItem('theme', 'light')
  }
}

function logout() {
  auth.logout()
  router.push('/')
}

const initials = computed(() => (auth.user?.name || '?').trim().charAt(0).toUpperCase())

function isRouteActive(path: string, exact = false) {
  if (exact) {
    return route.path === path
  }
  return route.path.startsWith(path)
}

const breadcrumbs = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  const crumbs = []

  if (parts.length > 0) {
    if (parts[0] === 'backoffice') {
      crumbs.push({ label: 'Backoffice', path: '/backoffice' })
    } else if (parts[0] === 'stats') {
      crumbs.push({ label: 'Statistiques', path: '/stats/teacher' })
    }
  }

  if (parts.length > 1) {
    if (parts[1] === 'courses') {
      crumbs.push({ label: 'Cours', path: '/backoffice/courses' })
    } else if (parts[1] === 'users') {
      crumbs.push({ label: 'Utilisateurs', path: '/backoffice/users' })
    } else if (parts[1] === 'students') {
      crumbs.push({ label: 'Étudiants', path: '/backoffice/students' })
    } else if (parts[1] === 'teacher') {
      crumbs.push({ label: 'Enseignant', path: '/stats/teacher' })
    }
  }

  if (parts.length > 2) {
    if (parts[1] === 'courses' && parts[3] === 'tracking') {
      crumbs.push({ label: 'Suivi de classe', path: route.path })
    } else if (parts[1] === 'courses' && parts[3] === 'storytelling') {
      crumbs.push({ label: 'Storytelling & Mails', path: route.path })
    } else if (parts[1] === 'students') {
      crumbs.push({ label: 'Fiche Étudiant', path: route.path })
    }
  }

  return crumbs
})

// Course Store and Lists
const courseStore = useCoursesStore()
onMounted(async () => {
  if (!courseStore.loaded) await courseStore.fetchCourses()
})
const courses = computed(() => courseStore.courses)

// Local UI collapse / modals
const isCoursesExpanded = ref(true)
const isCreateOpen = ref(false)
const isConfigOpen = ref(false)
const configCourseId = ref<number | string | null>(null)

// Shared Course form
const courseForm = reactive({
  title: '',
  semester: '',
  level: 'Débutant',
  theme: 'Général',
  category: 'other',
  accentColor: '#7c3aed',
  visible: true,
  context: '',
  scenario: ''
})

function isCourseSelected(id: number | string) {
  return route.path.startsWith('/backoffice/courses') && String(route.query.courseId) === String(id)
}

function selectCourseAndNavigate(id: number | string) {
  isMobileOpen.value = false
  router.push({ path: '/backoffice/courses', query: { courseId: String(id) } })
}

// Modal action functions
function openCreateModal() {
  courseForm.title = "Nouveau cours"
  courseForm.semester = "S1"
  courseForm.level = "Débutant"
  courseForm.theme = "Général"
  courseForm.category = "other"
  courseForm.accentColor = "#7c3aed"
  courseForm.visible = true
  courseForm.context = ""
  courseForm.scenario = ""
  isCreateOpen.value = true
}

async function submitCreateCourse() {
  if (!courseForm.title.trim()) {
    showToast("Le titre est requis", "error")
    return
  }
  try {
    const nc = await courseStore.createCourse({ ...courseForm })
    isCreateOpen.value = false
    showToast("Cours créé avec succès")
    selectCourseAndNavigate(nc.id)
  } catch {
    showToast("Erreur lors de la création", "error")
  }
}

// eslint-disable-next-line @typescript-eslint/no-explicit-any
function openConfigModal(c: any) {
  configCourseId.value = c.id
  courseForm.title = c.title
  courseForm.semester = c.semester || ""
  courseForm.level = c.level || ""
  courseForm.theme = c.theme || ""
  courseForm.category = c.category || "other"
  courseForm.accentColor = c.accentColor || "#7c3aed"
  courseForm.visible = c.visible !== false
  courseForm.context = c.context || ""
  courseForm.scenario = c.scenario || ""
  isConfigOpen.value = true
}

async function submitConfigCourse() {
  if (!configCourseId.value) return
  if (!courseForm.title.trim()) {
    showToast("Le titre est requis", "error")
    return
  }
  try {
    await courseStore.updateCourse(configCourseId.value, { ...courseForm })
    isConfigOpen.value = false
    showToast("Cours mis à jour")
  } catch {
    showToast("Erreur lors de la mise à jour", "error")
  }
}

async function deleteConfigCourse() {
  if (!configCourseId.value) return
  if (
    !(await confirmDialog({
      title: "Supprimer ce cours ?",
      description: "Séances, chapitres, pages et quiz de ce cours seront définitivement effacés.",
      confirmText: "Supprimer"
    }))
  ) {
    return
  }
  try {
    const deletedId = configCourseId.value
    await courseStore.deleteCourse(deletedId)
    isConfigOpen.value = false
    showToast("Cours supprimé")
    
    // Redirect if deleting the currently active course
    if (String(route.query.courseId) === String(deletedId)) {
      if (courseStore.courses[0]) {
        selectCourseAndNavigate(courseStore.courses[0].id)
      } else {
        router.push('/backoffice')
      }
    }
  } catch {
    showToast("Erreur lors de la suppression", "error")
  }
}

async function duplicateConfigCourse() {
  if (!configCourseId.value) return
  const src = courseStore.getCourse(configCourseId.value)
  if (!src) return

  try {
    isConfigOpen.value = false
    showToast("Duplication en cours...", "info")

    const nc = await courseStore.createCourse({
      title: `${src.title} (copie)`,
      theme: src.theme,
      category: src.category,
      context: src.context,
      accentColor: src.accentColor,
      level: src.level,
      scenario: src.scenario,
      semester: src.semester,
      visible: src.visible
    })

    for (const s of src.sessions || []) {
      const ns = await courseStore.addSession(nc.id, {
        title: s.title,
        pitch: s.pitch,
        visible: s.visible,
        renderConfig: s.renderConfig
      })
      for (const ch of s.chapters || []) {
        const nch = await courseStore.addChapter(ns.id, { title: ch.title, visible: ch.visible })
        for (const p of ch.pages || []) {
          await courseStore.addPage(nch.id, {
            title: p.title,
            content: p.content,
            points: p.points,
            visible: p.visible
          })
        }
        for (const ev of ch.evaluations || []) {
          await courseStore.addEvaluation(nch.id, {
            title: ev.title,
            description: ev.description,
            pointsReward: ev.pointsReward,
            visible: ev.visible
          })
        }
      }
    }

    showToast("Cours dupliqué avec succès")
    selectCourseAndNavigate(nc.id)
  } catch {
    showToast("Erreur lors de la duplication", "error")
  }
}
</script>

<style scoped>
/* Force scrolling when content overflow */
aside nav::-webkit-scrollbar {
  width: 4px;
}
aside nav::-webkit-scrollbar-thumb {
  background: var(--border);
  border-radius: 4px;
}
</style>
