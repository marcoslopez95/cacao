<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import { store } from '@/routes/security/users'
import { index } from '@/routes/security/users'
import { UF_ROLES } from '@/types/userFormCatalogs'
import type { RoleKey } from '@/types/userFormCatalogs'
import type { UserFormData } from '@/types/userForm'
import UserFormRolePicker from '@/components/security/UserForm/UserFormRolePicker.vue'
import UserFormSection from '@/components/security/UserForm/UserFormSection.vue'
import UserFormS01Identity from '@/components/security/UserForm/UserFormS01Identity.vue'
import UserFormS02Credentials from '@/components/security/UserForm/UserFormS02Credentials.vue'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index().url },
            { title: 'Nuevo usuario' },
        ],
    },
})

const activeRole = ref<RoleKey | ''>('')
const localData  = reactive<UserFormData>({})

function setField<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
    ;(localData as Record<string, unknown>)[key as string] = value
}

const form = useForm(() => ({
    first_name:    localData.firstName    ?? '',
    last_name:     localData.lastName     ?? '',
    email:         localData.email        ?? '',
    role:          activeRole.value,
    password_mode: localData.passwordMode ?? 'link',
    password:      localData.passwordMode === 'manual' ? (localData.password ?? '') : undefined,
    document_type_id:    localData.docTypeId      ?? undefined,
    doc_number:          localData.docNumber      ?? undefined,
    phone1:              localData.phone1         ?? undefined,
    phone1_country_code: localData.phone1Dial     ?? undefined,
    gender_id:           localData.genderId       ?? undefined,
    nationality_id:      localData.nationalityId  ?? undefined,
    birth_date:          localData.birthDate      ?? undefined,
}))

const canSubmit = computed(
    () => !!localData.firstName && !!localData.lastName && !!localData.email && !!activeRole.value,
)

function submit(): void {
    form.post(store().url)
}
</script>

<template>
    <Head title="Nuevo usuario" />

    <div class="up-content">
        <UserFormRolePicker v-if="!activeRole" @pick="k => (activeRole = k)" />

        <template v-else>
            <div class="uf-create-header">
                <div class="uf-create-header-top">
                    <h1 class="uf-create-title">
                        Nuevo usuario
                        <span class="uf-mode-tag">{{ UF_ROLES[activeRole].label }}</span>
                    </h1>
                    <button type="button" class="uf-btn ghost sm" @click="activeRole = ''">
                        Cambiar rol
                    </button>
                </div>
                <p class="uf-create-sub">
                    Completá los datos básicos. Podrás agregar el perfil completo en el siguiente paso.
                </p>
            </div>

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
                    <UserFormS01Identity :data="localData" :set-field="setField" />
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
                    <UserFormS02Credentials :data="localData" :set-field="setField" />
                </UserFormSection>
            </div>

            <div v-if="Object.keys(form.errors).length" class="uf-server-errors">
                <p v-for="(msg, field) in form.errors" :key="field" class="uf-error">{{ msg }}</p>
            </div>

            <div class="uf-create-footer">
                <button type="button" class="uf-btn ghost" @click="router.visit(index().url)">
                    Cancelar
                </button>
                <button
                    type="button"
                    class="uf-btn primary"
                    :disabled="!canSubmit || form.processing"
                    @click="submit"
                >
                    {{ form.processing ? 'Creando usuario…' : 'Crear usuario →' }}
                </button>
            </div>
        </template>
    </div>
</template>
