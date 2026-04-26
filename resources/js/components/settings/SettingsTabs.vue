<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { edit as profileEdit } from '@/routes/profile';
import { edit as securityEdit } from '@/routes/security';
import { edit as appearanceEdit } from '@/routes/appearance';

const url = computed(() => usePage().url);
</script>

<template>
    <nav class="sp-tabs" role="tablist">
        <Link
            :href="profileEdit.url()"
            class="sp-tab"
            :class="{ active: url.startsWith('/settings/profile') }"
            role="tab"
            :aria-selected="url.startsWith('/settings/profile')"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
            </svg>
            <span>Perfil</span>
        </Link>

        <Link
            :href="securityEdit.url()"
            class="sp-tab"
            :class="{ active: url.startsWith('/settings/security') }"
            role="tab"
            :aria-selected="url.startsWith('/settings/security')"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <span>Seguridad</span>
        </Link>

        <Link
            :href="appearanceEdit.url()"
            class="sp-tab"
            :class="{ active: url.startsWith('/settings/appearance') }"
            role="tab"
            :aria-selected="url.startsWith('/settings/appearance')"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 0 0 18"/><path d="M12 3v18"/>
            </svg>
            <span>Apariencia</span>
        </Link>
    </nav>
</template>
