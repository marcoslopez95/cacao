<script setup lang="ts">
import type { UserFormData } from '@/types/userForm'

const props = defineProps<{
    data: UserFormData
    setField: <K extends keyof UserFormData>(key: K, value: UserFormData[K]) => void
}>()

const CONSENTS: Array<{
    key: keyof UserFormData
    label: string
    sub: string
    required?: boolean
}> = [
    {
        key: 'consent_data',
        label: 'Tratamiento de datos personales',
        sub: 'Autorizo a la institución a recopilar, almacenar y procesar mis datos personales para la gestión académica y administrativa, de acuerdo a la normativa vigente.',
        required: true,
    },
    {
        key: 'consent_image',
        label: 'Uso de imagen',
        sub: 'Autorizo el uso de mi imagen en materiales institucionales, publicaciones y comunicaciones oficiales de la institución.',
    },
    {
        key: 'consent_whatsapp',
        label: 'Contacto por WhatsApp',
        sub: 'Acepto recibir notificaciones, avisos y comunicaciones de la institución a través de WhatsApp en el número registrado.',
    },
    {
        key: 'consent_email',
        label: 'Contacto por correo electrónico',
        sub: 'Acepto recibir comunicaciones institucionales en el correo electrónico registrado, incluyendo boletines y avisos administrativos.',
    },
]
</script>

<template>
    <div class="uf-policy-meta">
        <span>Versión de política de privacidad</span>
        <strong>v1.0 — vigente desde 2026-04-01</strong>
    </div>

    <div class="uf-consent">
        <label
            v-for="c in CONSENTS"
            :key="c.key as string"
            class="uf-consent-item"
            :class="{ checked: !!data[c.key] }"
        >
            <input
                type="checkbox"
                :dusk="'consent-' + (c.key as string)"
                :checked="!!data[c.key]"
                @change="setField(c.key, ($event.target as HTMLInputElement).checked)"
            />
            <span class="box" />
            <span class="text">
                <strong>{{ c.label }}<span v-if="c.required" class="req"> *</span></strong>
                <span>{{ c.sub }}</span>
            </span>
        </label>
    </div>
</template>
