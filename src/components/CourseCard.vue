<template>
    <Card class="group relative overflow-hidden pt-0 transition hover:shadow-lg hover:-translate-y-0.5">
        <div class="h-1.5 w-full" :style="{ background: accent }"></div>
        <CardHeader class="pt-5">
            <div class="flex items-start gap-3">
                <div class="grid size-12 shrink-0 place-items-center rounded-xl text-white"
                    :style="{ background: `linear-gradient(135deg, ${accent}, ${accent}99)` }">
                    <IconSchool class="size-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <CardTitle class="truncate text-base">{{ course.title }}</CardTitle>
                    <CardDescription class="truncate">{{ course.theme }}<span v-if="course.context"> · {{ course.context }}</span></CardDescription>
                </div>
                <Badge variant="secondary" class="shrink-0">{{ course.level || 'Tous niveaux' }}</Badge>
            </div>
        </CardHeader>
        <CardContent>
            <p class="line-clamp-3 min-h-[3.5rem] text-sm text-muted-foreground">{{ course.scenario }}</p>
        </CardContent>
        <CardFooter class="justify-between">
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
                <span class="inline-flex items-center gap-1.5"><IconBook class="size-4" /> {{ course.sessions.length }} séances</span>
                <span class="inline-flex items-center gap-1.5"><IconStar class="size-4 text-amber-500" /> {{ course.sessions.length * 20 }} pts</span>
            </div>
            <RouterLink :to="`/course/${course.id}`">
                <Button size="sm">Commencer <IconArrowRight class="size-4" /></Button>
            </RouterLink>
        </CardFooter>
    </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { IconSchool, IconBook, IconStar, IconArrowRight } from '@tabler/icons-vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'

const props = defineProps<{ course: any }>()
const accent = computed(() => props.course?.accentColor || '#7c3aed')
</script>
