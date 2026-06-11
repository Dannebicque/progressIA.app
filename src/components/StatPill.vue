<template>
    <div class="rounded-xl border bg-card px-4 py-3 text-center">
        <div class="text-xs text-muted-foreground">{{ label }}</div>
        <div class="text-lg font-semibold" :style="accent ? { color: accent } : undefined">{{ value }}</div>
    </div>
</template>

<script setup lang="ts">
defineProps<{ label: string; value: string | number; accent?: string }>()
</script>
