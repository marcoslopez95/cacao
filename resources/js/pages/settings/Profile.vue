<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import SettingsTabs from '@/components/settings/SettingsTabs.vue';
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
        breadcrumbs: [{ title: 'Configuración', href: edit() }],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const isDirty = ref(false);
const isSaved = ref(false);

function onInput(): void {
    isDirty.value = true;
    isSaved.value = false;
}

function onSuccess(): void {
    isDirty.value = false;
    isSaved.value = true;
    setTimeout(() => { isSaved.value = false; }, 2400);
}
</script>

<template>
    <Head title="Perfil · Configuración" />
    <h1 class="sr-only">Configuración — Perfil</h1>

    <SettingsTabs />

    <!-- Información del perfil -->
    <Form
        v-bind="ProfileController.update.form()"
        class="sp-section"
        :options="{ preserveScroll: true }"
        @success="onSuccess"
        v-slot="{ errors, processing }"
    >
        <div class="sp-section-head">
            <h2>Información del perfil</h2>
            <p>Actualiza tu nombre y dirección de correo.</p>
        </div>

        <div class="sp-section-body">
            <div class="sp-fields">
                <div class="sp-field">
                    <label class="sp-label" for="name">
                        Nombre <span class="req">*</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        class="sp-input"
                        :class="{ error: errors.name }"
                        :default-value="user.name"
                        required
                        autocomplete="name"
                        @input="onInput"
                    />
                    <div v-if="errors.name" class="sp-error">{{ errors.name }}</div>
                </div>

                <div class="sp-field">
                    <label class="sp-label" for="email">
                        Correo electrónico <span class="req">*</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="sp-input"
                        :class="{ error: errors.email }"
                        :default-value="user.email"
                        required
                        autocomplete="username"
                        @input="onInput"
                    />
                    <div v-if="errors.email" class="sp-error">{{ errors.email }}</div>

                    <div v-if="user.email_verified_at" class="sp-verif ok">
                        <span class="sp-verif-ico">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        <div class="sp-verif-text">
                            <strong>Correo verificado.</strong> Tu dirección de correo está confirmada.
                        </div>
                    </div>
                    <div v-else-if="mustVerifyEmail" class="sp-verif warn">
                        <span class="sp-verif-ico">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 1 21h22z"/><path d="M12 10v5M12 18h.01"/></svg>
                        </span>
                        <div class="sp-verif-text">
                            <template v-if="status === 'verification-link-sent'">
                                <strong>Enlace enviado.</strong> Revisa tu bandeja de entrada.
                            </template>
                            <template v-else>
                                <strong>Correo no verificado.</strong>
                                <Link :href="send()" as="button" class="sp-verif-resend">
                                    Reenviar enlace de verificación
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sp-section-foot">
            <span
                class="sp-status"
                :class="{ dirty: isDirty && !isSaved, saved: isSaved }"
            >
                <template v-if="isSaved">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                    Cambios guardados
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
                data-test="update-profile-button"
            >
                Guardar cambios
            </Button>
        </div>
    </Form>

</template>
