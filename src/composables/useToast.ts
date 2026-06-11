import { toast } from 'vue-sonner'

type ToastType = 'success' | 'error' | 'info' | 'warning' | string

// Backwards-compatible wrapper over vue-sonner so existing showToast() calls keep working.
export function showToast(msg: string, type: ToastType = 'success', duration = 3000) {
  const opts = { duration }
  switch (type) {
    case 'error':
      return toast.error(msg, opts)
    case 'info':
      return toast.info(msg, opts)
    case 'warning':
      return toast.warning(msg, opts)
    case 'success':
      return toast.success(msg, opts)
    default:
      return toast(msg, opts)
  }
}
