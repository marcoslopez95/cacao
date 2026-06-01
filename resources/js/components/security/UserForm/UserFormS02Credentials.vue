<script setup lang="ts">
import { computed } from 'vue'
import type { UserFormData } from '@/types/userForm'
import AppFormField from '@/components/UI/AppFormField.vue'
import AppPasswordInput from '@/components/UI/AppPasswordInput.vue'
import AppPasswordStrength from '@/components/UI/AppPasswordStrength.vue'
import AppPillRadios from '@/components/UI/AppPillRadios.vue'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

const emailOk = computed(() =>
    !!props.data.email && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(props.data.email),
)

const passwordModeOptions = [
    { key: 'link', label: 'Enviar link de activación' },
    { key: 'manual', label: 'Escribir contraseña manualmente' },
    { key: 'random', label: 'Generar contraseña aleatoria' },
]

const currentPasswordMode = computed(() => props.data.passwordMode ?? 'link')
</script>

<template>
    <div class="uf-grid">
        <AppFormField label="Modo de contraseña" :col="12" required>
            <AppPillRadios
                :model-value="currentPasswordMode"
                :options="passwordModeOptions"
                @update:model-value="setField('passwordMode', $event)"
            />
        </AppFormField>

        <AppFormField
            label="Correo electrónico"
            required
            :col="6"
            :ok="emailOk ? 'Formato válido' : undefined"
        >
            <input
                dusk="email-input"
                class="uf-input"
                type="email"
                :value="data.email"
                placeholder="usuario@correo.com"
                autocomplete="email"
                @input="setField('email', ($event.target as HTMLInputElement).value)"
            />
        </AppFormField>

        <AppFormField v-if="currentPasswordMode === 'manual'" label="Contraseña" required :col="6">
            <AppPasswordInput
                :model-value="data.password ?? ''"
                placeholder="Mínimo 8 caracteres"
                @update:model-value="setField('password', $event)"
            />
            <AppPasswordStrength :password="data.password ?? ''" />
        </AppFormField>

        <p v-else-if="currentPasswordMode === 'random'" class="uf-help col-12">
            El sistema generará una contraseña aleatoria segura. Se mostrará al finalizar la creación del usuario.
        </p>
    </div>
</template>
