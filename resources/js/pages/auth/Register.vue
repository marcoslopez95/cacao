<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Button from '@/components/base/Button.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        style="display:flex;flex-direction:column;gap:1.5rem;"
    >
        <div style="display:grid;gap:1.5rem;">
            <div style="display:grid;gap:0.375rem;">
                <label for="name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Name</label>
                <input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                    class="input"
                />
                <InputError :message="errors.name" />
            </div>

            <div style="display:grid;gap:0.375rem;">
                <label for="email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Email address</label>
                <input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                    class="input"
                />
                <InputError :message="errors.email" />
            </div>

            <div style="display:grid;gap:0.375rem;">
                <label for="password" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Password</label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                />
                <InputError :message="errors.password" />
            </div>

            <div style="display:grid;gap:0.375rem;">
                <label for="password_confirmation" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Confirm password</label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                style="width:100%;margin-top:0.5rem;"
                :tabindex="5"
                :disabled="processing"
                :loading="processing"
                data-test="register-user-button"
            >
                Create account
            </Button>
        </div>

        <div style="text-align:center;font-size:var(--text-sm);color:var(--text-muted);">
            Already have an account?
            <TextLink :href="login()" :tabindex="6">Log in</TextLink>
        </div>
    </Form>
</template>
