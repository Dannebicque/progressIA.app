<template>
    <Card>
        <CardContent class="flex items-center gap-4 py-5">
            <div class="grid size-11 shrink-0 place-items-center rounded-xl" :class="tintClass">
                <component :is="icon" class="size-5" />
            </div>
            <div class="min-w-0">
                <div class="text-sm text-muted-foreground">{{ label }}</div>
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold tracking-tight">{{ value }}</span>
                    <span v-if="sub" class="text-sm text-muted-foreground">{{ sub }}</span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'
import { Card, CardContent } from '@/components/ui/card'

const props = withDefaults(defineProps<{
    icon: Component
    label: string
    value: string | number
    sub?: string
    tint?: 'indigo' | 'pink' | 'amber' | 'emerald' | 'sky' | 'violet'
}>(), { tint: 'indigo' })

const tintClass = computed(() => ({
    indigo: 'bg-indigo-100 text-indigo-700',
    pink: 'bg-pink-100 text-pink-700',
    amber: 'bg-amber-100 text-amber-700',
    emerald: 'bg-emerald-100 text-emerald-700',
    sky: 'bg-sky-100 text-sky-700',
    violet: 'bg-violet-100 text-violet-700',
}[props.tint]))
</script>
