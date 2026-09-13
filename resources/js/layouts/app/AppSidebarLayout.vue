<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { useMediaQuery } from '@vueuse/core'
import { ref, watch } from 'vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppTopbar from '@/components/AppTopbar.vue'
import Toast from '@/components/feedback/Toast.vue'
import type { BreadcrumbItem } from '@/types'

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItem[]
}>(), {
    breadcrumbs: () => [],
})

// Mobile sidebar drawer — desktop (>=1024px, Tailwind `lg`) keeps the sidebar
// static and always visible, matching the previous fixed-width behaviour.
const page = usePage()
const isDesktop = useMediaQuery('(min-width: 1024px)')
const sidebarOpen = ref(false)

function toggleSidebar(): void {
    sidebarOpen.value = !sidebarOpen.value
}

function closeSidebar(): void {
    sidebarOpen.value = false
}

watch(isDesktop, (desktop) => {
    if (desktop) {
        sidebarOpen.value = false
    }
})

watch(() => page.url, () => {
    sidebarOpen.value = false
})
</script>

<template>
    <div class="app-shell">
        <AppSidebar :class="{ 'app-sidebar--open': sidebarOpen }" />
        <div
            v-if="!isDesktop && sidebarOpen"
            class="app-sidebar-scrim"
            dusk="sidebar-scrim"
            @click="closeSidebar"
        />
        <main class="app-main">
            <AppTopbar :breadcrumbs="breadcrumbs" @toggle-sidebar="toggleSidebar" />
            <div class="app-content">
                <slot />
            </div>
        </main>
    </div>
    <Toast />
</template>
