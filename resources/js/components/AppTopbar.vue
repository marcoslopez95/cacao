<script setup lang="ts">
import type { BreadcrumbItem } from '@/types'

defineProps<{
    breadcrumbs?: BreadcrumbItem[]
}>()
</script>

<template>
    <header class="app-topbar">
        <nav v-if="breadcrumbs && breadcrumbs.length" style="display:flex;align-items:center;gap:6px;flex:1;">
            <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <span v-if="i > 0" style="color:var(--topbar-fg);opacity:0.4;font-size:13px;">/</span>
                <a
                    :href="crumb.href"
                    style="font-size:13px;font-weight:500;color:var(--topbar-fg);text-decoration:none;opacity:0.7;"
                    :style="i === breadcrumbs!.length - 1 ? 'opacity:1;' : ''"
                >{{ crumb.title }}</a>
            </template>
        </nav>
        <div v-else style="flex:1;" />
    </header>
</template>
