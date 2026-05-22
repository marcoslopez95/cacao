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
            Tu portal estudiantil está siendo preparado. Desde aquí podrás gestionar tu inscripción, consultar tus notas y mucho más.
        </p>
    </div>
</template>
