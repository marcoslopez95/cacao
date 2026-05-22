<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const userName = computed(() => page.props.auth?.user?.name ?? '')
</script>

<template>
    <div class="portal-welcome">
        <h1 class="portal-welcome__heading">Bienvenido/a, {{ userName }}</h1>
        <p class="portal-welcome__message">
            Tu portal académico está siendo preparado. Pronto podrás gestionar tus secciones, horarios y notas desde aquí.
        </p>
    </div>
</template>
