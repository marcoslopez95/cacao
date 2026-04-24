<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

defineOptions({
    layout: {
        title: 'Iniciar sesión',
        description: 'Ingresa tu correo y contraseña para acceder',
    },
})

defineProps<{
    status?: string
    canResetPassword: boolean
    canRegister: boolean
}>()
</script>

<template>
    <Head title="Iniciar sesión" />

    <div v-if="status" style="margin-bottom:16px;text-align:center;font-size:var(--text-sm);font-weight:500;color:var(--success);">
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        style="display:flex;flex-direction:column;gap:20px;"
    >
        <div style="display:grid;gap:16px;">
            <div style="display:grid;gap:6px;">
                <label for="email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Correo electrónico
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="input"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div style="display:grid;gap:6px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <label for="password" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Contraseña
                    </label>
                    <TextLink v-if="canResetPassword" :href="request()" style="font-size:var(--text-xs);" :tabindex="5">
                        ¿Olvidaste tu contraseña?
                    </TextLink>
                </div>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="input"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Contraseña"
                />
                <InputError :message="errors.password" />
            </div>

            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:var(--text-sm);">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    :tabindex="3"
                    style="width:14px;height:14px;accent-color:var(--accent);"
                />
                Recuérdame
            </label>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                :loading="processing"
                :disabled="processing"
                :tabindex="4"
                style="width:100%;margin-top:4px;"
            >
                Iniciar sesión
            </Button>
        </div>

        <div v-if="canRegister" style="text-align:center;font-size:var(--text-sm);color:var(--text-muted);">
            ¿No tienes cuenta?
            <TextLink :href="register()" :tabindex="5">Regístrate</TextLink>
        </div>
    </Form>
</template>
