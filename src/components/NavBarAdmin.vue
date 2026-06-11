<script setup lang="ts">
import { IconChartBar } from "@tabler/icons-vue";
import { useRoute } from "vue-router";
import { Button } from "@/components/ui/button";

const props = defineProps<{
  title?: string;
  description?: string;
}>();

const route = useRoute();

function isActive(path: string) {
  if (path.startsWith("/backoffice/")) {
    return route.path.startsWith(path);
  }
  return route.path === path;
}

function buttonVariant(path: string) {
  return isActive(path) ? "default" : "outline";
}
</script>

<template>
  <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="text-2xl font-bold tracking-tight">
        Back-office · {{ title }}
      </h1>
      <p class="text-sm text-muted-foreground">
        {{description}}
      </p>
    </div>
    <div class="flex flex-wrap gap-2">
      <RouterLink to="/backoffice/courses"
        ><Button :variant="buttonVariant('/backoffice/courses')" size="sm"
          >Cours</Button
        ></RouterLink
      >
      <RouterLink to="/backoffice/users"
        ><Button :variant="buttonVariant('/backoffice/users')" size="sm"
          >Utilisateurs</Button
        ></RouterLink
      >
      <RouterLink to="/backoffice/students"
        ><Button :variant="buttonVariant('/backoffice/students')" size="sm"
          >Étudiants / inscrits</Button
        ></RouterLink
      >
      <RouterLink to="/stats/teacher"
        ><Button :variant="buttonVariant('/stats/teacher')" size="sm"
          ><IconChartBar class="size-4" /> Statistiques</Button
        ></RouterLink
      >
    </div>
  </div>
</template>

<style scoped></style>
