<template>
    <div class="fixed right-4 bottom-6 z-50">
        <transition-group name="toast" tag="div" class="flex flex-col items-end gap-2">
            <div v-for="t in toasts" :key="t.id"
                class="bg-white px-4 py-2 rounded shadow-md flex items-center gap-3 max-w-xs w-full">
                <div class="text-sm truncate">{{ t.msg }}</div>
                <button @click="remove(t.id)" class="text-xs text-gray-400">✕</button>
            </div>
        </transition-group>
    </div>
</template>

<script setup lang="ts">
import { toasts, removeToast } from '../composables/useToast'
const remove = (id: number) => removeToast(id)
</script>

<style scoped>
.toast-enter-from {
    transform: translateY(8px);
    opacity: 0
}

.toast-enter-active {
    transition: all 220ms cubic-bezier(.2, .8, .2, 1)
}

.toast-leave-to {
    transform: translateY(8px);
    opacity: 0
}

.toast-leave-active {
    transition: all 180ms cubic-bezier(.2, .8, .2, 1)
}
</style>
