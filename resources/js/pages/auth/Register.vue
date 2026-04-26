<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import PasswordInput from '@/components/PasswordInput.vue'
import TextLink from '@/components/TextLink.vue'
import { login } from '@/routes'
import { store } from '@/routes/register'

setLayoutProps({
    title: 'Crear cuenta',
    description: 'Completa tus datos para activar tu acceso a CACAO.',
    panelQuote: 'Tu acceso a CACAO comienza con',
    panelHighlight: 'una invitación institucional.',
    panelRole: 'Registro por invitación',
    panelContext: 'Token válido · 48h',
})

defineProps<{
    inviteEmail?: string
    inviteExpiresIn?: string
}>()

function scorePassword(pw: string): number {
    if (!pw) { return 0; }
    let s = 0
    if (pw.length >= 8) { s++; }
    if (pw.length >= 12) { s++; }
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) { s++; }
    if (/\d/.test(pw)) { s++; }
    if (/[^A-Za-z0-9]/.test(pw)) { s++; }
    return Math.min(s, 4)
}
</script>

<template>
    <Head title="Crear cuenta" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing, data }"
        class="flex flex-col gap-5"
    >
        <!-- Invite banner -->
        <div
            v-if="inviteEmail"
            class="flex items-center gap-3.5 rounded-md border border-terracota bg-terra-light dark:bg-[#3D1E0E] dark:border-terra-hover px-4 py-3"
        >
            <div class="shrink-0 w-9 h-9 rounded-full bg-terracota text-white grid place-items-center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>
            <div class="text-[12px] leading-snug">
                <div class="text-[13px] font-semibold text-terra-text dark:text-terra-hover">Invitación válida</div>
                <div class="text-gris dark:text-gris-light">
                    Invitado como <strong class="text-tinta dark:text-papel">{{ inviteEmail }}</strong>
                    <template v-if="inviteExpiresIn"> · token expira en {{ inviteExpiresIn }}</template>
                </div>
            </div>
        </div>

        <!-- Name -->
        <div class="grid gap-1.5">
            <label for="name" class="text-[13px] font-medium text-tinta dark:text-papel">Nombre completo</label>
            <input
                id="name"
                name="name"
                type="text"
                required
                autofocus
                :tabindex="1"
                autocomplete="name"
                placeholder="María González"
                class="h-11 px-3.5 text-[14px] rounded-md border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft text-tinta dark:text-papel placeholder:text-gris focus:border-terracota focus:ring-2 focus:ring-terracota/20 focus:outline-none transition-colors"
            />
            <InputError :message="errors.name" />
        </div>

        <!-- Email -->
        <div class="grid gap-1.5">
            <label for="email" class="text-[13px] font-medium text-tinta dark:text-papel">Correo institucional</label>
            <input
                id="email"
                name="email"
                type="email"
                required
                :tabindex="2"
                autocomplete="email"
                placeholder="usuario@institucion.edu"
                :value="inviteEmail ?? ''"
                :readonly="!!inviteEmail"
                class="h-11 px-3.5 text-[14px] rounded-md border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft text-tinta dark:text-papel placeholder:text-gris focus:border-terracota focus:ring-2 focus:ring-terracota/20 focus:outline-none transition-colors read-only:opacity-70 read-only:cursor-not-allowed"
            />
            <InputError :message="errors.email" />
        </div>

        <!-- Password + confirm -->
        <div class="grid sm:grid-cols-2 gap-3.5">
            <div class="grid gap-1.5">
                <label for="password" class="text-[13px] font-medium text-tinta dark:text-papel">Contraseña</label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    placeholder="Al menos 8 caracteres"
                />
                <InputError :message="errors.password" />
                <!-- Strength meter -->
                <div v-if="data?.password" class="mt-1 flex flex-col gap-1.5">
                    <div class="flex gap-1">
                        <div
                            v-for="i in 4"
                            :key="i"
                            class="flex-1 h-[3px] rounded-sm transition-colors"
                            :class="[
                                i - 1 < scorePassword(data.password)
                                    ? scorePassword(data.password) <= 1 ? 'bg-red-500'
                                    : scorePassword(data.password) <= 2 ? 'bg-amber-500'
                                    : 'bg-green-500'
                                    : 'bg-gris-borde dark:bg-pizarra'
                            ]"
                        />
                    </div>
                    <div class="text-[11px] font-mono text-gris">
                        Seguridad: <strong class="text-tinta dark:text-papel">{{ ['', 'muy débil', 'débil', 'aceptable', 'fuerte'][scorePassword(data.password)] }}</strong>
                    </div>
                </div>
            </div>
            <div class="grid gap-1.5">
                <label for="password_confirmation" class="text-[13px] font-medium text-tinta dark:text-papel">Confirmar contraseña</label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    placeholder="Repetir contraseña"
                />
                <InputError :message="errors.password_confirmation" />
            </div>
        </div>

        <Button
            type="submit"
            variant="primary"
            size="lg"
            :tabindex="5"
            :disabled="processing"
            :loading="processing"
            class="w-full justify-center mt-1"
        >
            {{ processing ? 'Creando cuenta…' : 'Crear cuenta' }}
        </Button>

        <div class="text-center text-[13px] text-gris dark:text-gris-light pt-2">
            ¿Ya tienes cuenta?
            <TextLink :href="login()" :tabindex="6" class="!text-terracota hover:!text-terra-hover !no-underline font-medium">
                Iniciar sesión
            </TextLink>
        </div>
    </Form>
</template>
