<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/UI/AppButton.vue';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

type Props = {
    mustVerifyEmail: boolean;
    status?: string;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <Heading
            variant="small"
            title="Profile information"
            description="Update your name and email address"
        />

        <Form
            v-bind="ProfileController.update.form()"
            style="display:flex;flex-direction:column;gap:1.5rem;"
            v-slot="{ errors, processing }"
        >
            <div style="display:grid;gap:0.5rem;">
                <label for="name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Name</label>
                <input
                    id="name"
                    class="input"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div style="display:grid;gap:0.5rem;">
                <label for="email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Email address</label>
                <input
                    id="email"
                    type="email"
                    class="input"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError :message="errors.email" />
            </div>

            <div v-if="mustVerifyEmail && !user.email_verified_at">
                <p style="margin-top:-1rem;font-size:var(--text-sm);color:var(--text-muted);">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        style="color:var(--text-primary);text-decoration:underline;text-underline-offset:4px;background:transparent;border:none;cursor:pointer;"
                    >
                        Click here to resend the verification email.
                    </Link>
                </p>

                <div
                    v-if="status === 'verification-link-sent'"
                    style="margin-top:0.5rem;font-size:var(--text-sm);font-weight:500;color:var(--success);"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:1rem;">
                <Button :disabled="processing" :loading="processing" data-test="update-profile-button">Save</Button>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
