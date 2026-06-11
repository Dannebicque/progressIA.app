<template>
    <AlertDialog :open="confirmState.open" @update:open="(v: boolean) => { if (!v) resolveConfirm(false) }">
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ confirmState.title }}</AlertDialogTitle>
                <AlertDialogDescription v-if="confirmState.description">{{ confirmState.description }}</AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="resolveConfirm(false)">{{ confirmState.cancelText }}</AlertDialogCancel>
                <AlertDialogAction class="bg-destructive text-white hover:bg-destructive/90" @click="resolveConfirm(true)">
                    {{ confirmState.confirmText }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>

<script setup lang="ts">
import { confirmState, resolveConfirm } from '@/composables/useConfirm'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
</script>
