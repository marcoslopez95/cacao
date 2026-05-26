<script setup lang="ts">
import { onUnmounted, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import type { UserEditProps } from '@/types/userEdit'
import { useUserEditForm } from '@/composables/forms/useUserEditForm'
import { index } from '@/routes/security/users'
import UserFormHeader from '@/components/security/UserForm/UserFormHeader.vue'
import UserFormTabs from '@/components/security/UserForm/UserFormTabs.vue'
import UserFormSideNav from '@/components/security/UserForm/UserFormSideNav.vue'
import UserFormSection from '@/components/security/UserForm/UserFormSection.vue'
import UserFormS01Identity from '@/components/security/UserForm/UserFormS01Identity.vue'
import UserFormS02Credentials from '@/components/security/UserForm/UserFormS02Credentials.vue'
import UserFormS03Address from '@/components/security/UserForm/UserFormS03Address.vue'
import UserFormS04Demographic from '@/components/security/UserForm/UserFormS04Demographic.vue'
import UserFormS05Health from '@/components/security/UserForm/UserFormS05Health.vue'
import UserFormS06Consents from '@/components/security/UserForm/UserFormS06Consents.vue'
import UserFormS07Attachments from '@/components/security/UserForm/UserFormS07Attachments.vue'
import UserFormS08Academic from '@/components/security/UserForm/UserFormS08Academic.vue'
import UserFormS09PrevEducation from '@/components/security/UserForm/UserFormS09PrevEducation.vue'
import UserFormS10Languages from '@/components/security/UserForm/UserFormS10Languages.vue'
import UserFormS11Family from '@/components/security/UserForm/UserFormS11Family.vue'
import UserFormS12Socioeconomic from '@/components/security/UserForm/UserFormS12Socioeconomic.vue'
import UserFormS13Benefits from '@/components/security/UserForm/UserFormS13Benefits.vue'
import UserFormS14Housing from '@/components/security/UserForm/UserFormS14Housing.vue'
import UserFormS15Guardians from '@/components/security/UserForm/UserFormS15Guardians.vue'
import UserFormS16GuardianProfile from '@/components/security/UserForm/UserFormS16GuardianProfile.vue'
import UserFormS17ProfessorProfile from '@/components/security/UserForm/UserFormS17ProfessorProfile.vue'

const props = defineProps<UserEditProps>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Usuarios', href: index().url },
            { title: 'Editar usuario' },
        ],
    },
})

const {
    activeRole,
    activeTab,
    activeSection,
    formData,
    savedSections,
    editingSections,
    autosave,
    saving,
    errors,
    tabs,
    activeTabDef,
    completion,
    UF_SECTIONS,
    saveSection,
    editSection,
    cancelSection,
    statusFor,
    scrollToSection,
    setupScrollSpy,
    setField,
} = useUserEditForm(props)

let cleanupSpy: (() => void) | null = null

watch(
    activeTabDef,
    () => {
        cleanupSpy?.()
        if (!activeTabDef.value) return
        setTimeout(() => {
            cleanupSpy = setupScrollSpy(activeTabDef.value!.sections)
        }, 50)
    },
    { immediate: true },
)

onUnmounted(() => cleanupSpy?.())
</script>

<template>
    <Head :title="'Editar — ' + user.name" />

    <div class="up-content">
        <UserFormHeader
            v-if="activeRole"
            :role="activeRole"
            :is-edit="true"
            :completion="completion"
            :autosave="autosave"
            @change-role="router.visit(index().url)"
        />

        <UserFormTabs v-model="activeTab" :tabs="tabs" :completion="completion" />

        <div class="uf-form-layout">
            <UserFormSideNav
                :sections="activeTabDef?.sections ?? []"
                :active-section="activeSection"
                :completion="completion"
                :saved-sections="savedSections"
                :editing-sections="editingSections"
                @scroll-to="scrollToSection"
            />

            <div class="uf-sections">
                <UserFormSection
                    v-for="secNum in (activeTabDef?.sections ?? [])"
                    :key="secNum"
                    :num="String(secNum).padStart(2, '0')"
                    :title="UF_SECTIONS[secNum]?.title ?? `Sección ${secNum}`"
                    :sub="UF_SECTIONS[secNum]?.sub"
                    :note="UF_SECTIONS[secNum]?.note"
                    :comfy="UF_SECTIONS[secNum]?.comfy"
                    :status="statusFor(secNum)"
                    :saved="savedSections.has(secNum) ? { at: new Date() } : null"
                    :section-id="secNum"
                    :is-saving="saving === secNum"
                    :section-errors="errors[secNum]"
                    @save="saveSection(secNum)"
                    @enter-edit="editSection(secNum)"
                    @cancel="cancelSection(secNum)"
                >
                    <UserFormS01Identity          v-if="secNum === 1"  :data="formData" :set-field="setField" />
                    <UserFormS02Credentials       v-else-if="secNum === 2"  :data="formData" :set-field="setField" />
                    <UserFormS03Address           v-else-if="secNum === 3"  :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS04Demographic       v-else-if="secNum === 4"  :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS05Health            v-else-if="secNum === 5"  :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS06Consents          v-else-if="secNum === 6"  :data="formData" :set-field="setField" />
                    <UserFormS07Attachments       v-else-if="secNum === 7"  :data="formData" :set-field="setField" />
                    <UserFormS08Academic          v-else-if="secNum === 8"  :data="formData" :set-field="setField" />
                    <UserFormS09PrevEducation     v-else-if="secNum === 9"  :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS10Languages         v-else-if="secNum === 10" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS11Family            v-else-if="secNum === 11" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS12Socioeconomic     v-else-if="secNum === 12" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS13Benefits          v-else-if="secNum === 13" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS14Housing           v-else-if="secNum === 14" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS15Guardians         v-else-if="secNum === 15" :data="formData" :set-field="setField" />
                    <UserFormS16GuardianProfile   v-else-if="secNum === 16" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                    <UserFormS17ProfessorProfile  v-else-if="secNum === 17" :data="formData" :set-field="setField" :catalog-data="catalogData" />
                </UserFormSection>
            </div>
        </div>

        <div class="uf-footer-bar">
            <div class="uf-footer-left">
                <span class="uf-footer-progress">
                    {{ completion.pct }}% completo
                </span>
            </div>
            <div class="uf-footer-right">
                <button type="button" class="uf-btn ghost" @click="router.visit(index().url)">
                    Volver al listado
                </button>
            </div>
        </div>
    </div>
</template>
