<template>
  <BackofficeLayout>

    <div class="grid gap-6 lg:grid-cols-3">
      <Card>
        <CardHeader>
          <CardTitle class="text-base">{{
            editingUserId ? "Modifier l'utilisateur" : "Ajouter un utilisateur"
          }}</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="space-y-1.5">
            <Label>Nom</Label>
            <Input v-model="form.name" />
          </div>
          <div class="space-y-1.5">
            <Label>Email</Label>
            <Input v-model="form.email" type="email" />
          </div>
          <div class="space-y-1.5">
            <Label>Rôle</Label>
            <Select v-model="form.role">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="ROLE_TEACHER">Enseignant</SelectItem>
                <SelectItem value="ROLE_STUDENT">Étudiant</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-1.5">
            <Label>Statut</Label>
            <Select v-model="form.status">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="actif">Actif</SelectItem>
                <SelectItem value="inactif">Inactif</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="flex gap-2">
            <Button class="flex-1" @click="saveUser">{{
              editingUserId ? "Mettre à jour" : "Ajouter"
            }}</Button>
            <Button v-if="editingUserId" variant="outline" @click="resetForm"
              >Annuler</Button
            >
          </div>
        </CardContent>
      </Card>

      <Card class="lg:col-span-2">
        <CardHeader class="space-y-3">
          <CardTitle class="text-base">Utilisateurs</CardTitle>
          <div class="grid gap-2 md:grid-cols-2">
            <Input v-model="search" placeholder="Rechercher (nom / email)" />
            <Select v-model="roleFilter">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous les rôles</SelectItem>
                <SelectItem value="ROLE_TEACHER">Enseignant</SelectItem>
                <SelectItem value="ROLE_STUDENT">Étudiant</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardHeader>
        <CardContent class="space-y-3">
          <div
            v-for="user in filteredUsers"
            :key="user.id"
            class="rounded-lg border p-3"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="font-medium">{{ user.name }}</p>
                <p class="text-sm text-muted-foreground">{{ user.email }}</p>
              </div>
              <div class="flex items-center gap-2">
                <Badge variant="outline">{{
                  user.role === "ROLE_TEACHER" ? "Enseignant" : "Étudiant"
                }}</Badge>
                <Badge
                  :variant="user.status === 'actif' ? 'default' : 'secondary'"
                  >{{ user.status }}</Badge
                >
                <Button size="sm" variant="outline" @click="editUser(user)"
                  >Modifier</Button
                >
                <Button
                  size="sm"
                  variant="ghost"
                  class="text-destructive"
                  @click="removeUser(user.id)"
                >
                  <IconTrash class="size-4" />
                </Button>
              </div>
            </div>
          </div>
          <p
            v-if="!filteredUsers.length"
            class="py-8 text-center text-sm text-muted-foreground"
          >
            Aucun utilisateur trouvé.
          </p>
        </CardContent>
      </Card>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { IconTrash } from "@tabler/icons-vue";
import BackofficeLayout from "@/components/BackofficeLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { showToast } from "@/composables/useToast";
import { confirmDialog } from "@/composables/useConfirm";


type UserRole = "ROLE_TEACHER" | "ROLE_STUDENT";
type UserStatus = "actif" | "inactif";

interface BackofficeUser {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  status: UserStatus;
}

const users = ref<BackofficeUser[]>([
  {
    id: 1,
    name: "Camille Durand",
    email: "camille.durand@progressia.fr",
    role: "ROLE_TEACHER",
    status: "actif",
  },
  {
    id: 2,
    name: "Léa Martin",
    email: "lea.martin@progressia.fr",
    role: "ROLE_STUDENT",
    status: "actif",
  },
  {
    id: 3,
    name: "Hugo Petit",
    email: "hugo.petit@progressia.fr",
    role: "ROLE_STUDENT",
    status: "inactif",
  },
]);

const editingUserId = ref<number | null>(null);
const search = ref("");
const roleFilter = ref<"all" | UserRole>("all");
const form = reactive<{
  name: string;
  email: string;
  role: UserRole;
  status: UserStatus;
}>({
  name: "",
  email: "",
  role: "ROLE_STUDENT",
  status: "actif",
});

const filteredUsers = computed(() => {
  const q = search.value.trim().toLowerCase();
  return users.value.filter((user) => {
    const roleMatch =
      roleFilter.value === "all" || user.role === roleFilter.value;
    const queryMatch =
      !q ||
      user.name.toLowerCase().includes(q) ||
      user.email.toLowerCase().includes(q);
    return roleMatch && queryMatch;
  });
});

function resetForm() {
  editingUserId.value = null;
  form.name = "";
  form.email = "";
  form.role = "ROLE_STUDENT";
  form.status = "actif";
}

function editUser(user: BackofficeUser) {
  editingUserId.value = user.id;
  form.name = user.name;
  form.email = user.email;
  form.role = user.role;
  form.status = user.status;
}

function saveUser() {
  if (!form.name.trim() || !form.email.trim()) {
    showToast("Nom et email requis");
    return;
  }
  if (editingUserId.value) {
    const existing = users.value.find((u) => u.id === editingUserId.value);
    if (!existing) return;
    existing.name = form.name.trim();
    existing.email = form.email.trim();
    existing.role = form.role;
    existing.status = form.status;
    showToast("Utilisateur mis à jour");
    resetForm();
    return;
  }

  users.value.unshift({
    id: Math.max(0, ...users.value.map((u) => u.id)) + 1,
    name: form.name.trim(),
    email: form.email.trim(),
    role: form.role,
    status: form.status,
  });
  showToast("Utilisateur ajouté");
  resetForm();
}

async function removeUser(id: number) {
  if (
    !(await confirmDialog({
      title: "Supprimer cet utilisateur ?",
      confirmText: "Supprimer",
    }))
  )
    return;
  users.value = users.value.filter((u) => u.id !== id);
  if (editingUserId.value === id) resetForm();
  showToast("Utilisateur supprimé");
}
</script>
