<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/components/base/Button.vue';
import Modal from '@/components/feedback/Modal.vue';
import { destroy as destroyInvitation } from '@/routes/teams/invitations';
import type { Team, TeamInvitation } from '@/types';

type Props = {
    team: Team;
    invitation: TeamInvitation | null;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const processing = ref(false);

const cancelInvitation = () => {
    if (!props.invitation) {
        return;
    }

    router.visit(destroyInvitation([props.team.slug, props.invitation.code]), {
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
        title="Cancel invitation"
        size="sm"
    >
        <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:1.5rem;">
            Are you sure you want to cancel the invitation for
            <strong>{{ props.invitation?.email }}</strong>?
        </p>

        <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
            <Button variant="secondary" @click="emit('update:open', false)">
                Keep invitation
            </Button>
            <Button
                data-test="cancel-invitation-confirm"
                variant="danger"
                :disabled="processing"
                :loading="processing"
                @click="cancelInvitation"
            >
                Cancel invitation
            </Button>
        </div>
    </Modal>
</template>
