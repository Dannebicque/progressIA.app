<template>
  <AppLayout>
    <NavBarAdmin
      title="Gestion des cours"
      description="Cours · séances · chapitres · pages · évaluations."
    />

    <div class="grid gap-6 lg:grid-cols-4">
      <Card class="lg:col-span-1">
        <CardHeader
          class="flex flex-row items-center justify-between space-y-0"
        >
          <CardTitle class="text-base">Cours</CardTitle>
          <Button size="sm" @click="createCourse"
            ><IconPlus class="size-4"
          /></Button>
        </CardHeader>
        <CardContent class="space-y-1">
          <div
            v-for="c in courses"
            :key="c.id"
            class="flex items-center gap-1 rounded-md transition"
            :class="selectedCourseId === c.id ? 'bg-accent' : 'hover:bg-muted'"
          >
            <button
              class="flex-1 truncate px-3 py-2 text-left text-sm"
              @click="selectCourse(c.id)"
            >
              {{ c.title }}
            </button>
            <Button
              variant="ghost"
              size="icon-sm"
              class="text-destructive"
              @click="removeCourse(c.id)"
              ><IconTrash class="size-4"
            /></Button>
          </div>
          <p
            v-if="!courses.length"
            class="px-3 py-6 text-center text-sm text-muted-foreground"
          >
            Aucun cours.
          </p>
        </CardContent>
      </Card>

      <div class="space-y-6 lg:col-span-3">
        <Card
          v-if="!course"
          class="grid place-items-center py-16 text-center text-muted-foreground"
        >
          Sélectionnez un cours.
        </Card>

        <template v-else>
          <Card>
            <CardHeader
              class="flex flex-row items-center justify-between space-y-0"
            >
              <CardTitle class="text-base">Cours</CardTitle>
              <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="duplicateCourse"
                  ><IconCopy class="size-4" /> Dupliquer</Button
                >
                <Button size="sm" @click="saveCourse">Enregistrer</Button>
              </div>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
              <div class="space-y-1.5">
                <Label>Titre</Label><Input v-model="cTitle" />
              </div>
              <div class="space-y-1.5">
                <Label>Thème</Label><Input v-model="cTheme" />
              </div>
              <div class="space-y-1.5">
                <Label>Niveau</Label><Input v-model="cLevel" />
              </div>
              <div class="space-y-1.5">
                <Label>Catégorie (badges)</Label>
                <Select v-model="cCategory">
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="other">Autre</SelectItem>
                    <SelectItem value="back">Back</SelectItem>
                    <SelectItem value="front">Front</SelectItem>
                    <SelectItem value="fullstack">Fullstack</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5 sm:col-span-2">
                <Label>Couleur d'accent</Label>
                <div class="flex items-center gap-2">
                  <input
                    type="color"
                    v-model="cAccent"
                    class="size-9 cursor-pointer rounded border bg-transparent"
                  />
                  <Input v-model="cAccent" class="flex-1" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader
              ><CardTitle class="text-base">Séances</CardTitle></CardHeader
            >
            <CardContent class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <Select v-model="selectedSessionId">
                  <SelectTrigger class="min-w-56 flex-1"
                    ><SelectValue placeholder="Choisir une séance"
                  /></SelectTrigger>
                  <SelectContent
                    ><SelectItem
                      v-for="s in course.sessions"
                      :key="s.id"
                      :value="String(s.id)"
                      >{{ s.title }}</SelectItem
                    ></SelectContent
                  >
                </Select>
                <Button variant="outline" size="sm" @click="addSession"
                  ><IconPlus class="size-4" /> Séance</Button
                >
                <Button
                  v-if="session"
                  variant="ghost"
                  size="sm"
                  class="text-destructive"
                  @click="removeSession"
                  ><IconTrash class="size-4"
                /></Button>
              </div>
              <div v-if="session" class="flex items-end gap-2">
                <div class="flex-1 space-y-1.5">
                  <Label>Titre de la séance</Label><Input v-model="sTitle" />
                </div>
                <Button size="sm" @click="saveSession">OK</Button>
              </div>
            </CardContent>
          </Card>

          <Card v-if="session">
            <CardHeader
              ><CardTitle class="text-base">Chapitres</CardTitle></CardHeader
            >
            <CardContent class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <Select v-model="selectedChapterId">
                  <SelectTrigger class="min-w-56 flex-1"
                    ><SelectValue placeholder="Choisir un chapitre"
                  /></SelectTrigger>
                  <SelectContent
                    ><SelectItem
                      v-for="ch in session.chapters"
                      :key="ch.id"
                      :value="String(ch.id)"
                      >{{ ch.title }}</SelectItem
                    ></SelectContent
                  >
                </Select>
                <Button variant="outline" size="sm" @click="addChapter"
                  ><IconPlus class="size-4" /> Chapitre</Button
                >
                <Button
                  v-if="chapter"
                  variant="ghost"
                  size="sm"
                  class="text-destructive"
                  @click="removeChapter"
                  ><IconTrash class="size-4"
                /></Button>
              </div>
              <div v-if="chapter" class="flex items-end gap-2">
                <div class="flex-1 space-y-1.5">
                  <Label>Titre du chapitre</Label><Input v-model="chTitle" />
                </div>
                <Button size="sm" @click="saveChapter">OK</Button>
              </div>
            </CardContent>
          </Card>

          <Card v-if="chapter">
            <CardHeader
              ><CardTitle class="text-base">Pages</CardTitle></CardHeader
            >
            <CardContent class="space-y-3">
              <div class="flex flex-wrap items-center gap-2">
                <Button
                  v-for="p in chapter.pages"
                  :key="p.id"
                  size="sm"
                  :variant="
                    selectedPageId === String(p.id) ? 'default' : 'outline'
                  "
                  @click="selectedPageId = String(p.id)"
                  >{{ p.title }}</Button
                >
                <Button variant="outline" size="sm" @click="addPage"
                  ><IconPlus class="size-4" /> Page</Button
                >
              </div>
              <div v-if="page" class="space-y-3">
                <div class="flex items-end gap-2">
                  <div class="flex-1 space-y-1.5">
                    <Label>Titre de la page</Label><Input v-model="pTitle" />
                  </div>
                  <div class="space-y-1.5">
                    <Label>Points</Label
                    ><Input
                      type="number"
                      min="0"
                      v-model.number="pPoints"
                      class="w-24"
                    />
                  </div>
                  <Button
                    size="sm"
                    variant="ghost"
                    class="text-destructive"
                    @click="removePage"
                    ><IconTrash class="size-4"
                  /></Button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <Button variant="outline" size="sm" @click="isLargeEditorOpen = true">
                    Édition grand format
                  </Button>
                  <Button variant="ghost" size="sm" @click="isMarkdownHelpOpen = true">
                    Aide Markdown
                  </Button>
                </div>
                <MarkdownEditor v-model="pContent" />
                <Button size="sm" @click="savePage">Enregistrer la page</Button>
              </div>
            </CardContent>
          </Card>

          <Card v-if="chapter">
            <CardHeader
              class="flex flex-row items-center justify-between space-y-0"
            >
              <CardTitle class="text-base">Évaluations</CardTitle>
              <Button size="sm" variant="outline" @click="addEvaluation"
                ><IconPlus class="size-4" /> Évaluation</Button
              >
            </CardHeader>
            <CardContent class="space-y-4">
              <EvaluationEditor
                v-for="ev in chapter.evaluations"
                :key="ev.id"
                :evaluation-id="ev.id"
                @delete="removeEvaluation(ev.id)"
              />
              <p
                v-if="!chapter.evaluations.length"
                class="text-sm text-muted-foreground"
              >
                Aucune évaluation. Ajoutez-en une (QCM ou réponses libres).
              </p>
            </CardContent>
          </Card>
        </template>
      </div>
    </div>

    <Dialog :open="isLargeEditorOpen" @update:open="(v) => (isLargeEditorOpen = v)">
      <DialogContent class="max-w-[95vw] lg:max-w-6xl">
        <DialogHeader>
          <DialogTitle>Éditeur Markdown grand format</DialogTitle>
          <DialogDescription>
            Modifiez votre contenu avec plus d'espace, tout en gardant l'aperçu en direct.
          </DialogDescription>
        </DialogHeader>
        <MarkdownEditor
          v-model="pContent"
          editor-height-class="h-[65vh]"
          preview-height-class="h-[65vh]"
        />
        <DialogFooter class="gap-2">
          <Button variant="outline" @click="isMarkdownHelpOpen = true">
            Ouvrir l'aide Markdown
          </Button>
          <Button @click="isLargeEditorOpen = false">Revenir à la page</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="isMarkdownHelpOpen" @update:open="(v) => (isMarkdownHelpOpen = v)">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Aide Markdown</DialogTitle>
          <DialogDescription>Raccourci des syntaxes les plus utiles.</DialogDescription>
        </DialogHeader>

        <div class="space-y-4 text-sm">
          <div class="rounded-lg border p-3">
            <p class="font-medium">Titres</p>
            <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
              # Titre 1<br />
              ## Titre 2<br />
              ### Titre 3
            </code>
          </div>

          <div class="rounded-lg border p-3">
            <p class="font-medium">Mise en forme</p>
            <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
              **gras** · *italique* · ~~barré~~
            </code>
          </div>

          <div class="rounded-lg border p-3">
            <p class="font-medium">Listes</p>
            <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
              - Élément<br />
              - Élément<br />
              <br />
              1. Étape<br />
              2. Étape
            </code>
          </div>

          <div class="rounded-lg border p-3">
            <p class="font-medium">Liens et images</p>
            <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
              [Texte du lien](https://example.com)<br />
              ![Description image](https://example.com/image.png)
            </code>
          </div>

          <div class="rounded-lg border p-3">
            <p class="font-medium">Citations et code</p>
            <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
              > Citation<br />
              <br />
              `code en ligne`<br />
              ```js<br />
              console.log('bloc de code')<br />
              ```
            </code>
          </div>
        </div>

        <DialogFooter>
          <Button @click="isMarkdownHelpOpen = false">Fermer</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { IconPlus, IconTrash, IconCopy, IconChartBar } from "@tabler/icons-vue";
import AppLayout from "@/components/AppLayout.vue";
import NavBarAdmin from "@/components/NavBarAdmin.vue";
import MarkdownEditor from "@/components/MarkdownEditor.vue";
import EvaluationEditor from "@/components/EvaluationEditor.vue";
import { useCoursesStore } from "@/stores/courses";
import { showToast } from "@/composables/useToast";
import { confirmDialog } from "@/composables/useConfirm";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

const store = useCoursesStore();
const courses = computed(() => store.courses);

const selectedCourseId = ref<number | string | null>(null);
const selectedSessionId = ref<string>("");
const selectedChapterId = ref<string>("");
const selectedPageId = ref<string>("");

const course = computed(() =>
  selectedCourseId.value != null
    ? store.getCourse(selectedCourseId.value)
    : null,
);
const session = computed(
  () =>
    course.value?.sessions?.find(
      (s: any) => String(s.id) === selectedSessionId.value,
    ) || null,
);
const chapter = computed(
  () =>
    session.value?.chapters?.find(
      (ch: any) => String(ch.id) === selectedChapterId.value,
    ) || null,
);
const page = computed(
  () =>
    chapter.value?.pages?.find(
      (p: any) => String(p.id) === selectedPageId.value,
    ) || null,
);

const cTitle = ref("");
const cTheme = ref("");
const cLevel = ref("");
const cCategory = ref("other");
const cAccent = ref("#7c3aed");
const sTitle = ref("");
const chTitle = ref("");
const pTitle = ref("");
const pContent = ref("");
const pPoints = ref(5);
const isLargeEditorOpen = ref(false);
const isMarkdownHelpOpen = ref(false);

function selectCourse(id: number | string) {
  selectedCourseId.value = id;
  const c = store.getCourse(id);
  selectedSessionId.value = c?.sessions?.[0] ? String(c.sessions[0].id) : "";
}

watch(
  course,
  (c) => {
    if (!c) return;
    cTitle.value = c.title;
    cTheme.value = c.theme || "";
    cLevel.value = c.level || "";
    cCategory.value = c.category || "other";
    cAccent.value = c.accentColor || "#7c3aed";
  },
  { immediate: true },
);
watch(session, (s) => {
  sTitle.value = s?.title || "";
  selectedChapterId.value = s?.chapters?.[0] ? String(s.chapters[0].id) : "";
});
watch(chapter, (ch) => {
  chTitle.value = ch?.title || "";
  selectedPageId.value = ch?.pages?.[0] ? String(ch.pages[0].id) : "";
});
watch(
  page,
  (p) => {
    pTitle.value = p?.title || "";
    pContent.value = p?.content || "";
    pPoints.value = p?.points ?? 5;
  },
  { immediate: true },
);

onMounted(async () => {
  if (!store.loaded) await store.fetchCourses();
  if (store.courses[0]) selectCourse(store.courses[0].id);
});

async function createCourse() {
  const c = await store.createCourse({
    title: "Nouveau cours",
    theme: "Général",
    category: "other",
    accentColor: "#7c3aed",
    level: "Débutant",
  });
  selectCourse(c.id);
  showToast("Cours créé");
}
async function removeCourse(id: number | string) {
  if (
    !(await confirmDialog({
      title: "Supprimer ce cours ?",
      description: "Séances, chapitres, pages et évaluations seront supprimés.",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deleteCourse(id);
  if (selectedCourseId.value === id) selectedCourseId.value = null;
  showToast("Cours supprimé");
}
async function saveCourse() {
  await store.updateCourse(selectedCourseId.value!, {
    title: cTitle.value,
    theme: cTheme.value,
    level: cLevel.value,
    category: cCategory.value,
    accentColor: cAccent.value,
  });
  showToast("Cours enregistré");
}
async function duplicateCourse() {
  if (!course.value) return;
  const src = course.value;
  const nc = await store.createCourse({
    title: `${src.title} (copie)`,
    theme: src.theme,
    category: src.category,
    context: src.context,
    accentColor: src.accentColor,
    level: src.level,
    scenario: src.scenario,
  });
  for (const s of src.sessions || []) {
    const ns = await store.addSession(nc.id, {
      title: s.title,
      pitch: s.pitch,
      renderConfig: s.renderConfig,
    });
    for (const ch of s.chapters || []) {
      const nch = await store.addChapter(ns.id, { title: ch.title });
      for (const p of ch.pages || [])
        await store.addPage(nch.id, {
          title: p.title,
          content: p.content,
          points: p.points,
        });
    }
  }
  selectCourse(nc.id);
  showToast("Cours dupliqué");
}

async function addSession() {
  const s = await store.addSession(selectedCourseId.value!, {});
  selectedSessionId.value = String(s.id);
  showToast("Séance créée");
}
async function removeSession() {
  if (!session.value) return;
  if (
    !(await confirmDialog({
      title: "Supprimer cette séance ?",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deleteSession(session.value.id);
  selectedSessionId.value = course.value?.sessions?.[0]
    ? String(course.value.sessions[0].id)
    : "";
  showToast("Séance supprimée");
}
async function saveSession() {
  await store.updateSession(session.value.id, { title: sTitle.value });
  showToast("Séance enregistrée");
}

async function addChapter() {
  const ch = await store.addChapter(session.value.id, {});
  selectedChapterId.value = String(ch.id);
  showToast("Chapitre créé");
}
async function removeChapter() {
  if (!chapter.value) return;
  if (
    !(await confirmDialog({
      title: "Supprimer ce chapitre ?",
      description: "Pages et évaluations seront supprimées.",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deleteChapter(chapter.value.id);
  selectedChapterId.value = session.value?.chapters?.[0]
    ? String(session.value.chapters[0].id)
    : "";
  showToast("Chapitre supprimé");
}
async function saveChapter() {
  await store.updateChapter(chapter.value.id, { title: chTitle.value });
  showToast("Chapitre enregistré");
}

async function addPage() {
  const p = await store.addPage(chapter.value.id, {});
  selectedPageId.value = String(p.id);
  showToast("Page créée");
}
async function removePage() {
  if (!page.value) return;
  if (
    !(await confirmDialog({
      title: "Supprimer cette page ?",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deletePage(page.value.id);
  selectedPageId.value = chapter.value?.pages?.[0]
    ? String(chapter.value.pages[0].id)
    : "";
  showToast("Page supprimée");
}
async function savePage() {
  await store.updatePage(page.value.id, {
    title: pTitle.value,
    content: pContent.value,
    points: pPoints.value,
  });
  showToast("Page enregistrée");
}

async function addEvaluation() {
  await store.addEvaluation(chapter.value.id, {});
  showToast("Évaluation créée");
}
async function removeEvaluation(id: number | string) {
  if (
    !(await confirmDialog({
      title: "Supprimer cette évaluation ?",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deleteEvaluation(id);
  showToast("Évaluation supprimée");
}
</script>
