import type { ComputedRef, Ref } from 'vue'
import { computed, onMounted, ref } from 'vue'
import type { Appearance, ResolvedAppearance } from '@/types'

export type { Appearance, ResolvedAppearance }

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>
    resolvedAppearance: ComputedRef<ResolvedAppearance>
    updateAppearance: (value: Appearance) => void
}

function applyTheme(value: Appearance): void {
    if (typeof window === 'undefined') return

    let theme: 'dark' | 'light'
    if (value === 'system') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    } else {
        theme = value
    }

    document.documentElement.setAttribute('data-theme', theme)
}

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') return
    document.cookie = `${name}=${value};path=/;max-age=${days * 86400};SameSite=Lax`
}

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') return false
    return window.matchMedia('(prefers-color-scheme: dark)').matches
}

export function updateTheme(value: Appearance): void {
    applyTheme(value)
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') return

    const stored = localStorage.getItem('cacao-theme') as Appearance | null
    applyTheme(stored ?? 'system')

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = localStorage.getItem('cacao-theme') as Appearance | null
        if (!current || current === 'system') applyTheme('system')
    })
}

const appearance = ref<Appearance>('system')

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const saved = localStorage.getItem('cacao-theme') as Appearance | null
        if (saved) appearance.value = saved
    })

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') return prefersDark() ? 'dark' : 'light'
        return appearance.value
    })

    function updateAppearance(value: Appearance): void {
        appearance.value = value
        localStorage.setItem('cacao-theme', value)
        setCookie('cacao-theme', value)
        applyTheme(value)
    }

    return { appearance, resolvedAppearance, updateAppearance }
}
