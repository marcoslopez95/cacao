<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import AlertError from '@/components/AlertError.vue';
import Button from '@/components/base/Button.vue';
import Card from '@/components/base/Card.vue';
import Icon from '@/components/base/Icon.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { regenerateRecoveryCodes } from '@/routes/two-factor';

const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        (recoveryCodeSectionRef.value as HTMLElement | null)?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <Card style="width:100%;">
        <template #header>
            <div>
                <div style="display:flex;align-items:center;gap:0.75rem;font-weight:600;margin-bottom:0.25rem;">
                    <Icon name="lock" :size="16" />
                    2FA recovery codes
                </div>
                <p style="font-size:var(--text-sm);color:var(--text-muted);">
                    Recovery codes let you regain access if you lose your 2FA
                    device. Store them in a secure password manager.
                </p>
            </div>
        </template>

        <div
            style="display:flex;flex-direction:column;gap:0.75rem;user-select:none;"
        >
            <div style="display:flex;flex-direction:column;gap:0.75rem;align-items:flex-start;">
                <Button @click="toggleRecoveryCodesVisibility" style="width:fit-content;">
                    <template #icon>
                        <Icon :name="isRecoveryCodesVisible ? 'eyeOff' : 'eye'" :size="16" />
                    </template>
                    {{ isRecoveryCodesVisible ? 'Hide' : 'View' }} recovery codes
                </Button>

                <Form
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    v-bind="regenerateRecoveryCodes.form()"
                    method="post"
                    :options="{ preserveScroll: true }"
                    @success="fetchRecoveryCodes"
                    #default="{ processing }"
                >
                    <Button
                        variant="secondary"
                        type="submit"
                        :disabled="processing"
                    >
                        <template #icon>
                            <Icon name="refreshCw" :size="16" />
                        </template>
                        Regenerate codes
                    </Button>
                </Form>
            </div>
            <div
                :style="[
                    'overflow:hidden;transition:all 0.3s;',
                    isRecoveryCodesVisible
                        ? 'height:auto;opacity:1;'
                        : 'height:0;opacity:0;',
                ]"
            >
                <div v-if="errors?.length" style="margin-top:1.5rem;">
                    <AlertError :errors="errors" />
                </div>
                <div v-else style="margin-top:0.75rem;display:flex;flex-direction:column;gap:0.75rem;">
                    <div
                        ref="recoveryCodeSectionRef"
                        style="display:grid;gap:0.25rem;border-radius:0.5rem;background:var(--bg-subtle);padding:1rem;font-family:monospace;font-size:var(--text-sm);"
                    >
                        <div v-if="!recoveryCodesList.length" style="display:flex;flex-direction:column;gap:0.5rem;">
                            <div
                                v-for="n in 8"
                                :key="n"
                                style="height:1rem;border-radius:0.25rem;background:var(--text-muted);opacity:0.2;animation:pulse 1.5s ease-in-out infinite;"
                            ></div>
                        </div>
                        <div
                            v-else
                            v-for="(code, index) in recoveryCodesList"
                            :key="index"
                        >
                            {{ code }}
                        </div>
                    </div>
                    <p style="font-size:var(--text-xs);color:var(--text-muted);user-select:none;">
                        Each recovery code can be used once to access your
                        account and will be removed after use. If you need more,
                        click <span style="font-weight:700;">Regenerate codes</span> above.
                    </p>
                </div>
            </div>
        </div>
    </Card>
</template>
