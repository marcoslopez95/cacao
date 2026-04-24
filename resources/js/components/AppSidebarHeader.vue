<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        style="display:flex;height:4rem;flex-shrink:0;align-items:center;gap:0.5rem;border-bottom:1px solid var(--border);padding:0 1.5rem;transition:width height 0.2s ease;"
    >
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
