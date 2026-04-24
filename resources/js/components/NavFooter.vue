<script setup lang="ts">
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

type Props = {
    items: NavItem[];
    class?: string;
};

defineProps<Props>();
</script>

<template>
    <div :class="$props.class">
        <nav style="display:flex;flex-direction:column;gap:0.25rem;">
            <a
                v-for="item in items"
                :key="item.title"
                :href="toUrl(item.href)"
                target="_blank"
                rel="noopener noreferrer"
                style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.5rem;border-radius:0.375rem;color:var(--text-muted);text-decoration:none;font-size:var(--text-sm);"
            >
                <component v-if="item.icon" :is="item.icon" style="width:1rem;height:1rem;" />
                <span>{{ item.title }}</span>
            </a>
        </nav>
    </div>
</template>
