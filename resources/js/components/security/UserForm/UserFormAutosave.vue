<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { AutosaveState } from '@/types/userForm'

const props = defineProps<{ autosave: AutosaveState }>()

const TICK_MS = 1000
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | null = null

function stopTicking(): void {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
}

watch(
    () => [props.autosave.status, props.autosave.when] as const,
    ([status]) => {
        now.value = Date.now()
        stopTicking()

        if (status === 'saved') {
            timer = setInterval(() => {
                now.value = Date.now()
            }, TICK_MS)
        }
    },
    { immediate: true },
)

onBeforeUnmount(stopTicking)

const savedAgo = computed<string>(() => {
    const when = props.autosave.when

    if (!when) {
        return 'Guardado'
    }

    const seconds = Math.max(0, Math.floor((now.value - when.getTime()) / 1000))

    if (seconds < 60) {
        return `Guardado hace ${seconds}s`
    }

    const minutes = Math.floor(seconds / 60)

    return minutes < 60 ? `Guardado hace ${minutes} min` : `Guardado hace ${Math.floor(minutes / 60)} h`
})
</script>

<template>
    <span class="uf-autosave" :class="autosave.status">
        <span class="uf-autosave-dot" />
        <span v-if="autosave.status === 'saving'">Guardando…</span>
        <span v-else-if="autosave.status === 'saved'">{{ savedAgo }}</span>
        <span v-else-if="autosave.status === 'error'" :title="autosave.message">Error al guardar</span>
        <span v-else>Sin cambios</span>
    </span>
</template>
