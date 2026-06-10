<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <textarea v-model="value" class="w-full h-64 p-3 border rounded" />
        <div class="border rounded p-3 bg-white overflow-auto h-64">
            <MarkdownViewer :source="value" />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, defineEmits } from 'vue'
import MarkdownViewer from './MarkdownViewer.vue'

const props = defineProps<{ modelValue?: string }>()
const emit = defineEmits(['update:modelValue'])
const value = ref(props.modelValue || '')

watch(value, (v) => emit('update:modelValue', v))
</script>

<style scoped></style>
