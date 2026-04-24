<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CacaoAvatar from '@/components/base/Avatar.vue';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { Team } from '@/types';

const page = usePage();
const user = page.props.auth.user;
const { getInitials } = useInitials();

const currentTeam = computed(() => page.props.currentTeam as Team | null);

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <div style="display:flex;align-items:center;gap:0.5rem;padding:0.5rem;">
        <CacaoAvatar
            :initials="getInitials(user.name)"
            :src="user.avatar ?? undefined"
            size="sm"
        />
        <div style="flex:1;display:grid;font-size:var(--text-sm);line-height:1.25;">
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">{{ user.name }}</span>
            <span v-if="currentTeam" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:var(--text-xs);color:var(--text-muted);">{{ currentTeam.name }}</span>
        </div>
        <div style="display:flex;gap:0.25rem;">
            <Link :href="edit()" style="display:flex;align-items:center;padding:0.25rem;border-radius:0.25rem;color:var(--text-muted);text-decoration:none;" title="Settings">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
            </Link>
            <Link :href="logout()" @click="handleLogout" as="button" style="display:flex;align-items:center;padding:0.25rem;border-radius:0.25rem;color:var(--text-muted);text-decoration:none;" title="Log out" data-test="logout-button">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </Link>
        </div>
    </div>
</template>
