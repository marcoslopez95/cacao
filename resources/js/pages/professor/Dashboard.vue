<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'

const page = usePage()
const userName = computed(() => page.props.auth?.user?.name ?? '')
</script>

<template>
    <AppLayout>
        <div class="portal-welcome">
            <h1 class="portal-welcome__heading">Bienvenido/a, {{ userName }}</h1>
            <p class="portal-welcome__message">
                Tu portal académico está siendo preparado. Pronto podrás gestionar tus secciones, horarios y notas desde aquí.
            </p>
        </div>
    </AppLayout>
</template>
