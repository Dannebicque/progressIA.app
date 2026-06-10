import { reactive } from 'vue'

export const toasts = reactive<Array<{ id: number; msg: string; type?: string }>>([])

export function showToast(msg: string, type = 'success', duration = 3000) {
  const id = Date.now() + Math.floor(Math.random() * 1000)
  toasts.push({ id, msg, type })
  setTimeout(() => {
    const idx = toasts.findIndex((t) => t.id === id)
    if (idx > -1) toasts.splice(idx, 1)
  }, duration)
}

export function removeToast(id: number) {
  const idx = toasts.findIndex((t) => t.id === id)
  if (idx > -1) toasts.splice(idx, 1)
}
