<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Button from '@/components/base/Button.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Reset password',
        description: 'Please enter your new password below',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>
    <Head title="Reset password" />

    <Form
        v-bind="update.form()"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
    >
        <div style="display:grid;gap:1.5rem;">
            <div style="display:grid;gap:0.5rem;">
                <label for="email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    v-model="inputEmail"
                    class="input"
                    readonly
                />
                <InputError :message="errors.email" />
            </div>

            <div style="display:grid;gap:0.5rem;">
                <label for="password" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Password</label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    autofocus
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div style="display:grid;gap:0.5rem;">
                <label for="password_confirmation" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Confirm password</label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                style="width:100%;margin-top:1rem;"
                :disabled="processing"
                :loading="processing"
                data-test="reset-password-button"
            >
                Reset password
            </Button>
        </div>
    </Form>
</template>
