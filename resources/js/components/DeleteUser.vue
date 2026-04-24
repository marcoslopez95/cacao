<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import Button from '@/components/base/Button.vue';
import Modal from '@/components/feedback/Modal.vue';

const passwordInput = useTemplateRef<InstanceType<typeof PasswordInput>>('passwordInput');
const showModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            title="Delete account"
            description="Delete your account and all of its resources"
        />
        <div
            style="display:flex;flex-direction:column;gap:1rem;border-radius:0.5rem;border:1px solid #fecaca;background:#fef2f2;padding:1rem;"
        >
            <div style="position:relative;color:#dc2626;">
                <p style="font-weight:500;margin:0;">Warning</p>
                <p style="font-size:var(--text-sm);margin:0;">
                    Please proceed with caution, this cannot be undone.
                </p>
            </div>
            <div>
                <Button variant="danger" data-test="delete-user-button" @click="showModal = true">
                    Delete account
                </Button>
            </div>
        </div>
    </div>

    <Modal :open="showModal" @update:open="showModal = $event" title="Are you sure you want to delete your account?" size="md">
        <Form
            v-bind="ProfileController.destroy.form()"
            reset-on-success
            @error="() => (passwordInput as any)?.focus()"
            :options="{ preserveScroll: true }"
            v-slot="{ errors, processing, reset, clearErrors }"
        >
            <p style="font-size:var(--text-sm);color:var(--text-muted);margin-bottom:1rem;">
                Once your account is deleted, all of its resources and data will also be permanently
                deleted. Please enter your password to confirm you would like to permanently delete
                your account.
            </p>

            <div style="display:grid;gap:0.5rem;margin-bottom:1.5rem;">
                <label for="delete-password" class="sr-only">Password</label>
                <PasswordInput
                    id="delete-password"
                    name="password"
                    ref="passwordInput"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                <Button
                    variant="secondary"
                    type="button"
                    @click="() => { clearErrors(); reset(); showModal = false; }"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="danger"
                    :disabled="processing"
                    :loading="processing"
                    data-test="confirm-delete-user-button"
                >
                    Delete account
                </Button>
            </div>
        </Form>
    </Modal>
</template>
