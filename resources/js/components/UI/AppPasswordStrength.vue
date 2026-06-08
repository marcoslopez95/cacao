<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ password: string }>()

const score = computed<number>(() => {
    const p = props.password

    if (!p) {
return 0
}

    let s = 0

    if (p.length >= 8)  {
s++
}

    if (p.length >= 12) {
s++
}

    if (/[A-Z]/.test(p)) {
s++
}

    if (/[0-9]/.test(p)) {
s++
}

    if (/[^A-Za-z0-9]/.test(p)) {
s++
}

    return s
})

const label = computed(() => {
    if (score.value === 0) {
return ''
}

    if (score.value <= 1) {
return 'Muy débil'
}

    if (score.value === 2) {
return 'Débil'
}

    if (score.value === 3) {
return 'Moderada'
}

    if (score.value === 4) {
return 'Fuerte'
}

    return 'Muy fuerte'
})

function barColor(i: number): string {
    if (i >= score.value) {
return 'var(--border)'
}

    if (score.value <= 2) {
return 'var(--danger, #e53e3e)'
}

    if (score.value === 3) {
return 'var(--warning, #d97706)'
}

    return 'var(--success, #16a34a)'
}
</script>

<template>
    <div v-if="password" class="uf-password-strength">
        <div class="uf-strength-bars">
            <span
                v-for="i in 5"
                :key="i"
                class="uf-strength-bar"
                :style="{ background: barColor(i) }"
            />
        </div>
        <span class="uf-strength-label">{{ label }}</span>
    </div>
</template>
