<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AlertError from '@/components/AlertError.vue';
import InputError from '@/components/InputError.vue';
import Button from '@/components/base/Button.vue';
import Icon from '@/components/base/Icon.vue';
import Modal from '@/components/feedback/Modal.vue';
import { useAppearance } from '@/composables/useAppearance';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { confirm } from '@/routes/two-factor';
import type { TwoFactorConfigContent } from '@/types';

type Props = {
    requiresConfirmation: boolean;
    twoFactorEnabled: boolean;
};

const { resolvedAppearance } = useAppearance();

const props = defineProps<Props>();
const isOpen = defineModel<boolean>('isOpen');

const { copy, copied } = useClipboard();
const { qrCodeSvg, manualSetupKey, clearSetupData, fetchSetupData, errors } =
    useTwoFactorAuth();

const showVerificationStep = ref(false);
const code = ref<string>('');

const pinInputContainerRef = useTemplateRef('pinInputContainerRef');

const modalConfig = computed<TwoFactorConfigContent>(() => {
    if (props.twoFactorEnabled) {
        return {
            title: 'Two-factor authentication enabled',
            description:
                'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.',
            buttonText: 'Close',
        };
    }

    if (showVerificationStep.value) {
        return {
            title: 'Verify authentication code',
            description: 'Enter the 6-digit code from your authenticator app',
            buttonText: 'Continue',
        };
    }

    return {
        title: 'Enable two-factor authentication',
        description:
            'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app',
        buttonText: 'Continue',
    };
});

const handleModalNextStep = () => {
    if (props.requiresConfirmation) {
        showVerificationStep.value = true;

        nextTick(() => {
            (pinInputContainerRef.value as HTMLElement | null)?.querySelector('input')?.focus();
        });

        return;
    }

    clearSetupData();
    isOpen.value = false;
};

const resetModalState = () => {
    if (props.twoFactorEnabled) {
        clearSetupData();
    }

    showVerificationStep.value = false;
    code.value = '';
};

watch(
    () => isOpen.value,
    async (isOpen) => {
        if (!isOpen) {
            resetModalState();

            return;
        }

        if (!qrCodeSvg.value) {
            await fetchSetupData();
        }
    },
);
</script>

<template>
    <Modal
        :open="isOpen"
        @update:open="isOpen = $event"
        :title="modalConfig.title"
        size="md"
    >
        <p style="font-size:var(--text-sm);color:var(--text-muted);text-align:center;margin-bottom:1rem;">
            {{ modalConfig.description }}
        </p>

        <div style="display:flex;flex-direction:column;align-items:center;gap:1.25rem;width:100%;">
            <template v-if="!showVerificationStep">
                <AlertError v-if="errors?.length" :errors="errors" />
                <template v-else>
                    <div style="position:relative;display:flex;max-width:28rem;align-items:center;overflow:hidden;">
                        <div style="position:relative;margin:0 auto;aspect-ratio:1;width:16rem;overflow:hidden;border-radius:0.5rem;border:1px solid var(--border);">
                            <div
                                v-if="!qrCodeSvg"
                                style="position:absolute;inset:0;z-index:10;display:flex;aspect-ratio:1;width:100%;align-items:center;justify-content:center;background:var(--bg-base);animation:pulse 1.5s ease-in-out infinite;"
                            >
                                <Icon name="scanLine" :size="24" />
                            </div>
                            <div
                                v-else
                                style="position:relative;z-index:10;overflow:hidden;border:1px solid var(--border);padding:1.25rem;"
                            >
                                <div
                                    v-html="qrCodeSvg"
                                    style="display:flex;aspect-ratio:1;width:100%;align-items:center;justify-content:center;"
                                    :style="{
                                        filter:
                                            resolvedAppearance === 'dark'
                                                ? 'invert(1) brightness(1.5)'
                                                : undefined,
                                    }"
                                />
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;width:100%;align-items:center;gap:1.25rem;">
                        <Button style="width:100%;" @click="handleModalNextStep">
                            {{ modalConfig.buttonText }}
                        </Button>
                    </div>

                    <div style="position:relative;display:flex;width:100%;align-items:center;justify-content:center;">
                        <div style="position:absolute;inset:0;top:50%;height:1px;width:100%;background:var(--border);" />
                        <span style="position:relative;background:var(--bg-card);padding:0.25rem 0.5rem;">or, enter the code manually</span>
                    </div>

                    <div style="display:flex;width:100%;align-items:center;justify-content:center;gap:0.5rem;">
                        <div style="display:flex;width:100%;align-items:stretch;overflow:hidden;border-radius:0.75rem;border:1px solid var(--border);">
                            <div
                                v-if="!manualSetupKey"
                                style="display:flex;height:100%;width:100%;align-items:center;justify-content:center;background:var(--bg-subtle);padding:0.75rem;"
                            >
                                <Icon name="loader" :size="16" />
                            </div>
                            <template v-else>
                                <input
                                    type="text"
                                    readonly
                                    :value="manualSetupKey"
                                    style="height:100%;width:100%;background:var(--bg-base);padding:0.75rem;color:var(--text-primary);"
                                />
                                <button
                                    @click="copy(manualSetupKey || '')"
                                    style="position:relative;display:block;height:auto;border-left:1px solid var(--border);padding:0 0.75rem;background:transparent;cursor:pointer;"
                                    :style="{ background: 'transparent' }"
                                >
                                    <Icon v-if="copied" name="check" :size="16" style="color:#22c55e;" />
                                    <Icon v-else name="copy" :size="16" />
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </template>

            <template v-else>
                <Form
                    v-bind="confirm.form()"
                    error-bag="confirmTwoFactorAuthentication"
                    reset-on-error
                    @finish="code = ''"
                    @success="isOpen = false"
                    v-slot="{ errors, processing }"
                >
                    <input type="hidden" name="code" :value="code" />
                    <div
                        ref="pinInputContainerRef"
                        style="position:relative;width:100%;display:flex;flex-direction:column;gap:0.75rem;"
                    >
                        <div style="display:flex;width:100%;flex-direction:column;align-items:center;justify-content:center;gap:0.75rem;padding:0.5rem 0;">
                            <input
                                id="otp"
                                v-model="code"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]*"
                                maxlength="6"
                                :disabled="processing"
                                class="input"
                                style="text-align:center;letter-spacing:0.5em;font-size:1.5rem;width:14rem;"
                                placeholder="······"
                            />
                            <InputError :message="errors?.code" />
                        </div>

                        <div style="display:flex;width:100%;align-items:center;gap:1.25rem;">
                            <Button
                                type="button"
                                variant="secondary"
                                style="flex:1;"
                                @click="showVerificationStep = false"
                                :disabled="processing"
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                style="flex:1;"
                                :disabled="processing || code.length < 6"
                            >
                                Confirm
                            </Button>
                        </div>
                    </div>
                </Form>
            </template>
        </div>
    </Modal>
</template>
