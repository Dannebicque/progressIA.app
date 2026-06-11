<template>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <IconMarkdown class="size-4" /> Markdown
            </div>
            <Textarea v-model="value" :class="['resize-y font-mono text-sm', editorHeightClass]" spellcheck="false"
                placeholder="# Titre&#10;&#10;Votre contenu en Markdown…" />
        </div>
        <div class="space-y-1.5">
            <div class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <IconEye class="size-4" /> Aperçu
            </div>
            <div :class="['overflow-auto rounded-md border bg-card p-4', previewHeightClass]">
                <MarkdownViewer :source="value" />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { IconMarkdown, IconEye } from '@tabler/icons-vue'
import MarkdownViewer from './MarkdownViewer.vue'
import { Textarea } from '@/components/ui/textarea'

const props = withDefaults(defineProps<{
    modelValue?: string
    editorHeightClass?: string
    previewHeightClass?: string
}>(), {
    editorHeightClass: 'h-72',
    previewHeightClass: 'h-72',
})
const emit = defineEmits(['update:modelValue'])
const value = ref(props.modelValue || '')

watch(value, (v) => emit('update:modelValue', v))
watch(() => props.modelValue, (v) => { if (v !== value.value) value.value = v || '' })
</script>
