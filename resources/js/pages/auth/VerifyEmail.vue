<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Button from '@/components/base/Button.vue';
import TextLink from '@/components/TextLink.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verify email',
        description:
            'Please verify your email address by clicking on the link we just emailed to you.',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Email verification" />

    <div
        v-if="status === 'verification-link-sent'"
        style="margin-bottom:1rem;text-align:center;font-size:var(--text-sm);font-weight:500;color:var(--success);"
    >
        A new verification link has been sent to the email address you provided
        during registration.
    </div>

    <Form
        v-bind="send.form()"
        style="display:flex;flex-direction:column;gap:1.5rem;text-align:center;"
        v-slot="{ processing }"
    >
        <Button :disabled="processing" :loading="processing" variant="secondary">
            Resend verification email
        </Button>

        <TextLink :href="logout()" as="button" style="display:block;margin:0 auto;font-size:var(--text-sm);">
            Log out
        </TextLink>
    </Form>
</template>
