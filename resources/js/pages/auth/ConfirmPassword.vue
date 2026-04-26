<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import Button from '@/components/UI/AppButton.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Confirma tu contraseña',
        description:
            'Estás en un área segura. Confirma tu contraseña para continuar.',
    },
});
</script>

<template>
    <Head title="Confirmar contraseña" />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <div style="display:grid;gap:0.5rem;">
                <label for="password" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Contraseña</label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="Tu contraseña"
                />
                <InputError :message="errors.password" />
            </div>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                style="width:100%;"
                :disabled="processing"
                :loading="processing"
                data-test="confirm-password-button"
            >
                Confirmar contraseña
            </Button>
        </div>
    </Form>
</template>
