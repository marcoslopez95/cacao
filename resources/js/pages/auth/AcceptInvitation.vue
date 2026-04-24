<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import PasswordInput from '@/components/PasswordInput.vue'

const props = defineProps<{
    expired: boolean
    inviteEmail: string
    inviteRole?: string
    inviteExpiresIn?: string
    token: string
}>()

setLayoutProps({
    title:          props.expired ? 'Invitación expirada' : 'Aceptar invitación',
    description:    props.expired
        ? 'Este enlace ya fue utilizado o ha expirado.'
        : 'Completá tus datos para activar tu acceso a CACAO.',
    panelQuote:     'Tu acceso a CACAO comienza con',
    panelHighlight: 'una invitación institucional.',
    panelRole:      'Invitación institucional',
    panelContext:   'Acceso seguro',
})

const acceptUrl = `/invitations/${props.token}`
</script>

<template>
    <Head :title="expired ? 'Invitación expirada' : 'Aceptar invitación'" />

    <div
        v-if="expired"
        class="flex flex-col items-center gap-4 py-6 text-center"
    >
        <div class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 grid place-items-center">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
            </svg>
        </div>
        <h2 class="text-[18px] font-semibold text-tinta dark:text-papel m-0">Invitación no válida</h2>
        <p class="text-gris dark:text-gris-light text-[14px] m-0">
            Este enlace ya fue utilizado o ha expirado. Pedile a un administrador que te envíe una nueva invitación.
        </p>
    </div>

    <Form
        v-else
        :action="acceptUrl"
        method="post"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <!-- Invite banner -->
        <div class="flex items-center gap-3.5 rounded-md border border-terracota bg-terra-light dark:bg-[#3D1E0E] dark:border-terra-hover px-4 py-3">
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
                    <template v-if="inviteExpiresIn"> · expira {{ inviteExpiresIn }}</template>
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
                autocomplete="name"
                placeholder="María González"
                class="h-11 px-3.5 text-[14px] rounded-md border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft text-tinta dark:text-papel placeholder:text-gris focus:border-terracota focus:ring-2 focus:ring-terracota/20 focus:outline-none transition-colors"
            />
            <InputError :message="errors.name" />
        </div>

        <!-- Password -->
        <div class="grid gap-1.5">
            <label for="password" class="text-[13px] font-medium text-tinta dark:text-papel">Contraseña</label>
            <PasswordInput
                id="password"
                name="password"
                required
                autocomplete="new-password"
                placeholder="Al menos 8 caracteres"
            />
            <InputError :message="errors.password" />
        </div>

        <!-- Confirm password -->
        <div class="grid gap-1.5">
            <label for="password_confirmation" class="text-[13px] font-medium text-tinta dark:text-papel">Confirmar contraseña</label>
            <PasswordInput
                id="password_confirmation"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="Repetir contraseña"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <Button
            type="submit"
            variant="primary"
            size="lg"
            :disabled="processing"
            :loading="processing"
            class="w-full justify-center mt-1"
        >
            {{ processing ? 'Activando cuenta…' : 'Activar cuenta' }}
        </Button>
    </Form>
</template>
