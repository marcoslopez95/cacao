<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <div style="padding:0.25rem;display:flex;flex-direction:column;gap:0.25rem;font-size:var(--text-sm);">
        <div style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0.5rem;">
            <UserInfo :user="user" :show-email="true" />
        </div>
        <div style="height:1px;background:var(--border);margin:0.25rem 0;" />
        <Link
            :href="edit()"
            prefetch
            style="display:flex;align-items:center;gap:0.5rem;padding:0.375rem 0.5rem;border-radius:0.375rem;color:var(--text-primary);text-decoration:none;"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
            Settings
        </Link>
        <div style="height:1px;background:var(--border);margin:0.25rem 0;" />
        <Link
            :href="logout()"
            @click="handleLogout"
            as="button"
            style="display:flex;width:100%;align-items:center;gap:0.5rem;padding:0.375rem 0.5rem;border-radius:0.375rem;color:var(--text-primary);text-decoration:none;background:transparent;border:none;cursor:pointer;"
            data-test="logout-button"
        >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Log out
        </Link>
    </div>
</template>
