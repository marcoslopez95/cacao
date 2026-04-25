<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/UI/AppButton.vue';
import Modal from '@/components/feedback/Modal.vue';
import { store } from '@/routes/teams';

const open = ref(false);
const formKey = ref(0);

function handleOpenChange(value: boolean) {
    open.value = value;

    if (!value) {
        formKey.value++;
    }
}
</script>

<template>
    <span @click="open = true" style="display:contents;">
        <slot />
    </span>

    <Modal
        :open="open"
        @update:open="handleOpenChange"
        title="Create a new team"
        size="md"
    >
        <Form
            :key="formKey"
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            @success="open = false"
        >
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:1rem;">
                Create a new team to collaborate with others.
            </p>

            <div style="display:grid;gap:0.5rem;margin-bottom:1.5rem;">
                <label for="create-team-name" style="font-size:var(--text-sm);font-weight:500;">Team name</label>
                <input
                    id="create-team-name"
                    name="name"
                    data-test="create-team-name"
                    placeholder="My team"
                    required
                    class="input"
                />
                <InputError :message="errors.name" />
            </div>

            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <Button variant="secondary" type="button" @click="open = false">
                    Cancel
                </Button>
                <Button
                    type="submit"
                    data-test="create-team-submit"
                    :disabled="processing"
                    :loading="processing"
                >
                    Create team
                </Button>
            </div>
        </Form>
    </Modal>
</template>
