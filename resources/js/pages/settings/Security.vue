<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SettingsTabs from '@/components/settings/SettingsTabs.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import Button from '@/components/UI/AppButton.vue';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Configuración', href: edit() }],
    },
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref(false);
const isDirty = ref(false);
const isSaved = ref(false);
const newPasswordValue = ref('');

onUnmounted(() => clearTwoFactorAuthData());

function onAnyInput(): void {
    isDirty.value = true;
    isSaved.value = false;
}

function onNewPasswordInput(e: Event): void {
    onAnyInput();
    newPasswordValue.value = (e.target as HTMLInputElement).value;
}

function onSuccess(): void {
    isDirty.value = false;
    isSaved.value = true;
    newPasswordValue.value = '';
    setTimeout(() => { isSaved.value = false; }, 2400);
}

const pwStrengthScore = computed((): number => {
    const pw = newPasswordValue.value;
    if (!pw) return 0;
    let s = 0;
    if (pw.length >= 8) s++;
    if (pw.length >= 12) s++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) s++;
    if (/\d/.test(pw)) s++;
    if (/[^A-Za-z0-9]/.test(pw)) s++;
    return s;
});

const strengthData = [
    { color: 'var(--danger)', label: 'Muy débil' },
    { color: 'var(--danger)', label: 'Débil' },
    { color: 'var(--warning)', label: 'Aceptable' },
    { color: 'var(--success)', label: 'Buena' },
    { color: 'var(--success)', label: 'Fuerte' },
];

const pwStrengthColor = computed(() =>
    pwStrengthScore.value ? strengthData[pwStrengthScore.value - 1].color : 'var(--border)'
);
const pwStrengthLabel = computed(() =>
    pwStrengthScore.value ? strengthData[pwStrengthScore.value - 1].label : ''
);
</script>

<template>
    <Head title="Seguridad · Configuración" />
    <h1 class="sr-only">Configuración — Seguridad</h1>

    <SettingsTabs />

    <!-- Cambiar contraseña -->
    <Form
        v-bind="SecurityController.update.form()"
        class="sp-section"
        :options="{ preserveScroll: true }"
        reset-on-success
        :reset-on-error="['password', 'password_confirmation', 'current_password']"
        @success="onSuccess"
        v-slot="{ errors, processing }"
    >
        <div class="sp-section-head">
            <h2>Contraseña</h2>
            <p>Usa una contraseña larga y única para mantener tu cuenta segura.</p>
        </div>

        <div class="sp-section-body">
            <div class="sp-fields">
                <div class="sp-field">
                    <label class="sp-label" for="current_password">
                        Contraseña actual <span class="req">*</span>
                    </label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        autocomplete="current-password"
                        placeholder="Tu contraseña actual"
                        @input="onAnyInput"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="sp-field-row">
                    <div class="sp-field">
                        <label class="sp-label" for="password">
                            Nueva contraseña <span class="req">*</span>
                        </label>
                        <PasswordInput
                            id="password"
                            name="password"
                            autocomplete="new-password"
                            placeholder="Nueva contraseña"
                            @input="onNewPasswordInput"
                        />
                        <div v-if="newPasswordValue" class="sp-pw-strength">
                            <div class="sp-pw-strength-bars">
                                <div
                                    v-for="i in 5"
                                    :key="i"
                                    class="sp-pw-strength-bar"
                                    :style="{ background: i <= pwStrengthScore ? pwStrengthColor : undefined }"
                                />
                            </div>
                            <span class="sp-pw-strength-label" :style="{ color: pwStrengthColor }">
                                {{ pwStrengthLabel }}
                            </span>
                        </div>
                        <InputError :message="errors.password" />
                    </div>

                    <div class="sp-field">
                        <label class="sp-label" for="password_confirmation">
                            Confirmar contraseña <span class="req">*</span>
                        </label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder="Repite la contraseña"
                            @input="onAnyInput"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>
                </div>

                <div class="sp-help">
                    Mínimo 8 caracteres. Recomendamos combinar mayúsculas, minúsculas, números y símbolos.
                </div>
            </div>
        </div>

        <div class="sp-section-foot">
            <span
                class="sp-status"
                :class="{ dirty: isDirty && !isSaved, saved: isSaved }"
            >
                <template v-if="isSaved">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    Contraseña actualizada
                </template>
                <template v-else-if="isDirty">
                    <span class="dot"></span>
                    Hay cambios sin guardar
                </template>
                <template v-else>Sin cambios</template>
            </span>
            <Button
                type="submit"
                :disabled="!isDirty || processing"
                :loading="processing"
                data-test="update-password-button"
            >
                Actualizar contraseña
            </Button>
        </div>
    </Form>

    <!-- 2FA -->
    <div v-if="canManageTwoFactor" class="sp-section">
        <div class="sp-section-head">
            <h2>Autenticación en dos pasos</h2>
            <p>Agrega una capa extra de seguridad con un código de tu app autenticadora.</p>
        </div>
        <div class="sp-section-body">
            <div class="sp-2fa-status">
                <div class="sp-2fa-icon" :class="twoFactorEnabled ? 'on' : 'off'">
                    <svg v-if="twoFactorEnabled" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    <svg v-else width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </div>

                <div class="sp-2fa-info">
                    <div class="title">
                        Autenticación 2FA
                        <span class="pill" :class="twoFactorEnabled ? 'on' : 'off'">
                            {{ twoFactorEnabled ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>
                    <div class="meta">
                        {{ twoFactorEnabled
                            ? 'Protegida con app autenticadora.'
                            : 'Tu cuenta solo está protegida por contraseña.' }}
                    </div>
                </div>

                <Form v-if="twoFactorEnabled" v-bind="disable.form()" #default="{ processing }">
                    <Button variant="danger" size="sm" type="submit" :disabled="processing" :loading="processing">
                        Desactivar
                    </Button>
                </Form>
                <template v-else>
                    <Button v-if="hasSetupData" size="sm" @click="showSetupModal = true">
                        Continuar configuración
                    </Button>
                    <Form v-else v-bind="enable.form()" @success="showSetupModal = true" #default="{ processing }">
                        <Button type="submit" size="sm" :disabled="processing" :loading="processing">
                            Activar 2FA
                        </Button>
                    </Form>
                </template>
            </div>

            <div v-if="twoFactorEnabled">
                <div class="sp-help" style="margin-bottom:12px;">
                    Guarda tus <strong>códigos de recuperación</strong> en un lugar seguro.
                    Los necesitarás si pierdes acceso a tu app autenticadora.
                </div>
                <TwoFactorRecoveryCodes />
            </div>
        </div>
    </div>

    <TwoFactorSetupModal
        v-model:isOpen="showSetupModal"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />
</template>
