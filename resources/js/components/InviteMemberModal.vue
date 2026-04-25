<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/UI/AppButton.vue';
import Modal from '@/components/feedback/Modal.vue';
import { store as storeInvitation } from '@/routes/teams/invitations';
import type { RoleOption, Team } from '@/types';

type Props = {
    team: Team;
    availableRoles: RoleOption[];
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const inviteRole = ref('member');
const formKey = ref(0);

function handleOpenChange(value: boolean) {
    emit('update:open', value);

    if (!value) {
        inviteRole.value = 'member';
        formKey.value++;
    }
}
</script>

<template>
    <Modal
        :open="props.open"
        @update:open="handleOpenChange"
        title="Invite a team member"
        size="md"
    >
        <Form
            :key="formKey"
            v-bind="storeInvitation.form(props.team.slug)"
            v-slot="{ errors, processing }"
            @success="emit('update:open', false)"
        >
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:1rem;">
                Send an invitation to join this team.
            </p>

            <div style="display:grid;gap:1rem;margin-bottom:1.5rem;">
                <div style="display:grid;gap:0.5rem;">
                    <label for="invite-email" style="font-size:var(--text-sm);font-weight:500;">Email address</label>
                    <input
                        id="invite-email"
                        name="email"
                        data-test="invite-email"
                        type="email"
                        placeholder="colleague@example.com"
                        required
                        class="input"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:0.5rem;">
                    <label for="invite-role" style="font-size:var(--text-sm);font-weight:500;">Role</label>
                    <select
                        id="invite-role"
                        name="role"
                        data-test="invite-role"
                        v-model="inviteRole"
                        class="input"
                    >
                        <option
                            v-for="role in props.availableRoles"
                            :key="role.value"
                            :value="role.value"
                        >
                            {{ role.label }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
                </div>
            </div>

            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <Button variant="secondary" type="button" @click="emit('update:open', false)">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    data-test="invite-submit"
                    :disabled="processing"
                    :loading="processing"
                >
                    Send invitation
                </Button>
            </div>
        </Form>
    </Modal>
</template>
