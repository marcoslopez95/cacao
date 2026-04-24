<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <nav style="padding:0 0.5rem;">
        <div style="margin-bottom:0.25rem;padding:0 0.25rem;font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">
            Platform
        </div>
        <div style="display:flex;flex-direction:column;gap:0.125rem;">
            <Link
                v-for="item in items"
                :key="item.title"
                :href="item.href"
                style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.5rem;border-radius:0.375rem;font-size:var(--text-sm);text-decoration:none;color:var(--text-secondary);"
                :style="isCurrentUrl(item.href) ? { background: 'var(--bg-subtle)', color: 'var(--text-primary)', fontWeight: '500' } : {}"
            >
                <component v-if="item.icon" :is="item.icon" style="width:1rem;height:1rem;" />
                <span>{{ item.title }}</span>
            </Link>
        </div>
    </nav>
</template>
