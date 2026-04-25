<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import Button from '@/components/UI/AppButton.vue';
import InputError from '@/components/InputError.vue';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery code',
            description:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
            buttonText: 'login using an authentication code',
        };
    }

    return {
        title: 'Authentication code',
        description:
            'Enter the authentication code provided by your authenticator application.',
        buttonText: 'login using a recovery code',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};

const code = ref<string>('');
</script>

<template>
    <Head title="Two-factor authentication" />

    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <template v-if="!showRecoveryInput">
            <Form
                v-bind="store.form()"
                style="display:flex;flex-direction:column;gap:1rem;"
                reset-on-error
                @error="code = ''"
                #default="{ errors, processing, clearErrors }"
            >
                <input type="hidden" name="code" :value="code" />
                <div
                    style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem;text-align:center;"
                >
                    <div style="display:flex;width:100%;align-items:center;justify-content:center;">
                        <input
                            id="otp"
                            v-model="code"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="6"
                            :disabled="processing"
                            autofocus
                            class="input"
                            style="text-align:center;letter-spacing:0.5em;font-size:1.5rem;width:14rem;"
                            placeholder="······"
                        />
                    </div>
                    <InputError :message="errors.code" />
                </div>
                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    style="width:100%;"
                    :disabled="processing"
                    :loading="processing"
                >
                    Continue
                </Button>
                <div style="text-align:center;font-size:var(--text-sm);color:var(--text-muted);">
                    <span>or you can </span>
                    <button
                        type="button"
                        style="color:var(--text-primary);text-decoration:underline;text-underline-offset:4px;background:transparent;border:none;cursor:pointer;"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>

        <template v-else>
            <Form
                v-bind="store.form()"
                style="display:flex;flex-direction:column;gap:1rem;"
                reset-on-error
                #default="{ errors, processing, clearErrors }"
            >
                <input
                    name="recovery_code"
                    type="text"
                    placeholder="Enter recovery code"
                    :autofocus="showRecoveryInput"
                    required
                    class="input"
                />
                <InputError :message="errors.recovery_code" />
                <Button
                    type="submit"
                    variant="primary"
                    size="lg"
                    style="width:100%;"
                    :disabled="processing"
                    :loading="processing"
                >
                    Continue
                </Button>

                <div style="text-align:center;font-size:var(--text-sm);color:var(--text-muted);">
                    <span>or you can </span>
                    <button
                        type="button"
                        style="color:var(--text-primary);text-decoration:underline;text-underline-offset:4px;background:transparent;border:none;cursor:pointer;"
                        @click="() => toggleRecoveryMode(clearErrors)"
                    >
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>
    </div>
</template>
