<template>
  <BackofficeLayout>

    <div class="grid gap-6 lg:grid-cols-3">
      <!-- COLUMN 1: Filters -->
      <Card>
        <CardHeader>
          <CardTitle class="text-base">Filtres</CardTitle>
        </CardHeader>
        <CardContent class="space-y-3">
          <div class="space-y-1.5">
            <Label>Établissement</Label>
            <Select v-model="etablissementFilter">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous</SelectItem>
                <SelectItem
                  v-for="item in etablissementOptions"
                  :key="item"
                  :value="item"
                  >{{ item }}</SelectItem
                >
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-1.5">
            <Label>Année / Diplôme</Label>
            <Select v-model="diplomeFilter">
              <SelectTrigger><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tous</SelectItem>
                <SelectItem
                  v-for="item in diplomeOptions"
                  :key="item"
                  :value="item"
                  >{{ item }}</SelectItem
                >
              </SelectContent>
            </Select>
          </div>

          <div class="space-y-1.5">
            <Label>Groupes de TD/TP</Label>
            <div class="max-h-40 space-y-2 overflow-auto rounded-md border p-2">
              <label
                v-for="group in groupOptions"
                :key="group"
                class="flex items-center gap-2 text-sm"
              >
                <input
                  :checked="selectedGroups.includes(group)"
                  type="checkbox"
                  class="size-4 accent-primary"
                  @change="toggleGroup(group)"
                />
                <span>{{ group }}</span>
              </label>
              <p v-if="!groupOptions.length" class="text-xs text-muted-foreground italic">Aucun groupe défini.</p>
            </div>
            <p class="text-xs text-muted-foreground">
              Sélectionnez les groupes à afficher.
            </p>
          </div>

          <Button variant="outline" class="w-full text-xs" @click="clearFilters"
            >Réinitialiser les filtres</Button
          >
        </CardContent>
      </Card>

      <!-- COLUMN 2 & 3: CSV Import & Student list -->
      <div class="space-y-6 lg:col-span-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Import CSV</CardTitle>
            <CardDescription
              >Colonnes attendues : prénom, nom, email, établissement, diplôme,
              groupes, année.</CardDescription
            >
          </CardHeader>
          <CardContent class="space-y-3">
            <Input
              type="file"
              accept=".csv,text/csv"
              @change="onFileSelected"
            />
            <div class="flex flex-wrap items-center gap-2">
              <Button
                :disabled="!selectedFile || isImporting"
                @click="runCsvImport"
                >Importer</Button
              >
              <span class="text-sm text-muted-foreground">{{
                selectedFile?.name || "Aucun fichier sélectionné"
              }}</span>
            </div>
            <div v-if="isImporting || importProgress > 0" class="space-y-2">
              <Progress :model-value="importProgress" />
              <p class="text-sm text-muted-foreground">
                Progression : {{ Math.round(importProgress) }}%
              </p>
            </div>
            <p v-if="importSummary" class="text-sm">
              {{ importSummary.imported }} importé(s),
              {{ importSummary.errors.length }} erreur(s), sur
              {{ importSummary.totalRows }} ligne(s).
            </p>
            <div
              v-if="importSummary?.errors.length"
              class="max-h-40 space-y-2 overflow-auto rounded-md border p-3 text-sm"
            >
              <p
                v-for="err in importSummary.errors"
                :key="`${err.line}-${err.message}`"
                class="text-destructive"
              >
                Ligne {{ err.line }} : {{ err.message }}
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Student List -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base"
              >Étudiants inscrits ({{ filteredStudents.length }})</CardTitle
            >
          </CardHeader>
          <CardContent class="space-y-3">
            <div v-if="loading" class="py-8 text-center text-sm text-muted-foreground">
              Chargement des données étudiants...
            </div>
            <template v-else>
              <div
                v-for="student in filteredStudents"
                :key="student.id"
                class="rounded-lg border p-3 hover:border-primary/40 transition-colors flex items-center justify-between gap-4"
              >
                <div class="space-y-1">
                  <div class="flex items-center gap-2 flex-wrap">
                    <p class="font-medium text-sm">{{ student.name }}</p>
                    <Badge variant="outline" class="text-[10px]">{{ student.studentGroup || 'Sans groupe' }}</Badge>
                  </div>
                  <p class="text-xs text-muted-foreground">{{ student.email }}</p>
                  <div class="flex flex-wrap items-center gap-2 pt-1 text-[10px]">
                    <span class="text-muted-foreground">{{ student.studentInstitution || 'Établissement inconnu' }}</span>
                    <span class="text-muted-foreground">·</span>
                    <span class="text-muted-foreground">{{ student.studentYear || 'Année inconnue' }}</span>
                    <span class="text-muted-foreground">·</span>
                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">🏆 {{ student.points }} pts</span>
                  </div>
                </div>
                <RouterLink :to="'/backoffice/students/' + student.id">
                  <Button size="xs" variant="outline">Voir la fiche</Button>
                </RouterLink>
              </div>
              <p
                v-if="!filteredStudents.length"
                class="py-8 text-center text-sm text-muted-foreground"
              >
                Aucun étudiant pour ces filtres.
              </p>
            </template>
          </CardContent>
        </Card>
      </div>
    </div>
  </BackofficeLayout>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import BackofficeLayout from "@/components/BackofficeLayout.vue";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Progress } from "@/components/ui/progress";
import { Badge } from "@/components/ui/badge";
import { showToast } from "@/composables/useToast";

import { api } from "@/api/client";


interface Student {
  id: number;
  name: string;
  email: string;
  studentGroup: string;
  studentYear: string;
  studentInstitution: string;
  points: number;
  badges: Array<{ code: string; icon: string; label: string; description: string }>;
  courseStats: Array<{
    courseId: number;
    courseTitle: string;
    courseAccentColor: string;
    totalPages: number;
    completedPages: number;
    progressPct: number;
    evaluations: Array<{
      id: number;
      title: string;
      pointsReward: number;
      attempted: boolean;
      score: number | null;
      maxScore: number | null;
      passed: boolean;
    }>;
    passedEvaluationsCount: number;
    totalEvaluationsCount: number;
    totalEvaluationScore: number;
    totalEvaluationMaxScore: number;
  }>;
}

interface ParsedStudentRow {
  firstName: string;
  lastName: string;
  email: string;
  etablissement: string;
  diplome: string;
  groupes: string[];
  anneeUniversitaire: string;
}

interface ImportError {
  line: number;
  message: string;
}

const students = ref<Student[]>([]);
const loading = ref(false);

const etablissementFilter = ref("all");
const diplomeFilter = ref("all");
const selectedGroups = ref<string[]>([]);

const selectedFile = ref<File | null>(null);
const isImporting = ref(false);
const importProgress = ref(0);
const importSummary = ref<{
  imported: number;
  totalRows: number;
  errors: ImportError[];
} | null>(null);



async function loadStudents() {
  loading.value = true;
  try {
    const res = await api.get<Student[]>('/api/teacher/students');
    students.value = res;
  } catch (e) {
    console.error(e);
    showToast('Erreur lors du chargement des données étudiants', 'error');
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadStudents();
});

const etablissementOptions = computed(() =>
  [...new Set(students.value.map((s) => s.studentInstitution).filter(Boolean))].sort(),
);
const diplomeOptions = computed(() =>
  [...new Set(students.value.map((s) => s.studentYear).filter(Boolean))].sort(),
);
const groupOptions = computed(() =>
  [
    ...new Set(
      students.value.flatMap((s) =>
        s.studentGroup ? s.studentGroup.split(/[,/\-\s]+/).map((g) => g.trim()).filter(Boolean) : []
      )
    )
  ].sort(),
);

const filteredStudents = computed(() =>
  students.value.filter((s) => {
    const okEtablissement =
      etablissementFilter.value === "all" ||
      s.studentInstitution === etablissementFilter.value;
    const okDiplome =
      diplomeFilter.value === "all" || s.studentYear === diplomeFilter.value;
    const okGroupes =
      !selectedGroups.value.length ||
      selectedGroups.value.some((g) => s.studentGroup && s.studentGroup.includes(g));
    return okEtablissement && okDiplome && okGroupes;
  }),
);

function toggleGroup(group: string) {
  if (selectedGroups.value.includes(group)) {
    selectedGroups.value = selectedGroups.value.filter((g) => g !== group);
    return;
  }
  selectedGroups.value = [...selectedGroups.value, group];
}

function clearFilters() {
  etablissementFilter.value = "all";
  diplomeFilter.value = "all";
  selectedGroups.value = [];
}

function onFileSelected(event: Event) {
  const target = event.target as HTMLInputElement;
  selectedFile.value = target.files?.[0] ?? null;
  importSummary.value = null;
  importProgress.value = 0;
}

async function runCsvImport() {
  if (!selectedFile.value) return;
  const raw = await selectedFile.value.text();
  const { rows, errors: parsingErrors } = parseCsv(raw);

  isImporting.value = true;
  importProgress.value = 0;
  const errors: ImportError[] = [...parsingErrors];
  let imported = 0;
  const nextId = () => Math.max(0, ...students.value.map((s) => s.id)) + 1;

  for (const [i, row] of rows.entries()) {
    await wait(35);
    const lineNumber = i + 2;
    if (!isValidEmail(row.email)) {
      errors.push({
        line: lineNumber,
        message: `Email invalide (${row.email})`,
      });
      importProgress.value = ((i + 1) / rows.length) * 100;
      continue;
    }
    if (
      students.value.some(
        (s) => s.email.toLowerCase() === row.email.toLowerCase(),
      )
    ) {
      errors.push({
        line: lineNumber,
        message: `Email déjà existant (${row.email})`,
      });
      importProgress.value = ((i + 1) / rows.length) * 100;
      continue;
    }

    students.value.push({
      id: nextId(),
      name: `${row.firstName} ${row.lastName}`,
      email: row.email,
      studentGroup: row.groupes.join(', '),
      studentYear: row.diplome,
      studentInstitution: row.etablissement,
      points: 0,
      badges: [],
      courseStats: []
    });
    imported += 1;
    importProgress.value = ((i + 1) / rows.length) * 100;
  }

  isImporting.value = false;
  importSummary.value = { imported, totalRows: rows.length, errors };
  if (!errors.length) showToast("Import CSV terminé");
  else showToast("Import CSV terminé avec erreurs");
}

function parseCsv(raw: string): {
  rows: ParsedStudentRow[];
  errors: ImportError[];
} {
  const lines = raw.split(/\r?\n/).filter((line) => line.trim().length > 0);
  if (!lines.length)
    return { rows: [], errors: [{ line: 1, message: "CSV vide" }] };

  const headerLine = lines[0] ?? "";
  const delimiter = detectDelimiter(headerLine);
  const headers = parseCsvLine(headerLine, delimiter).map((h) =>
    normalizeHeader(h),
  );

  const required = [
    "firstname",
    "lastname",
    "email",
    "etablissement",
    "diplome",
    "groupes",
    "anneeuniversitaire",
  ];
  const missing = required.filter((key) => !headers.includes(key));
  if (missing.length) {
    return {
      rows: [],
      errors: [
        { line: 1, message: `Colonnes manquantes: ${missing.join(", ")}` },
      ],
    };
  }

  const rows: ParsedStudentRow[] = [];
  const errors: ImportError[] = [];

  for (let i = 1; i < lines.length; i += 1) {
    const line = lines[i] ?? "";
    const values = parseCsvLine(line, delimiter);
    const get = (key: string) => values[headers.indexOf(key)]?.trim() || "";
    const groupes = get("groupes")
      .split(/[|/]/)
      .map((group) => group.trim())
      .filter(Boolean);

    const row: ParsedStudentRow = {
      firstName: get("firstname"),
      lastName: get("lastname"),
      email: get("email"),
      etablissement: get("etablissement"),
      diplome: get("diplome"),
      groupes,
      anneeUniversitaire: get("anneeuniversitaire"),
    };

    if (
      !row.firstName ||
      !row.lastName ||
      !row.email ||
      !row.etablissement ||
      !row.diplome ||
      !row.groupes.length ||
      !row.anneeUniversitaire
    ) {
      errors.push({ line: i + 1, message: "Données incomplètes" });
      continue;
    }

    rows.push(row);
  }

  return { rows, errors };
}

function detectDelimiter(headerLine: string): "," | ";" {
  const commaCount = (headerLine.match(/,/g) || []).length;
  const semicolonCount = (headerLine.match(/;/g) || []).length;
  return semicolonCount > commaCount ? ";" : ",";
}

function parseCsvLine(line: string, delimiter: "," | ";"): string[] {
  const values: string[] = [];
  let current = "";
  let insideQuotes = false;

  for (let i = 0; i < line.length; i += 1) {
    const char = line[i];
    if (char === '"') {
      if (insideQuotes && line[i + 1] === '"') {
        current += '"';
        i += 1;
      } else {
        insideQuotes = !insideQuotes;
      }
      continue;
    }
    if (char === delimiter && !insideQuotes) {
      values.push(current);
      current = "";
      continue;
    }
    current += char;
  }
  values.push(current);
  return values;
}

function normalizeHeader(value: string): string {
  const normalized = value
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, "");
  if (normalized === "prenom") return "firstname";
  if (normalized === "nom") return "lastname";
  if (normalized === "annee" || normalized === "anneescolaire")
    return "anneeuniversitaire";
  if (normalized === "groupes" || normalized === "groupe") return "groupes";
  return normalized;
}

function isValidEmail(value: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

function wait(ms: number) {
  return new Promise((resolve) => window.setTimeout(resolve, ms));
}
</script>
