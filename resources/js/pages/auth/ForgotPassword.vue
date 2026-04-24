<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Button from '@/components/base/Button.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />

    <div
        v-if="status"
        style="margin-bottom:1rem;text-align:center;font-size:var(--text-sm);font-weight:500;color:var(--success);"
    >
        {{ status }}
    </div>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div style="display:grid;gap:0.5rem;margin-bottom:1rem;">
                <label for="email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Email address</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    autocomplete="off"
                    autofocus
                    placeholder="email@example.com"
                    class="input"
                />
                <InputError :message="errors.email" />
            </div>

            <div style="display:flex;align-items:center;justify-content:flex-start;">
                <Button
                    style="width:100%;"
                    :disabled="processing"
                    :loading="processing"
                    data-test="email-password-reset-link-button"
                >
                    Email password reset link
                </Button>
            </div>
        </Form>

        <div style="text-align:center;font-size:var(--text-sm);color:var(--text-muted);">
            <span>Or, return to </span>
            <TextLink :href="login()">log in</TextLink>
        </div>
    </div>
</template>
