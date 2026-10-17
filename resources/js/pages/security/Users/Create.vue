<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import UserFormFooter from '@/components/security/UserForm/UserFormFooter.vue'
import UserFormHeader from '@/components/security/UserForm/UserFormHeader.vue'
import UserFormRolePicker from '@/components/security/UserForm/UserFormRolePicker.vue'
import UserFormS01Identity from '@/components/security/UserForm/UserFormS01Identity.vue'
import UserFormS02Credentials from '@/components/security/UserForm/UserFormS02Credentials.vue'
import UserFormSection from '@/components/security/UserForm/UserFormSection.vue'
import { useUserFormPage } from '@/composables/forms/useUserFormPage'
import { store, index } from '@/routes/security/users'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index().url },
            { title: 'Nuevo usuario' },
        ],
    },
})

/** Sections collected on creation; the rest of the profile is completed from the edit page. */
const CREATE_SECTIONS = [1, 2] as const

const { activeRole, formData, completion, autosave, pickRole, resetRole, setField } = useUserFormPage({
    sections: CREATE_SECTIONS,
})

const form = useForm(() => ({
    first_name:    formData.firstName    ?? '',
    last_name:     formData.lastName     ?? '',
    email:         formData.email        ?? '',
    role:          activeRole.value,
    password_mode: formData.passwordMode ?? 'link',
    password:      formData.passwordMode === 'manual' ? (formData.password ?? '') : undefined,
    document_type_id:    formData.docTypeId      ?? undefined,
    doc_number:          formData.docNumber      ?? undefined,
    phone1:              formData.phone1         ?? undefined,
    phone1_country_code: formData.phone1Dial     ?? undefined,
    gender_id:           formData.genderId       ?? undefined,
    nationality_id:      formData.nationalityId  ?? undefined,
    birth_date:          formData.birthDate      ?? undefined,
}))

const hasRequiredFields = computed(
    () => !!formData.firstName && !!formData.lastName && !!formData.email && !!activeRole.value,
)

function submit(): void {
    form.post(store().url)
}
</script>

<template>
    <Head title="Nuevo usuario" />

    <div class="up-content">
        <UserFormRolePicker v-if="!activeRole" @pick="pickRole" />

        <template v-else>
            <UserFormHeader
                :role="activeRole"
                :completion="completion"
                :autosave="autosave"
                description="Completá los datos básicos. Podrás agregar el perfil completo en el siguiente paso."
                @change-role="resetRole"
            />

            <div class="uf-sections uf-sections--create">
                <UserFormSection
                    num="01"
                    title="Identidad personal"
                    sub="Nombre, documento e información de contacto."
                    status="empty"
                    :section-id="1"
                    @save="() => {}"
                    @enter-edit="() => {}"
                    @cancel="() => {}"
                >
                    <UserFormS01Identity :data="formData" :set-field="setField" />
                </UserFormSection>

                <UserFormSection
                    num="02"
                    title="Credenciales de acceso"
                    sub="Correo electrónico y contraseña de inicio de sesión."
                    status="empty"
                    :section-id="2"
                    @save="() => {}"
                    @enter-edit="() => {}"
                    @cancel="() => {}"
                >
                    <UserFormS02Credentials :data="formData" :set-field="setField" />
                </UserFormSection>
            </div>

            <div v-if="Object.keys(form.errors).length" class="uf-server-errors">
                <p v-for="(msg, field) in form.errors" :key="field" class="uf-error">{{ msg }}</p>
            </div>

            <UserFormFooter
                :completion="completion"
                :total-sections="CREATE_SECTIONS.length"
                :autosave="autosave"
                :can-submit-extra="hasRequiredFields"
                :processing="form.processing"
                :show-save-draft="false"
                @discard="router.visit(index().url)"
                @submit="submit"
            />
        </template>
    </div>
</template>
