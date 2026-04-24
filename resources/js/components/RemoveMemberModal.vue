<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/components/base/Button.vue';
import Modal from '@/components/feedback/Modal.vue';
import { destroy as destroyMember } from '@/routes/teams/members';
import type { Team, TeamMember } from '@/types';

type Props = {
    team: Team;
    member: TeamMember | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const processing = ref(false);

const removeMember = () => {
    if (!props.member) {
        return;
    }

    router.visit(destroyMember([props.team.slug, props.member.id]), {
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
        onSuccess: () => emit('update:open', false),
    });
};
</script>

<template>
    <Modal
        :open="props.open"
        @update:open="emit('update:open', $event)"
        title="Remove team member"
        size="sm"
    >
        <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:1.5rem;">
            Are you sure you want to remove
            <strong>{{ props.member?.name }}</strong> from this team?
        </p>

        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
            <Button variant="secondary" @click="emit('update:open', false)">
                Cancel
            </Button>
            <Button
                data-test="remove-member-confirm"
                variant="danger"
                :disabled="processing"
                :loading="processing"
                @click="removeMember"
            >
                Remove member
            </Button>
        </div>
    </Modal>
</template>
