<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/UI/AppButton.vue';
import Modal from '@/components/feedback/Modal.vue';
import { destroy } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    team: Team;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const confirmationName = ref('');
const formKey = ref(0);

const canDeleteTeam = computed(() => {
    return confirmationName.value === props.team.name;
});

const handleOpenChange = (nextOpen: boolean) => {
    emit('update:open', nextOpen);

    if (!nextOpen) {
        confirmationName.value = '';
        formKey.value++;
    }
};
</script>

<template>
    <Modal
        :open="props.open"
        @update:open="handleOpenChange"
        title="Are you sure?"
        size="md"
    >
        <Form
            :key="formKey"
            v-bind="destroy.form(props.team.slug)"
            v-slot="{ errors, processing }"
            @success="handleOpenChange(false)"
        >
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:1rem;">
                This action cannot be undone. This will permanently
                delete the team <strong>"{{ props.team.name }}"</strong>.
            </p>

            <div style="display:grid;gap:0.5rem;margin-bottom:1.5rem;">
                <label for="confirmation-name" style="font-size:var(--text-sm);font-weight:500;">
                    Type <strong>"{{ props.team.name }}"</strong> to confirm
                </label>
                <input
                    id="confirmation-name"
                    name="name"
                    data-test="delete-team-name"
                    v-model="confirmationName"
                    placeholder="Enter team name"
                    autocomplete="off"
                    class="input"
                />
                <InputError :message="errors.name" />
            </div>

            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <Button variant="secondary" type="button" @click="handleOpenChange(false)">
                    Cancel
                </Button>
                <Button
                    data-test="delete-team-confirm"
                    variant="danger"
                    type="submit"
                    :disabled="!canDeleteTeam || processing"
                    :loading="processing"
                >
                    Delete team
                </Button>
            </div>
        </Form>
    </Modal>
</template>
