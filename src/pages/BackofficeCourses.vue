<template>
  <AppLayout>
    <NavBarAdmin
      title="Gestion des cours"
      description="Interface d'édition interactive Gutenberg & GitBook."
    />

    <!-- Main 3-column layout -->
    <div class="grid grid-cols-12 gap-6 items-start">
      
      <!-- COLUMN 1: Cours & Paramètres Globaux -->
      <div v-show="isLeftPanelVisible" class="col-span-12 lg:col-span-3 space-y-6">
        <!-- Course Selection Card -->
        <Card>
          <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
            <CardTitle class="text-sm font-semibold">Mes Cours</CardTitle>
            <Button size="icon-sm" variant="outline" @click="createCourse" title="Nouveau cours">
              <IconPlus class="size-4" />
            </Button>
          </CardHeader>
          <CardContent class="space-y-1 max-h-[30vh] overflow-y-auto pr-1">
            <div
              v-for="c in courses"
              :key="c.id"
              class="flex items-center gap-1 rounded-md transition"
              :class="selectedCourseId === c.id ? 'bg-accent font-medium' : 'hover:bg-muted'"
            >
              <button
                class="flex-1 truncate px-3 py-2 text-left text-sm"
                @click="selectCourse(c.id)"
              >
                <span class="inline-block size-2 rounded-full mr-2" :style="{ backgroundColor: c.accentColor || '#7c3aed' }"></span>
                {{ c.title }}
              </button>
              <Button
                variant="ghost"
                size="icon-sm"
                class="text-destructive opacity-50 hover:opacity-100"
                @click="removeCourse(c.id)"
              >
                <IconTrash class="size-4" />
              </Button>
            </div>
            <p v-if="!courses.length" class="text-xs text-center text-muted-foreground py-4">
              Aucun cours.
            </p>
          </CardContent>
        </Card>

        <!-- Course Global Settings Card -->
        <Card v-if="course" class="border-t-4" :style="{ borderTopColor: cAccent }">
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold flex items-center gap-1.5">
              <IconSettings class="size-4 text-muted-foreground" />
              Paramètres du cours
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-3 text-xs">
            <div class="space-y-1">
              <Label class="text-[10px] uppercase font-bold text-muted-foreground">Titre</Label>
              <Input v-model="cTitle" class="h-8 text-xs" @blur="saveCourse" />
            </div>
            
            <div class="grid grid-cols-2 gap-2">
              <div class="space-y-1">
                <Label class="text-[10px] uppercase font-bold text-muted-foreground">Semestre</Label>
                <Input v-model="cSemester" placeholder="Ex: S1" class="h-8 text-xs" @blur="saveCourse" />
              </div>
              <div class="space-y-1">
                <Label class="text-[10px] uppercase font-bold text-muted-foreground">Niveau</Label>
                <Input v-model="cLevel" placeholder="Ex: Débutant" class="h-8 text-xs" @blur="saveCourse" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div class="space-y-1">
                <Label class="text-[10px] uppercase font-bold text-muted-foreground">Thème</Label>
                <Input v-model="cTheme" class="h-8 text-xs" @blur="saveCourse" />
              </div>
              <div class="space-y-1">
                <Label class="text-[10px] uppercase font-bold text-muted-foreground">Catégorie</Label>
                <Select v-model="cCategory" @update:model-value="saveCourse">
                  <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
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
              <Label class="text-[10px] uppercase font-bold text-muted-foreground">Couleur d'accent</Label>
              <div class="flex items-center gap-1.5">
                <input
                  type="color"
                  v-model="cAccent"
                  class="size-7 cursor-pointer rounded border bg-transparent shrink-0"
                  @change="saveCourse"
                />
                <Input v-model="cAccent" class="h-8 text-xs font-mono" @blur="saveCourse" />
              </div>
            </div>

            <div class="flex items-center gap-2 py-1">
              <Checkbox id="cVisible" :checked="cVisible" @update:checked="(val: any) => { cVisible = val; saveCourse(); }" />
              <Label for="cVisible" class="font-medium text-xs">Visible pour les étudiants</Label>
            </div>

            <Separator class="my-2" />

            <div class="space-y-1">
              <Label class="text-[10px] uppercase font-bold text-muted-foreground">Pitch / Contexte</Label>
              <textarea
                v-model="cContext"
                rows="2"
                placeholder="Description rapide..."
                class="w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                @blur="saveCourse"
              ></textarea>
            </div>

            <div class="space-y-1">
              <Label class="text-[10px] uppercase font-bold text-muted-foreground">Scénario de cours</Label>
              <textarea
                v-model="cScenario"
                rows="3"
                placeholder="Scénarisation globale du cours..."
                class="w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                @blur="saveCourse"
              ></textarea>
            </div>

            <div class="flex gap-2 pt-2">
              <Button variant="outline" size="xs" class="flex-1" @click="duplicateCourse">
                <IconCopy class="size-3 mr-1" /> Dupliquer
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- COLUMN 2: Zone de Construction Centrale (Builder) -->
      <div class="col-span-12 space-y-6" :class="isLeftPanelVisible ? 'lg:col-span-6' : 'lg:col-span-9'">
        <Card v-if="!course" class="grid place-items-center py-24 text-muted-foreground">
          <div class="text-center space-y-2">
            <IconFolder class="size-10 mx-auto text-muted-foreground/50" />
            <p class="text-sm">Sélectionnez un cours dans le volet de gauche.</p>
          </div>
        </Card>

        <template v-else>
          <!-- Sessions Navigation Tabs -->
          <div class="flex flex-col gap-2">
            <div class="flex items-center justify-between border-b pb-2">
              <div class="flex items-center gap-2">
                <Button 
                  size="icon-xs" 
                  variant="outline" 
                  @click="isLeftPanelVisible = !isLeftPanelVisible" 
                  :title="isLeftPanelVisible ? 'Masquer la liste des cours' : 'Afficher la liste des cours'"
                >
                  <IconLayoutSidebar class="size-3.5" />
                </Button>
                <Label class="text-xs uppercase font-bold text-muted-foreground tracking-wider">Séances de ce cours</Label>
              </div>
              <Button size="xs" variant="outline" @click="addSession" class="gap-1 text-xs">
                <IconPlus class="size-3.5" /> Séance
              </Button>
            </div>
            
            <div class="flex flex-wrap items-center gap-1.5">
              <button
                v-for="(s, idx) in course.sessions"
                :key="s.id"
                class="px-3 py-1.5 rounded-full text-xs transition flex items-center gap-1.5 border"
                :class="selectedSessionId === String(s.id) 
                  ? 'bg-primary text-primary-foreground font-semibold border-primary shadow-sm' 
                  : 'bg-card text-muted-foreground hover:bg-muted border-input'"
                @click="selectedSessionId = String(s.id)"
              >
                <span>{{ Number(idx) + 1 }}</span>
                <span class="truncate max-w-28">{{ s.title }}</span>
                <IconEyeOff v-if="s.visible === false" class="size-3 opacity-60" />
              </button>
              <p v-if="!course.sessions?.length" class="text-xs text-muted-foreground py-2">
                Aucune séance. Cliquez sur "+ Séance" pour démarrer.
              </p>
            </div>
          </div>

          <!-- Active Session details -->
          <div v-if="session" class="space-y-6">
            <!-- Session Main Card -->
            <Card :id="`session-${session.id}`" class="border-l-4 transition-all" :class="{ 'element-highlight': highlightedElementId === `session-${session.id}` }" :style="{ borderLeftColor: cAccent }">
              <CardContent class="pt-5 space-y-4">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1 space-y-2">
                    <div class="flex items-center gap-2">
                      <Input
                        v-model="sTitle"
                        class="text-lg font-bold border-none shadow-none px-0 focus-visible:ring-0 h-8"
                        placeholder="Titre de la séance"
                        @blur="saveSession"
                      />
                    </div>
                  </div>
                  
                  <div class="flex items-center gap-1.5 shrink-0">
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      :class="sVisible ? 'text-primary' : 'text-muted-foreground'"
                      @click="() => { sVisible = !sVisible; saveSession(); }"
                      title="Visibilité de la séance"
                    >
                      <component :is="sVisible ? IconEye : IconEyeOff" class="size-4" />
                    </Button>
                    
                    <div class="flex gap-0.5">
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        @click="moveSession(session, 'up')"
                        title="Monter la séance"
                      >
                        <IconChevronUp class="size-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        @click="moveSession(session, 'down')"
                        title="Descendre la séance"
                      >
                        <IconChevronDown class="size-4" />
                      </Button>
                    </div>

                    <Button
                      variant="ghost"
                      size="icon-sm"
                      class="text-destructive"
                      @click="removeSession"
                      title="Supprimer la séance"
                    >
                      <IconTrash class="size-4" />
                    </Button>
                  </div>
                </div>

                <!-- Session Context/Pitch & Upload config (collapsible) -->
                <div class="rounded-lg bg-muted/30 p-3 space-y-3 text-xs border border-dashed">
                  <div class="space-y-1">
                    <Label class="text-[10px] uppercase font-bold text-muted-foreground">Contexte scénaristique de la séance (Pitch)</Label>
                    <textarea
                      v-model="sPitch"
                      rows="2"
                      placeholder="Contexte scénaristique..."
                      class="w-full rounded-md border border-input bg-transparent px-3 py-1 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                      @blur="saveSession"
                    ></textarea>
                  </div>

                  <div class="space-y-2 pt-1">
                    <div class="flex items-center gap-2">
                      <Checkbox 
                        :id="`allowUpload-${session.id}`" 
                        :checked="session.renderConfig?.allowUpload" 
                        @update:checked="(val: any) => updateSessionConfig(session, 'allowUpload', val)" 
                      />
                      <Label :for="`allowUpload-${session.id}`" class="font-medium text-xs flex items-center gap-1 cursor-pointer">
                        <IconUpload class="size-3.5" />
                        Autoriser un dépôt de fichier ou rendu étudiant pour cette séance
                      </Label>
                    </div>

                    <div v-if="session.renderConfig?.allowUpload" class="pl-5 space-y-2 grid grid-cols-2 gap-4">
                      <div class="space-y-1">
                        <Label class="text-[10px] text-muted-foreground">Nombre maximum de fichiers</Label>
                        <Input
                          type="number"
                          min="1"
                          max="10"
                          class="h-7 text-xs w-20"
                          :model-value="session.renderConfig?.maxFiles || 1"
                          @update:model-value="(val) => updateSessionConfig(session, 'maxFiles', Number(val))"
                        />
                      </div>
                      <div class="space-y-1.5">
                        <Label class="text-[10px] text-muted-foreground">Types autorisés</Label>
                        <div class="flex gap-3 items-center">
                          <label class="flex items-center gap-1 cursor-pointer">
                            <Checkbox 
                              :checked="session.renderConfig?.allowedTypes?.includes('file')" 
                              @update:checked="(val: any) => toggleAllowedType(session, 'file', val)" 
                            /> Fichier
                          </label>
                          <label class="flex items-center gap-1 cursor-pointer">
                            <Checkbox 
                              :checked="session.renderConfig?.allowedTypes?.includes('image')" 
                              @update:checked="(val: any) => toggleAllowedType(session, 'image', val)" 
                            /> Image
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>

            <!-- Chapters list (Visual Blocks builder) -->
            <div class="space-y-6">
              <div class="flex items-center justify-between border-b pb-1">
                <h3 class="text-sm font-semibold tracking-wide uppercase text-muted-foreground">Chapitres & Contenu</h3>
                <Button size="xs" variant="outline" @click="addChapter" class="gap-1">
                  <IconPlus class="size-3" /> Chapitre
                </Button>
              </div>

              <!-- CHAPTER CARD -->
              <div
                v-for="ch in session.chapters"
                :key="ch.id"
                :id="`chapter-${ch.id}`"
                class="rounded-xl border bg-card text-card-foreground shadow-sm transition-all"
                :class="{ 'element-highlight': highlightedElementId === `chapter-${ch.id}` }"
              >
                <!-- Chapter Header -->
                <div class="flex items-center justify-between gap-4 px-4 py-3 bg-muted/20 rounded-t-xl border-b">
                  <div class="flex-1 flex items-center gap-2">
                    <IconFolder class="size-4 text-muted-foreground shrink-0" />
                    <Input
                      :model-value="ch.title"
                      class="font-semibold text-sm border-none shadow-none h-7 px-1 bg-transparent focus-visible:ring-0 focus-visible:bg-background"
                      placeholder="Titre du chapitre"
                      @blur="(e: any) => updateChapterTitle(ch, e.target.value)"
                    />
                  </div>

                  <div class="flex items-center gap-1">
                    <Button
                      variant="ghost"
                      size="icon-sm"
                      :class="ch.visible !== false ? 'text-primary' : 'text-muted-foreground'"
                      @click="toggleChapterVisibility(ch)"
                      title="Visibilité du chapitre"
                    >
                      <component :is="ch.visible !== false ? IconEye : IconEyeOff" class="size-4" />
                    </Button>

                    <div class="flex gap-0.5">
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        @click="moveChapter(ch, 'up')"
                        title="Monter le chapitre"
                      >
                        <IconChevronUp class="size-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        @click="moveChapter(ch, 'down')"
                        title="Descendre le chapitre"
                      >
                        <IconChevronDown class="size-4" />
                      </Button>
                    </div>

                    <Button
                      variant="ghost"
                      size="icon-sm"
                      class="text-destructive"
                      @click="removeChapterBlock(ch)"
                      title="Supprimer le chapitre"
                    >
                      <IconTrash class="size-4" />
                    </Button>
                  </div>
                </div>

                <!-- Chapter Blocks list -->
                <div class="p-4 space-y-4 bg-card rounded-b-xl min-h-16">
                  
                  <!-- Page & Evaluation Items -->
                  <div class="space-y-3">
                    <div 
                      v-for="item in getChapterItems(ch)" 
                      :key="item.key"
                      :id="item.key"
                      class="rounded-lg border bg-background overflow-hidden transition-all shadow-xs border-l-4"
                      :class="[
                        item.type === 'page' ? 'border-l-blue-500' : 'border-l-amber-500',
                        { 'element-highlight': highlightedElementId === item.key }
                      ]"
                    >
                      <!-- Block Header -->
                      <div class="flex items-center justify-between gap-4 px-3 py-2 bg-muted/5">
                        <div class="flex-1 flex items-center gap-2 min-w-0">
                          <component
                            :is="item.type === 'page' ? IconFileText : IconClipboardCheck"
                            class="size-4 shrink-0"
                            :class="item.type === 'page' ? 'text-blue-500' : 'text-amber-500'"
                          />
                          <Input
                            :model-value="item.data.title"
                            class="font-medium text-xs border-none shadow-none h-6 px-1 bg-transparent focus-visible:ring-0 focus-visible:bg-background flex-1"
                            placeholder="Titre"
                            @blur="(e: any) => updateItemTitle(item, e.target.value)"
                          />
                        </div>

                        <!-- Points / Right side controls -->
                        <div class="flex items-center gap-3 shrink-0 text-xs">
                          <!-- Points Input -->
                          <div class="flex items-center gap-1.5">
                            <span class="text-[10px] uppercase text-muted-foreground font-semibold">Pts:</span>
                            <input
                              type="number"
                              min="0"
                              class="w-12 h-6 border rounded px-1.5 text-center text-xs bg-background"
                              :value="item.type === 'page' ? item.data.points : item.data.pointsReward"
                              @change="(e: any) => updateItemPoints(item, Number(e.target.value))"
                            />
                          </div>

                          <Separator orientation="vertical" class="h-4" />

                          <!-- Edit toggle button -->
                          <Button
                            variant="outline"
                            size="xs"
                            class="h-6 px-2 text-[10px] font-semibold gap-1"
                            :class="expandedBlocks[item.key] ? 'bg-muted text-foreground' : ''"
                            @click="toggleBlock(item.key)"
                          >
                            {{ expandedBlocks[item.key] ? 'Fermer' : 'Modifier' }}
                          </Button>

                          <!-- Actions -->
                          <div class="flex items-center gap-0.5">
                            <Button
                              variant="ghost"
                              size="icon-xs"
                              :class="item.data.visible !== false ? 'text-primary' : 'text-muted-foreground'"
                              @click="toggleItemVisibility(item)"
                              title="Visibilité"
                            >
                              <component :is="item.data.visible !== false ? IconEye : IconEyeOff" class="size-3.5" />
                            </Button>
                            
                            <Button
                              variant="ghost"
                              size="icon-xs"
                              @click="moveItem(item, ch, 'up')"
                              title="Monter"
                            >
                              <IconChevronUp class="size-3.5" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon-xs"
                              @click="moveItem(item, ch, 'down')"
                              title="Descendre"
                            >
                              <IconChevronDown class="size-3.5" />
                            </Button>
                            <Button
                              variant="ghost"
                              size="icon-xs"
                              class="text-destructive"
                              @click="removeItemBlock(item)"
                              title="Supprimer"
                            >
                              <IconTrash class="size-3.5" />
                            </Button>
                          </div>
                        </div>
                      </div>

                      <!-- Block Body: expanded editor -->
                      <div v-if="expandedBlocks[item.key]" class="border-t p-3 bg-muted/10 space-y-3">
                        <!-- Markdown Editor for Page -->
                        <div v-if="item.type === 'page'" class="space-y-2">
                          <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase text-muted-foreground font-bold">Contenu Markdown de la page</span>
                            <Button variant="ghost" size="xs" @click="openMarkdownHelp">Aide Markdown</Button>
                          </div>
                          <MarkdownEditor v-model="item.data.content" />
                          <div class="flex justify-end gap-2">
                            <Button size="xs" @click="savePageContent(item.data)">Sauvegarder le contenu</Button>
                          </div>
                        </div>

                        <!-- Quiz Builder for Evaluation -->
                        <div v-else-if="item.type === 'eval'">
                          <div class="mb-2 text-[10px] uppercase text-muted-foreground font-bold">Éditeur de questionnaire</div>
                          <EvaluationEditor
                            :evaluation-id="item.id"
                            @delete="removeItemBlock(item)"
                          />
                        </div>
                      </div>
                    </div>

                    <p v-if="!ch.pages?.length && !ch.evaluations?.length" class="text-xs text-center text-muted-foreground/60 py-6 border border-dashed rounded-lg">
                      Aucun élément dans ce chapitre. Ajoutez une page ou une évaluation.
                    </p>
                  </div>

                  <!-- Add block buttons in chapter footer -->
                  <div class="flex items-center justify-center gap-2 pt-2 border-t border-dashed">
                    <Button size="xs" variant="outline" class="gap-1 text-xs hover:border-blue-500 hover:text-blue-600" @click="addPageBlock(ch)">
                      <IconPlus class="size-3 text-blue-500" /> Page
                    </Button>
                    <Button size="xs" variant="outline" class="gap-1 text-xs hover:border-amber-500 hover:text-amber-600" @click="addEvaluationBlock(ch)">
                      <IconPlus class="size-3 text-amber-500" /> Évaluation / Quiz
                    </Button>
                  </div>
                </div>
              </div>

              <div v-if="!session.chapters?.length" class="text-center py-12 border border-dashed rounded-xl bg-card">
                <IconBook class="size-8 mx-auto text-muted-foreground/45 mb-1" />
                <p class="text-sm text-muted-foreground">Cette séance est vide de chapitres.</p>
                <Button size="sm" variant="outline" class="mt-3" @click="addChapter">Créer le premier chapitre</Button>
              </div>
            </div>
          </div>
        </template>
      </div>

      <!-- COLUMN 3: Arborescence de Navigation Interactive (Hierarchy Outline) -->
      <div v-if="course" class="col-span-12 lg:col-span-3">
        <Card class="sticky top-24">
          <CardHeader class="pb-3 border-b flex flex-row items-center justify-between space-y-0">
            <CardTitle class="text-sm font-semibold flex items-center gap-1.5">
              <IconLayoutList class="size-4 text-muted-foreground" />
              Navigation & Structure
            </CardTitle>
            <span class="text-[10px] bg-accent text-accent-foreground px-2 py-0.5 rounded-full font-semibold">
              {{ totalOutlineItems }} élém.
            </span>
          </CardHeader>
          <CardContent class="pt-4 pr-2 max-h-[75vh] overflow-y-auto outline-tree-container">
            <div class="space-y-4">
              
              <!-- Session Nodes -->
              <div v-for="(s, sIdx) in course.sessions" :key="s.id" class="space-y-1.5">
                <button
                  class="flex w-full items-start gap-1 text-left text-xs font-semibold hover:text-primary transition"
                  :class="[
                    selectedSessionId === String(s.id) ? 'text-primary' : 'text-muted-foreground',
                    s.visible === false ? 'opacity-50' : ''
                  ]"
                  @click="scrollToElement(`session-${s.id}`, s.id)"
                >
                  <span class="shrink-0 text-[10px] bg-muted px-1 py-0.2 rounded mt-0.5">S{{ Number(sIdx) + 1 }}</span>
                  <span class="truncate flex-1">{{ s.title }}</span>
                  <IconEyeOff v-if="s.visible === false" class="size-3 mt-0.5 text-muted-foreground shrink-0" />
                </button>

                <!-- Chapter Nodes nested under session (only if session active, or always display outline) -->
                <div class="pl-3 border-l-2 border-muted/50 ml-3.5 space-y-2 py-1">
                  
                  <!-- Chapter Outline -->
                  <div v-for="ch in s.chapters" :key="ch.id" class="space-y-1">
                    <button
                      class="flex w-full items-start gap-1 text-left text-[11px] font-medium hover:text-primary transition"
                      :class="ch.visible === false ? 'opacity-50' : ''"
                      @click="scrollToElement(`chapter-${ch.id}`, s.id)"
                    >
                      <IconFolder class="size-3.5 text-muted-foreground shrink-0 mt-0.5" />
                      <span class="truncate flex-1">{{ ch.title }}</span>
                      <IconEyeOff v-if="ch.visible === false" class="size-3 mt-0.5 text-muted-foreground shrink-0" />
                    </button>

                    <!-- Page/Eval list nodes -->
                    <div class="pl-4 ml-1.5 border-l border-muted/30 space-y-1">
                      <!-- Page Node -->
                      <button
                        v-for="p in ch.pages"
                        :key="p.id"
                        class="flex w-full items-center gap-1 text-left text-[10px] text-muted-foreground hover:text-blue-500 transition"
                        :class="p.visible === false ? 'opacity-40' : ''"
                        @click="scrollToElement(`page-${p.id}`, s.id)"
                      >
                        <IconFileText class="size-3 text-blue-400 shrink-0" />
                        <span class="truncate flex-1">{{ p.title }}</span>
                        <span class="text-[8px] bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 px-1 py-0.2 rounded font-semibold">{{ p.points }}p</span>
                        <IconEyeOff v-if="p.visible === false" class="size-2 text-muted-foreground shrink-0" />
                      </button>

                      <!-- Eval Node -->
                      <button
                        v-for="ev in ch.evaluations"
                        :key="ev.id"
                        class="flex w-full items-center gap-1 text-left text-[10px] text-muted-foreground hover:text-amber-500 transition"
                        :class="ev.visible === false ? 'opacity-40' : ''"
                        @click="scrollToElement(`eval-${ev.id}`, s.id)"
                      >
                        <IconClipboardCheck class="size-3 text-amber-500 shrink-0" />
                        <span class="truncate flex-1">{{ ev.title }}</span>
                        <span class="text-[8px] bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300 px-1 py-0.2 rounded font-semibold">{{ ev.pointsReward }}p</span>
                        <IconEyeOff v-if="ev.visible === false" class="size-2 text-muted-foreground shrink-0" />
                      </button>
                    </div>
                  </div>

                  <p v-if="!s.chapters?.length" class="text-[10px] text-muted-foreground/60 italic pl-2">
                    Aucun chapitre.
                  </p>
                </div>
              </div>

              <p v-if="!course.sessions?.length" class="text-xs text-muted-foreground text-center py-4">
                Créez une séance pour voir la structure.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>

    </div>

    <!-- Help Markdown Dialog (reused) -->
    <Dialog :open="isMarkdownHelpOpen" @update:open="(v: any) => (isMarkdownHelpOpen = v)">
      <DialogContent class="max-w-3xl">
        <DialogHeader>
          <DialogTitle>Aide Markdown</DialogTitle>
          <DialogDescription>Raccourci des syntaxes les plus utiles.</DialogDescription>
        </DialogHeader>

        <div class="space-y-4 text-sm">
          <div class="grid grid-cols-2 gap-4">
            <div class="rounded-lg border p-3">
              <p class="font-medium text-xs uppercase tracking-wide text-muted-foreground">Titres</p>
              <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
                # Titre 1<br />
                ## Titre 2<br />
                ### Titre 3
              </code>
            </div>

            <div class="rounded-lg border p-3">
              <p class="font-medium text-xs uppercase tracking-wide text-muted-foreground">Mise en forme</p>
              <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
                **gras** · *italique* · ~~barré~~
              </code>
            </div>

            <div class="rounded-lg border p-3">
              <p class="font-medium text-xs uppercase tracking-wide text-muted-foreground">Listes</p>
              <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
                - Élément<br />
                - Élément<br />
                1. Étape<br />
                2. Étape
              </code>
            </div>

            <div class="rounded-lg border p-3">
              <p class="font-medium text-xs uppercase tracking-wide text-muted-foreground">Liens et images</p>
              <code class="mt-1 block rounded bg-muted px-2 py-1 font-mono text-xs">
                [Lien](https://example.com)<br />
                ![Image](https://example.com/img.png)
              </code>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button @click="isMarkdownHelpOpen = false">Fermer</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- Create Course Dialog -->
    <Dialog :open="isCreateCourseOpen" @update:open="(v: any) => (isCreateCourseOpen = v)">
      <DialogContent class="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Créer un nouveau cours</DialogTitle>
          <DialogDescription>
            Saisissez les informations et la scénarisation globale de votre nouveau cours.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4 py-2 text-sm">
          <div class="space-y-1">
            <Label for="newTitle" class="text-xs font-semibold">Titre du cours</Label>
            <Input id="newTitle" v-model="newCourseTitle" placeholder="Ex: Algorithmique et structures de données" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <Label for="newSemester" class="text-xs font-semibold">Semestre</Label>
              <Input id="newSemester" v-model="newCourseSemester" placeholder="Ex: S1" />
            </div>
            <div class="space-y-1">
              <Label for="newLevel" class="text-xs font-semibold">Niveau</Label>
              <Input id="newLevel" v-model="newCourseLevel" placeholder="Ex: Débutant" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <Label for="newTheme" class="text-xs font-semibold">Thème</Label>
              <Input id="newTheme" v-model="newCourseTheme" placeholder="Ex: Général" />
            </div>
            <div class="space-y-1">
              <Label for="newCategory" class="text-xs font-semibold">Catégorie</Label>
              <Select id="newCategory" v-model="newCourseCategory">
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
            <Label for="newAccent" class="text-xs font-semibold">Couleur d'accent</Label>
            <div class="flex items-center gap-2">
              <input
                id="newAccent"
                type="color"
                v-model="newCourseAccent"
                class="size-8 cursor-pointer rounded border bg-transparent shrink-0"
              />
              <Input v-model="newCourseAccent" class="font-mono text-xs h-8" />
            </div>
          </div>

          <div class="space-y-1">
            <Label for="newContext" class="text-xs font-semibold">Pitch / Description courte</Label>
            <textarea
              id="newContext"
              v-model="newCourseContext"
              rows="2"
              placeholder="Description rapide..."
              class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
          </div>

          <div class="space-y-1">
            <Label for="newScenario" class="text-xs font-semibold">Scénario de cours (Histoire / Univers)</Label>
            <textarea
              id="newScenario"
              v-model="newCourseScenario"
              rows="3"
              placeholder="Saisissez la scénarisation ou le contexte narratif du cours..."
              class="w-full rounded-md border border-input bg-transparent px-3 py-1.5 text-xs shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
            ></textarea>
          </div>

          <div class="flex items-center gap-2 pt-1">
            <Checkbox id="newVisible" :checked="newCourseVisible" @update:checked="(val: any) => { newCourseVisible = val; }" />
            <Label for="newVisible" class="text-xs font-semibold">Visible pour les étudiants</Label>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" @click="isCreateCourseOpen = false">Annuler</Button>
          <Button @click="submitCreateCourse">Créer le cours</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from "vue";
import { 
  IconPlus, 
  IconTrash, 
  IconCopy, 
  IconEye, 
  IconEyeOff, 
  IconChevronUp, 
  IconChevronDown, 
  IconSettings, 
  IconFolder, 
  IconFileText, 
  IconClipboardCheck, 
  IconUpload,
  IconLayoutList,
  IconBook,
  IconLayoutSidebar
} from "@tabler/icons-vue";
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
import { Checkbox } from "@/components/ui/checkbox";
import { Separator } from "@/components/ui/separator";
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

// Store
const store = useCoursesStore();
const courses = computed(() => store.courses);

// Navigation IDs
const selectedCourseId = ref<number | string | null>(null);
const selectedSessionId = ref<string>("");
const highlightedElementId = ref<string | null>(null);
const expandedBlocks = ref<Record<string, boolean>>({});

const isMarkdownHelpOpen = ref(false);
const isLeftPanelVisible = ref(true);

// Active objects
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

// Course parameter fields
const cTitle = ref("");
const cTheme = ref("");
const cLevel = ref("");
const cCategory = ref("other");
const cAccent = ref("#7c3aed");
const cSemester = ref("");
const cVisible = ref(true);
const cContext = ref("");
const cScenario = ref("");

// Session fields
const sTitle = ref("");
const sPitch = ref("");
const sVisible = ref(true);

// Total outline count
const totalOutlineItems = computed(() => {
  if (!course.value) return 0;
  let count = 0;
  for (const s of course.value.sessions || []) {
    count += (s.chapters || []).length;
    for (const ch of s.chapters || []) {
      count += (ch.pages || []).length;
      count += (ch.evaluations || []).length;
    }
  }
  return count;
});

// Watch course selection
watch(
  course,
  (c) => {
    if (!c) return;
    cTitle.value = c.title;
    cTheme.value = c.theme || "";
    cLevel.value = c.level || "";
    cCategory.value = c.category || "other";
    cAccent.value = c.accentColor || "#7c3aed";
    cSemester.value = c.semester || "";
    cVisible.value = c.visible !== false;
    cContext.value = c.context || "";
    cScenario.value = c.scenario || "";
  },
  { immediate: true },
);

// Watch active session
watch(session, (s) => {
  sTitle.value = s?.title || "";
  sPitch.value = s?.pitch || "";
  sVisible.value = s?.visible !== false;
});

// Load courses on start
onMounted(async () => {
  if (!store.loaded) await store.fetchCourses();
  if (store.courses[0]) selectCourse(store.courses[0].id);
});

function selectCourse(id: number | string) {
  selectedCourseId.value = id;
  const c = store.getCourse(id);
  selectedSessionId.value = c?.sessions?.[0] ? String(c.sessions[0].id) : "";
}

// Collapsible helper
function toggleBlock(key: string) {
  expandedBlocks.value[key] = !expandedBlocks.value[key];
}

// Interactive scrolling and flashing
async function scrollToElement(elementId: string, sessionId?: number | string) {
  if (sessionId) {
    selectedSessionId.value = String(sessionId);
    await nextTick();
  }
  
  const el = document.getElementById(elementId);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    highlightedElementId.value = elementId;
    setTimeout(() => {
      if (highlightedElementId.value === elementId) {
        highlightedElementId.value = null;
      }
    }, 2000);
  }
}

// Interleaved sorting of pages/evaluations
interface ChapterItem {
  key: string;
  type: 'page' | 'eval';
  id: number | string;
  position: number;
  data: any;
}

function getChapterItems(ch: any): ChapterItem[] {
  const items: ChapterItem[] = [];
  for (const p of ch.pages || []) {
    items.push({ key: `page-${p.id}`, type: 'page', id: p.id, position: p.position ?? 0, data: p });
  }
  for (const ev of ch.evaluations || []) {
    items.push({ key: `eval-${ev.id}`, type: 'eval', id: ev.id, position: ev.position ?? 0, data: ev });
  }
  return items.sort((a, b) => a.position - b.position || Number(a.id) - Number(b.id));
}

// Course creation fields
const isCreateCourseOpen = ref(false);
const newCourseTitle = ref("");
const newCourseTheme = ref("");
const newCourseCategory = ref("other");
const newCourseAccent = ref("#7c3aed");
const newCourseLevel = ref("Débutant");
const newCourseSemester = ref("S1");
const newCourseContext = ref("");
const newCourseScenario = ref("");
const newCourseVisible = ref(true);

// CRUD Operations: Course
function createCourse() {
  newCourseTitle.value = "Nouveau cours";
  newCourseTheme.value = "Général";
  newCourseCategory.value = "other";
  newCourseAccent.value = "#7c3aed";
  newCourseLevel.value = "Débutant";
  newCourseSemester.value = "S1";
  newCourseContext.value = "";
  newCourseScenario.value = "";
  newCourseVisible.value = true;
  isCreateCourseOpen.value = true;
}

async function submitCreateCourse() {
  const c = await store.createCourse({
    title: newCourseTitle.value,
    theme: newCourseTheme.value,
    category: newCourseCategory.value,
    accentColor: newCourseAccent.value,
    level: newCourseLevel.value,
    semester: newCourseSemester.value,
    context: newCourseContext.value,
    scenario: newCourseScenario.value,
    visible: newCourseVisible.value,
  });
  isCreateCourseOpen.value = false;
  selectCourse(c.id);
  showToast("Cours créé");
}

async function removeCourse(id: number | string) {
  if (
    !(await confirmDialog({
      title: "Supprimer ce cours ?",
      description: "Séances, chapitres, pages et évaluations seront supprimés définitivement.",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deleteCourse(id);
  if (selectedCourseId.value === id) selectedCourseId.value = null;
  showToast("Cours supprimé");
}

async function saveCourse() {
  if (!selectedCourseId.value) return;
  await store.updateCourse(selectedCourseId.value, {
    title: cTitle.value,
    theme: cTheme.value,
    level: cLevel.value,
    category: cCategory.value,
    accentColor: cAccent.value,
    semester: cSemester.value,
    visible: cVisible.value,
    context: cContext.value,
    scenario: cScenario.value,
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
    semester: src.semester,
    visible: src.visible,
  });
  for (const s of src.sessions || []) {
    const ns = await store.addSession(nc.id, {
      title: s.title,
      pitch: s.pitch,
      visible: s.visible,
      renderConfig: s.renderConfig,
    });
    for (const ch of s.chapters || []) {
      const nch = await store.addChapter(ns.id, { title: ch.title, visible: ch.visible });
      for (const p of ch.pages || [])
        await store.addPage(nch.id, {
          title: p.title,
          content: p.content,
          points: p.points,
          visible: p.visible,
        });
      for (const ev of ch.evaluations || [])
        await store.addEvaluation(nch.id, {
          title: ev.title,
          description: ev.description,
          pointsReward: ev.pointsReward,
          visible: ev.visible,
        });
    }
  }
  selectCourse(nc.id);
  showToast("Cours dupliqué");
}

// CRUD Operations: Session
async function addSession() {
  const s = await store.addSession(selectedCourseId.value!, {
    title: `Séance ${course.value?.sessions?.length + 1 || 1}`,
  });
  selectedSessionId.value = String(s.id);
  showToast("Séance créée");
}

async function removeSession() {
  if (!session.value) return;
  if (
    !(await confirmDialog({
      title: "Supprimer cette séance ?",
      description: "Toutes les chapitres, pages et quiz de cette séance seront effacés.",
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
  if (!session.value) return;
  await store.updateSession(session.value.id, { 
    title: sTitle.value, 
    pitch: sPitch.value,
    visible: sVisible.value
  });
  showToast("Séance enregistrée");
}

async function updateSessionConfig(s: any, key: string, val: any) {
  const config = { ...(s.renderConfig || { allowUpload: true, allowedTypes: ['file', 'image'], maxFiles: 1 }) };
  config[key] = val;
  await store.updateSession(s.id, { renderConfig: config });
  showToast("Configuration de séance enregistrée");
}

async function toggleAllowedType(s: any, type: string, checked: boolean) {
  const config = { ...(s.renderConfig || { allowUpload: true, allowedTypes: ['file', 'image'], maxFiles: 1 }) };
  const current = config.allowedTypes ? [...config.allowedTypes] : [];
  if (checked) {
    if (!current.includes(type)) current.push(type);
  } else {
    const idx = current.indexOf(type);
    if (idx !== -1) current.splice(idx, 1);
  }
  config.allowedTypes = current;
  await store.updateSession(s.id, { renderConfig: config });
  showToast("Filtres de types de fichiers mis à jour");
}

// CRUD Operations: Chapter
async function addChapter() {
  const ch = await store.addChapter(session.value.id, {
    title: `Chapitre ${session.value?.chapters?.length + 1 || 1}`,
    visible: true,
  });
  showToast("Chapitre créé");
  scrollToElement(`chapter-${ch.id}`);
}

async function removeChapterBlock(ch: any) {
  if (
    !(await confirmDialog({
      title: "Supprimer ce chapitre ?",
      description: "Toutes les pages et quiz associés seront supprimés.",
      confirmText: "Supprimer",
    }))
  )
    return;
  await store.deleteChapter(ch.id);
  showToast("Chapitre supprimé");
}

async function updateChapterTitle(ch: any, val: string) {
  if (!val) return;
  await store.updateChapter(ch.id, { title: val });
  showToast("Titre du chapitre mis à jour");
}

async function toggleChapterVisibility(ch: any) {
  const nextVal = ch.visible !== false ? false : true;
  await store.updateChapter(ch.id, { visible: nextVal });
  showToast(nextVal ? "Chapitre visible" : "Chapitre masqué");
}

// CRUD Operations: Items (Pages & Evaluations)
async function addPageBlock(ch: any) {
  const p = await store.addPage(ch.id, {
    title: "Nouvelle page",
    content: "# Titre \nÉcrivez le contenu ici...",
    points: 5,
    visible: true,
  });
  showToast("Page créée");
  const key = `page-${p.id}`;
  expandedBlocks.value[key] = true;
  scrollToElement(key);
}

async function addEvaluationBlock(ch: any) {
  const ev = await store.addEvaluation(ch.id, {
    title: "Nouvelle évaluation",
    description: "Description de l'évaluation",
    pointsReward: 20,
    visible: true,
  });
  showToast("Évaluation créée");
  const key = `eval-${ev.id}`;
  expandedBlocks.value[key] = true;
  scrollToElement(key);
}

async function removeItemBlock(item: ChapterItem) {
  if (
    !(await confirmDialog({
      title: item.type === 'page' ? "Supprimer la page ?" : "Supprimer l'évaluation ?",
      confirmText: "Supprimer",
    }))
  )
    return;
  if (item.type === 'page') {
    await store.deletePage(item.id);
  } else {
    await store.deleteEvaluation(item.id);
  }
  showToast(item.type === 'page' ? "Page supprimée" : "Évaluation supprimée");
}

async function updateItemTitle(item: ChapterItem, val: string) {
  if (!val) return;
  if (item.type === 'page') {
    await store.updatePage(item.id, { title: val });
  } else {
    await store.updateEvaluation(item.id, { title: val });
  }
  showToast("Titre mis à jour");
}

async function updateItemPoints(item: ChapterItem, points: number) {
  if (item.type === 'page') {
    await store.updatePage(item.id, { points });
  } else {
    await store.updateEvaluation(item.id, { pointsReward: points });
  }
  showToast("Points mis à jour");
}

async function toggleItemVisibility(item: ChapterItem) {
  const nextVal = item.data.visible !== false ? false : true;
  if (item.type === 'page') {
    await store.updatePage(item.id, { visible: nextVal });
  } else {
    await store.updateEvaluation(item.id, { visible: nextVal });
  }
  showToast(nextVal ? "Élément visible" : "Élément masqué");
}

async function savePageContent(page: any) {
  await store.updatePage(page.id, { content: page.content });
  showToast("Contenu de la page enregistré");
}

// Reordering Operations
async function moveSession(sess: any, direction: 'up' | 'down') {
  if (!course.value?.sessions) return;
  await reorderElements(course.value.sessions, sess.id, direction, store.updateSession);
}

async function moveChapter(ch: any, direction: 'up' | 'down') {
  if (!session.value?.chapters) return;
  await reorderElements(session.value.chapters, ch.id, direction, store.updateChapter);
}

async function moveItem(item: ChapterItem, ch: any, direction: 'up' | 'down') {
  const items = getChapterItems(ch);
  const sorted = [...items].sort((a, b) => a.position - b.position);
  const index = sorted.findIndex(el => el.key === item.key);
  if (index === -1) return;
  const targetIndex = direction === 'up' ? index - 1 : index + 1;
  if (targetIndex < 0 || targetIndex >= sorted.length) return;
  
  const currentEl = sorted[index];
  const targetEl = sorted[targetIndex];
  if (!currentEl || !targetEl) return;

  // Swap positions in database
  const tempPos = currentEl.position;
  const targetPos = targetEl.position;
  
  const nextPos = targetPos;
  const nextTargetPos = tempPos === targetPos ? (direction === 'up' ? tempPos + 1 : tempPos - 1) : tempPos;
  
  // Save page or evaluation
  if (currentEl.type === 'page') {
    await store.updatePage(currentEl.id, { position: nextPos });
  } else {
    await store.updateEvaluation(currentEl.id, { position: nextPos });
  }
  
  if (targetEl.type === 'page') {
    await store.updatePage(targetEl.id, { position: nextTargetPos });
  } else {
    await store.updateEvaluation(targetEl.id, { position: nextTargetPos });
  }
  
  showToast("Ordre de l'élément mis à jour");
}

async function reorderElements(
  elements: any[], 
  activeId: number | string, 
  direction: 'up' | 'down', 
  updateFn: (id: any, patch: any) => Promise<any>
) {
  const sorted = [...elements].sort((a, b) => (a.position || 0) - (b.position || 0));
  const index = sorted.findIndex(el => el.id === activeId);
  if (index === -1) return;
  const targetIndex = direction === 'up' ? index - 1 : index + 1;
  if (targetIndex < 0 || targetIndex >= sorted.length) return;
  
  const temp = sorted[index].position || 0;
  const targetTemp = sorted[targetIndex].position || 0;
  
  const newPos = targetTemp;
  const newTargetPos = temp === targetTemp ? (direction === 'up' ? temp + 1 : temp - 1) : temp;
  
  await updateFn(sorted[index].id, { position: newPos });
  await updateFn(sorted[targetIndex].id, { position: newTargetPos });
  showToast("Ordre mis à jour");
}

function openMarkdownHelp() {
  isMarkdownHelpOpen.value = true;
}
</script>

<style scoped>
@keyframes highlight-flash {
  0% {
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.5);
    background-color: rgba(124, 58, 237, 0.05);
    border-color: rgba(124, 58, 237, 0.5);
  }
  50% {
    box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.3);
  }
  100% {
    box-shadow: 0 0 0 0px transparent;
    background-color: transparent;
  }
}

.element-highlight {
  animation: highlight-flash 1.8s ease-out;
  border-radius: 0.5rem;
}

.outline-tree-container::-webkit-scrollbar {
  width: 4px;
}
.outline-tree-container::-webkit-scrollbar-track {
  background: transparent;
}
.outline-tree-container::-webkit-scrollbar-thumb {
  background: var(--muted);
  border-radius: 2px;
}
</style>
