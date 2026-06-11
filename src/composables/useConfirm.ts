import { reactive } from 'vue'

interface ConfirmOptions {
  title: string
  description?: string
  confirmText?: string
  cancelText?: string
}

interface ConfirmState extends Required<ConfirmOptions> {
  open: boolean
  resolve: ((value: boolean) => void) | null
}

export const confirmState = reactive<ConfirmState>({
  open: false,
  title: '',
  description: '',
  confirmText: 'Confirmer',
  cancelText: 'Annuler',
  resolve: null,
})

// Promise-based confirmation backed by a shadcn AlertDialog (see ConfirmDialog.vue).
export function confirmDialog(opts: ConfirmOptions): Promise<boolean> {
  return new Promise((resolve) => {
    confirmState.title = opts.title
    confirmState.description = opts.description ?? ''
    confirmState.confirmText = opts.confirmText ?? 'Confirmer'
    confirmState.cancelText = opts.cancelText ?? 'Annuler'
    confirmState.resolve = resolve
    confirmState.open = true
  })
}

export function resolveConfirm(value: boolean) {
  confirmState.open = false
  confirmState.resolve?.(value)
  confirmState.resolve = null
}
