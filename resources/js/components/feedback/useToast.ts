import { reactive } from 'vue'

export type ToastVariant = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

export interface ToastItem {
    id: number
    message: string
    variant: ToastVariant
    duration: number
    leaving: boolean
    action?: { label: string; onClick: () => void }
}

interface ToastOptions {
    message: string
    variant?: ToastVariant
    duration?: number
    action?: { label: string; onClick: () => void }
}

const state = reactive<{ toasts: ToastItem[] }>({ toasts: [] })
let nextId = 0

function dismiss(id: number): void {
    const item = state.toasts.find(t => t.id === id)
    if (!item) return
    item.leaving = true
    setTimeout(() => {
        const idx = state.toasts.findIndex(t => t.id === id)
        if (idx !== -1) state.toasts.splice(idx, 1)
    }, 160)
}

function toast(opts: ToastOptions): void {
    const id = ++nextId
    state.toasts.push({
        id,
        message: opts.message,
        variant: opts.variant ?? 'neutral',
        duration: opts.duration ?? 4000,
        leaving: false,
        action: opts.action,
    })
    if (opts.duration !== 0) {
        setTimeout(() => dismiss(id), opts.duration ?? 4000)
    }
}

export function useToast() {
    return { toast, dismiss, toasts: state.toasts }
}
