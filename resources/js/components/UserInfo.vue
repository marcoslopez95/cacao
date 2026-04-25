<script setup lang="ts">
import CacaoAvatar from '@/components/UI/AppAvatar.vue';
import { useInitials } from '@/composables/useInitials';
import type { Team, User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
    team?: Team | null;
};

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    team: null,
});

const { getInitials } = useInitials();
</script>

<template>
    <CacaoAvatar
        :initials="getInitials(user.name)"
        :src="user.avatar ?? undefined"
        size="sm"
    />

    <div style="display:grid;flex:1;text-align:left;font-size:var(--text-sm);line-height:1.25;">
        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500;">{{ user.name }}</span>
        <span v-if="team" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:var(--text-xs);color:var(--text-muted);">{{
            team.name
        }}</span>
        <span
            v-else-if="showEmail"
            style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:var(--text-xs);color:var(--text-muted);"
            >{{ user.email }}</span
        >
    </div>
</template>
