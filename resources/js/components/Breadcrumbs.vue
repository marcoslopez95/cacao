<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();
</script>

<template>
    <nav aria-label="breadcrumb">
        <ol style="display:flex;align-items:center;gap:0.375rem;font-size:var(--text-sm);">
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <li style="display:flex;align-items:center;gap:0.375rem;">
                    <template v-if="index === breadcrumbs.length - 1">
                        <span style="color:var(--text-primary);font-weight:500;">{{ item.title }}</span>
                    </template>
                    <template v-else>
                        <Link :href="item.href" style="color:var(--text-muted);text-decoration:none;" :class="'hover:text-primary'">
                            {{ item.title }}
                        </Link>
                    </template>
                </li>
                <li v-if="index !== breadcrumbs.length - 1" aria-hidden="true" style="color:var(--text-muted);">
                    /
                </li>
            </template>
        </ol>
    </nav>
</template>
