<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import AppTopbar from '@/components/AppTopbar.vue'
import Toast from '@/components/feedback/Toast.vue'
import type { BreadcrumbItem } from '@/types'

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItem[]
}>(), {
    breadcrumbs: () => [],
})
</script>

<template>
    <div class="app-shell">
        <AppSidebar />
        <main class="app-main">
            <AppTopbar :breadcrumbs="breadcrumbs" />
            <div class="app-content">
                <slot />
            </div>
        </main>
    </div>
    <Toast />
</template>
