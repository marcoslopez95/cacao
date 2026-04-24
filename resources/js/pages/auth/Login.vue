<script setup lang="ts">
import { ref } from 'vue'
import { Form, Head, setLayoutProps } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

setLayoutProps({
    title: 'Iniciar sesión',
    description: 'Accedé con tus credenciales institucionales.',
    panelQuote: 'Cada período, cada sección, cada nota.',
    panelHighlight: 'Sin hojas de cálculo sueltas.',
    panelRole: 'Portal institucional',
    panelContext: 'Acceso seguro',
})

defineProps<{
    status?: string
    canResetPassword: boolean
    canRegister: boolean
}>()

const showPassword = ref(false)
</script>

<template>
    <Head title="Iniciar sesión" />

    <div
        v-if="status"
        class="mb-5 rounded-md border border-green-200 bg-green-50 dark:border-green-900 dark:bg-green-950 px-3 py-2.5 text-[13px] text-green-700 dark:text-green-400"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <!-- Email -->
        <div class="grid gap-1.5">
            <label for="email" class="text-[13px] font-medium text-tinta dark:text-papel">
                Correo institucional
            </label>
            <input
                id="email"
                type="email"
                name="email"
                required
                autofocus
                :tabindex="1"
                autocomplete="email"
                placeholder="usuario@institucion.edu"
                class="h-11 px-3.5 text-[14px] rounded-md border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft text-tinta dark:text-papel placeholder:text-gris focus:border-terracota focus:ring-2 focus:ring-terracota/20 focus:outline-none transition-colors"
            />
            <InputError :message="errors.email" />
        </div>

        <!-- Password -->
        <div class="grid gap-1.5">
            <div class="flex items-center justify-between">
                <label for="password" class="text-[13px] font-medium text-tinta dark:text-papel">
                    Contraseña
                </label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    :tabindex="5"
                    class="!text-[12px] !text-terracota hover:!text-terra-hover !no-underline font-medium"
                >
                    ¿Olvidaste tu contraseña?
                </TextLink>
            </div>
            <div class="relative">
                <input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Al menos 8 caracteres"
                    class="w-full h-11 pl-3.5 pr-11 text-[14px] rounded-md border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft text-tinta dark:text-papel placeholder:text-gris focus:border-terracota focus:ring-2 focus:ring-terracota/20 focus:outline-none transition-colors"
                />
                <button
                    type="button"
                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    @click="showPassword = !showPassword"
                    class="absolute inset-y-0 right-0 flex items-center justify-center w-10 text-gris hover:text-tinta dark:hover:text-papel transition-colors"
                >
                    <svg v-if="showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <InputError :message="errors.password" />
        </div>

        <!-- Remember -->
        <label class="flex items-center gap-2.5 cursor-pointer text-[13px] text-gris dark:text-gris-light">
            <input
                id="remember"
                type="checkbox"
                name="remember"
                :tabindex="3"
                class="w-4 h-4 accent-terracota rounded-sm"
            />
            <span>Mantener sesión iniciada en este dispositivo</span>
        </label>

        <!-- Submit -->
        <Button
            type="submit"
            variant="primary"
            size="lg"
            :loading="processing"
            :disabled="processing"
            :tabindex="4"
            class="w-full justify-center"
        >
            {{ processing ? 'Verificando…' : 'Iniciar sesión' }}
        </Button>

        <!-- Footer -->
        <div v-if="canRegister" class="text-center text-[13px] text-gris dark:text-gris-light pt-2">
            ¿No tenés cuenta?
            <TextLink :href="register()" :tabindex="6" class="!text-terracota hover:!text-terra-hover !no-underline font-medium">
                Solicitá acceso
            </TextLink>
        </div>
    </Form>
</template>
