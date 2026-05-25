import { computed, reactive, ref } from 'vue'
import { useHttp } from '@inertiajs/vue3'
import type { UserEditProps } from '@/types/userEdit'
import type { UserFormData } from '@/types/userForm'
import { UF_SECTIONS, UF_TABS } from '@/types/userFormCatalogs'
import type { RoleKey, TabDef } from '@/types/userFormCatalogs'
import type { SectionStatus } from '@/composables/forms/useUserFormPage'
import { store as storeAddress, update as updateAddress, destroy as destroyAddress } from '@/routes/security/users/addresses'
import { upsert as upsertDemographic } from '@/routes/security/users/demographic-profile'
import { upsert as upsertHealth } from '@/routes/security/users/health-profile'
import { upsert as upsertBackground } from '@/routes/security/students/background'
import { store as storeLanguage, destroy as destroyLanguage } from '@/routes/security/students/languages'
import { upsert as upsertFamily } from '@/routes/security/students/family-profile'
import { upsert as upsertSocioeconomic } from '@/routes/security/students/socioeconomic-profile'
import { store as storeBenefit, destroy as destroyBenefit } from '@/routes/security/students/benefits'
import { upsert as upsertHousing } from '@/routes/security/students/housing-profile'
import { sync as syncHousingServices } from '@/routes/security/students/housing-profile/services'
import { upsert as upsertGuardianProfile } from '@/routes/security/guardians/profile'
import { upsert as upsertStaffProfile } from '@/routes/academic/professors/staff-profile'

export function useUserEditForm(props: UserEditProps) {
    const roleKey = (props.user.roles[0] ?? '') as RoleKey | ''

    const activeTab     = ref<string>(UF_TABS[roleKey as RoleKey]?.[0]?.key ?? '')
    const activeSection = ref<number | null>(null)
    const savedSections = ref<Set<number>>(new Set(initialSavedSections(props)))
    const editingSections = ref<Set<number>>(new Set())
    const saving  = ref<number | null>(null)
    const errors  = ref<Record<number, Record<string, string>>>({})

    const formData = reactive<UserFormData>(buildInitialFormData(props))

    const tabs = computed<TabDef[]>(() => UF_TABS[roleKey as RoleKey] ?? [])

    const activeTabDef = computed(
        () => tabs.value.find(t => t.key === activeTab.value) ?? tabs.value[0],
    )

    const completion = computed(() => {
        const total = tabs.value.flatMap(t => t.sections).length
        const done  = savedSections.value.size
        return {
            pct: total > 0 ? Math.round((done / total) * 100) : 0,
            sectionsComplete: savedSections.value,
            sectionsPartial:  new Set<number>(),
        }
    })

    const autosave = ref<{ status: 'idle' | 'saving' | 'saved'; when: Date | null }>({
        status: 'idle',
        when: null,
    })

    // Single http instance; transform() overrides data per call
    const http = useHttp()

    function statusFor(n: number): SectionStatus {
        if (editingSections.value.has(n)) return 'editing'
        if (savedSections.value.has(n)) return 'complete'
        return 'empty'
    }

    async function saveSection(n: number): Promise<void> {
        saving.value = n
        errors.value[n] = {}
        autosave.value = { status: 'saving', when: null }
        try {
            await (handlers[n] ?? (() => Promise.resolve()))()
            savedSections.value = new Set([...savedSections.value, n])
            const next = new Set(editingSections.value)
            next.delete(n)
            editingSections.value = next
            autosave.value = { status: 'saved', when: new Date() }
        } catch (e) {
            autosave.value = { status: 'idle', when: null }
            if (e && typeof e === 'object' && 'errors' in e) {
                errors.value[n] = (e as { errors: Record<string, string> }).errors
            }
        } finally {
            saving.value = null
        }
    }

    function editSection(n: number): void {
        editingSections.value = new Set([...editingSections.value, n])
    }

    function cancelSection(n: number): void {
        const next = new Set(editingSections.value)
        next.delete(n)
        editingSections.value = next
    }

    function setField<K extends keyof UserFormData>(key: K, value: UserFormData[K]): void {
        ;(formData as Record<string, unknown>)[key as string] = value
    }

    function scrollToSection(n: number): void {
        document.getElementById(`sec-${n}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }

    function setupScrollSpy(sectionNums: number[]): () => void {
        const els = sectionNums
            .map(n => document.getElementById(`sec-${n}`))
            .filter((el): el is HTMLElement => el !== null)

        if (!els.length) return () => {}

        const obs = new IntersectionObserver(
            entries => {
                const visible = entries
                    .filter(e => e.isIntersecting)
                    .sort((a, b) => a.target.getBoundingClientRect().top - b.target.getBoundingClientRect().top)
                if (visible.length) {
                    activeSection.value = parseInt(visible[0].target.id.replace('sec-', ''))
                }
            },
            { rootMargin: '-30% 0px -50% 0px' },
        )

        els.forEach(el => obs.observe(el))
        return () => obs.disconnect()
    }

    // ── Section save handlers ──────────────────────────────────────────────

    async function saveAddresses(): Promise<void> {
        const userId  = props.user.id
        const current = formData.addresses ?? []
        const savedIds = new Set(props.addresses.map(a => a.id))

        const newItems      = current.filter(a => !savedIds.has(a.__id))
        const existingItems = current.filter(a => savedIds.has(a.__id))
        const currentIds    = new Set(existingItems.map(a => a.__id))
        const deletedIds    = [...savedIds].filter(id => !currentIds.has(id))

        for (const addr of newItems) {
            await http.transform(() => addressPayload(addr)).post(storeAddress({ user: userId }).url)
        }
        for (const addr of existingItems) {
            await http.transform(() => addressPayload(addr)).put(updateAddress({ user: userId, address: addr.__id }).url)
        }
        for (const id of deletedIds) {
            await http.transform(() => ({})).delete(destroyAddress({ user: userId, address: id }).url)
        }
    }

    async function saveDemographic(): Promise<void> {
        const userId = props.user.id
        await http.transform(() => ({
            birth_city:           formData.birthCity          ?? null,
            birth_state:          formData.birthState         ?? null,
            birth_country:        formData.birthCountry       ?? null,
            is_indigenous:        formData.indigenous         ?? false,
            indigenous_community: formData.indigenousComm     ?? null,
            native_language:      formData.nativeLang         ?? null,
            is_returned_migrant:  formData.returnedMigrant    ?? false,
            previous_country:     formData.returnFrom         ?? null,
            religion:             formData.religion           ?? null,
            practices_sport:      formData.sport              ?? false,
            sport:                formData.sportName          ?? null,
            cultural_activities:  formData.culture            ?? null,
        })).put(upsertDemographic({ user: userId }).url)
    }

    async function saveHealth(): Promise<void> {
        const userId = props.user.id
        await http.transform(() => ({
            blood_type:                 formData.bloodType        ?? null,
            weight_kg:                  formData.weight           ? Number(formData.weight) : null,
            height_cm:                  formData.height           ? Number(formData.height) : null,
            has_disability:             formData.disability       ?? false,
            disability_type:            formData.disabilityType   ?? null,
            disability_description:     formData.disabilityDesc   ?? null,
            has_special_needs:          formData.specialNeeds     ?? false,
            special_needs_description:  formData.specialNeedsDesc ?? null,
            chronic_condition:          formData.chronic          ?? null,
            regular_medication:         formData.medication       ?? null,
            allergies:                  formData.allergies        ?? null,
            has_medical_insurance:      formData.insurance        ?? false,
            insurance_type:             formData.insuranceType    ?? null,
            emergency_contact_name:     formData.emergencyName    ?? null,
            emergency_contact_phone:    formData.emergencyPhone   ?? null,
            emergency_contact_relation: formData.emergencyRel     ?? null,
        })).put(upsertHealth({ user: userId }).url)
    }

    async function saveBackground(): Promise<void> {
        if (!props.student) return
        await http.transform(() => ({
            // S8 fields (academic status fields live on students table, handled separately)
            previous_institution:       formData.prevInstitution     ?? null,
            institution_type:           formData.prevInstitutionType  ?? null,
            graduation_year:            formData.gradYear             ? Number(formData.gradYear) : null,
            previous_gpa:               formData.prevGpa              ? Number(formData.prevGpa)  : null,
            repeated_grade:             formData.repeated             ?? false,
            repeated_grade_description: formData.repeatedDesc         ?? null,
            transfer_reason:            formData.transferReason       ?? null,
            has_prior_studies:          formData.priorUni             ?? false,
            prior_studies_description:  formData.priorUniDesc         ?? null,
            digital_level:              formData.digitalLevel         ?? null,
            mother_education_level:     formData.motherEdu            ?? null,
            father_education_level:     formData.fatherEdu            ?? null,
        })).put(upsertBackground({ student: props.student.id }).url)
    }

    async function saveLanguages(): Promise<void> {
        if (!props.student) return
        const studentId  = props.student.id
        const savedIds   = new Set(props.student.languages.map(l => l.id ?? 0).filter(Boolean))
        const current    = formData.languages ?? []
        const currentIds = new Set(current.filter(l => savedIds.has(l.__id)).map(l => l.__id))

        for (const id of [...savedIds].filter(id => !currentIds.has(id))) {
            await http.transform(() => ({})).delete(destroyLanguage({ student: studentId, language: id }).url)
        }
        for (const lang of current.filter(l => !savedIds.has(l.__id))) {
            await http.transform(() => ({
                language:         lang.lang   ?? null,
                language_level:   lang.level  ?? null,
                is_mother_tongue: lang.mother ?? false,
            })).post(storeLanguage({ student: studentId }).url)
        }
    }

    async function saveFamily(): Promise<void> {
        if (!props.student) return
        await http.transform(() => ({
            guardian_marital_status: formData.repMarital        ?? null,
            children_count:          formData.repChildren        ? Number(formData.repChildren)  : null,
            sibling_count:           formData.siblings           ? Number(formData.siblings)     : null,
            sibling_position:        formData.siblingPos         ? Number(formData.siblingPos)   : null,
            living_arrangement:      formData.living             ?? null,
            household_head_type:     formData.householdHead      ?? null,
            household_head_name:     formData.householdHeadName  ?? null,
        })).put(upsertFamily({ student: props.student.id }).url)
    }

    async function saveSocioeconomic(): Promise<void> {
        if (!props.student) return
        await http.transform(() => ({
            income_range:             formData.incomeRange           ?? null,
            income_source:            formData.incomeSource          ?? null,
            household_earners:        formData.contributors          ? Number(formData.contributors) : null,
            study_date:               formData.socioDate             ?? null,
            receives_remittances:     formData.remit                 ?? false,
            remittance_country:       formData.remitFrom             ?? null,
            student_works:            formData.studentWorks          ?? false,
            employment_type:          formData.employmentType        ?? null,
            weekly_work_hours:        formData.weekHours             ? Number(formData.weekHours) : null,
            has_scholarship:          formData.externalScholarship   ?? false,
            scholarship_name:         formData.scholarshipName       ?? null,
            has_institutional_benefit: formData.instBenefits         ?? false,
        })).put(upsertSocioeconomic({ student: props.student.id }).url)
    }

    async function saveBenefits(): Promise<void> {
        if (!props.student) return
        const studentId = props.student.id
        const savedIds  = new Set(props.student.benefits.map(b => b.benefit_id).filter((id): id is number => id !== null))
        const current   = formData.benefits ?? []
        const currentIds = new Set(current.map(b => b.__id))

        for (const id of [...savedIds].filter(id => !currentIds.has(id))) {
            await http.transform(() => ({})).delete(destroyBenefit({ student: studentId, benefit: id }).url)
        }
        for (const b of current.filter(b => !savedIds.has(b.__id))) {
            await http.transform(() => ({
                active: b.active ?? true,
                since:  b.start  ?? null,
                until:  b.end    ?? null,
            })).post(storeBenefit({ student: studentId, benefit: b.__id }).url)
        }
    }

    async function saveHousing(): Promise<void> {
        if (!props.student) return
        const studentId = props.student.id
        await http.transform(() => ({
            housing_type:          formData.housing       ?? null,
            tenure_type:           formData.tenure        ?? null,
            construction_material: formData.construction  ?? null,
            room_count:            formData.rooms         ? Number(formData.rooms)      : null,
            bathroom_count:        formData.bathrooms     ? Number(formData.bathrooms)  : null,
            household_members:     formData.peopleHome    ? Number(formData.peopleHome) : null,
            commute_time:          formData.commute       ?? null,
            transport_type:        formData.transport     ?? null,
        })).put(upsertHousing({ student: studentId }).url)
        const services: string[] = []
        if (formData.svc_water) services.push('water')
        if (formData.svc_elec)  services.push('electricity')
        if (formData.svc_gas)   services.push('gas')
        if (formData.svc_inet)  services.push('internet')
        await http.transform(() => ({ services })).patch(syncHousingServices({ student: studentId }).url)
    }

    async function saveGuardianProfile(): Promise<void> {
        if (!props.guardian) return
        await http.transform(() => ({
            occupation:       formData.occupation       ?? null,
            employer:         formData.employer         ?? null,
            work_phone:       formData.workPhone        ?? null,
            marital_status:   formData.guardianMarital  ?? null,
            education_level:  formData.guardianEdu      ?? null,
        })).put(upsertGuardianProfile({ guardian: props.guardian.id }).url)
    }

    async function saveStaffProfile(): Promise<void> {
        if (!props.professor) return
        await http.transform(() => ({
            employee_code:         formData.empCode      ?? null,
            academic_title:        formData.degree       ?? null,
            specialty:             formData.specialty    ?? null,
            contract_type:         formData.contract     ?? null,
            dedication_type:       formData.dedication   ?? null,
            weekly_hour_load:      formData.weeklyHours  ? Number(formData.weeklyHours) : null,
            hire_date:             formData.hireDate     ?? null,
            termination_date:      formData.endDate      ?? null,
            employment_status:     formData.emplStatus   ?? null,
            is_coordinator:        formData.isCoord      ?? false,
            coordinated_department: formData.coordDept   ?? null,
            coordinator_since:     formData.coordSince   ?? null,
        })).put(upsertStaffProfile({ professor: props.professor.id }).url)
    }

    // S06 (consents) and S07 (documents) are stubs:
    // S06: StoreUserConsentRequest::authorize() only allows the user themselves,
    //      not admins — the Edit.vue section is read-only for display.
    // S07: File uploads are a separate feature; only metadata is shown here.

    const handlers: Partial<Record<number, () => Promise<void>>> = {
        1:  () => Promise.resolve(),
        2:  () => Promise.resolve(),
        3:  saveAddresses,
        4:  saveDemographic,
        5:  saveHealth,
        6:  () => Promise.resolve(),
        7:  () => Promise.resolve(),
        8:  saveBackground,
        9:  saveBackground,
        10: saveLanguages,
        11: saveFamily,
        12: saveSocioeconomic,
        13: saveBenefits,
        14: saveHousing,
        15: () => Promise.resolve(),
        16: saveGuardianProfile,
        17: saveStaffProfile,
    }

    return {
        activeRole: roleKey,
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
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────

function addressPayload(addr: { country?: string; state?: string; muni?: string; parish?: string; zone?: string; line1?: string; line2?: string; primary?: boolean }) {
    return {
        country:       addr.country  ?? null,
        state:         addr.state    ?? null,
        municipality:  addr.muni     ?? null,
        parish:        addr.parish   ?? null,
        zone:          addr.zone     ?? null,
        address_line1: addr.line1    ?? null,
        address_line2: addr.line2    ?? null,
        is_primary:    addr.primary  ?? false,
    }
}

function initialSavedSections(props: UserEditProps): number[] {
    const saved: number[] = []
    saved.push(1, 2)
    if (props.addresses.length)       saved.push(3)
    if (props.demographicProfile)     saved.push(4)
    if (props.healthProfile)          saved.push(5)
    if (props.consent)                saved.push(6)
    if (props.documents.length)       saved.push(7)
    if (props.student) {
        const s = props.student
        if (s.background)             saved.push(8, 9)
        if (s.languages.length)       saved.push(10)
        if (s.familyProfile)          saved.push(11)
        if (s.socioeconomicProfile)   saved.push(12)
        if (s.benefits.length)        saved.push(13)
        if (s.housingProfile)         saved.push(14)
    }
    if (props.guardian?.profile)      saved.push(16)
    if (props.professor?.staffProfile) saved.push(17)
    return saved
}

function buildInitialFormData(props: UserEditProps): UserFormData {
    const d: UserFormData = {}

    d.firstName = props.user.first_name
    d.lastName  = props.user.last_name
    d.email     = props.user.email

    d.addresses = props.addresses.map(a => ({
        __id:    a.id,
        country: a.country?.name            ?? undefined,
        state:   a.state?.name              ?? undefined,
        muni:    a.municipality?.name       ?? undefined,
        parish:  a.parish?.name             ?? undefined,
        zone:    a.geographic_zone?.name    ?? undefined,
        line1:   a.address_line1            ?? undefined,
        line2:   a.address_line2            ?? undefined,
        primary: a.is_primary,
    }))

    if (props.demographicProfile) {
        const p = props.demographicProfile
        d.birthCity       = p.birth_city                    ?? undefined
        d.birthState      = p.birth_state?.name             ?? undefined
        d.birthCountry    = p.birth_country?.name           ?? undefined
        d.indigenous      = p.is_indigenous
        d.indigenousComm  = p.indigenous_community          ?? undefined
        d.nativeLang      = p.native_language?.name         ?? undefined
        d.returnedMigrant = p.is_returned_migrant
        d.returnFrom      = p.previous_country?.name        ?? undefined
        d.religion        = p.religion?.name                ?? undefined
        d.sport           = p.practices_sport
        d.sportName       = p.sport                         ?? undefined
        d.culture         = p.cultural_activities           ?? undefined
    }

    if (props.healthProfile) {
        const h = props.healthProfile
        d.bloodType        = h.blood_type?.name             ?? undefined
        d.weight           = h.weight_kg?.toString()        ?? undefined
        d.height           = h.height_cm?.toString()        ?? undefined
        d.disability       = h.has_disability
        d.disabilityType   = h.disability_type?.name        ?? undefined
        d.disabilityDesc   = h.disability_description       ?? undefined
        d.specialNeeds     = h.has_special_needs
        d.specialNeedsDesc = h.special_needs_description    ?? undefined
        d.chronic          = h.chronic_condition            ?? undefined
        d.medication       = h.regular_medication           ?? undefined
        d.allergies        = h.allergies                    ?? undefined
        d.insurance        = h.has_medical_insurance
        d.insuranceType    = h.insurance_type?.name         ?? undefined
        d.emergencyName    = h.emergency_contact_name       ?? undefined
        d.emergencyPhone   = h.emergency_contact_phone      ?? undefined
        d.emergencyRel     = h.emergency_contact_relation   ?? undefined
    }

    if (props.consent) {
        d.consent_data     = props.consent.accepts_data_processing
        d.consent_image    = props.consent.accepts_image_use
        d.consent_whatsapp = props.consent.accepts_whatsapp_contact
        d.consent_email    = props.consent.accepts_email_contact
    }

    d.attachments = props.documents.map(doc => ({
        __id:       doc.id,
        type:       doc.attachment_type?.name    ?? undefined,
        filename:   doc.original_filename        ?? undefined,
        verified:   doc.is_verified,
        verifiedBy: doc.verified_by_user?.name  ?? null,
        verifiedAt: doc.verified_at             ?? null,
    }))

    if (props.student) {
        const s = props.student
        if (s.background) {
            const b = s.background
            d.prevInstitution     = b.previous_institution          ?? undefined
            d.prevInstitutionType = b.institution_type?.name        ?? undefined
            d.gradYear            = b.graduation_year?.toString()    ?? undefined
            d.prevGpa             = b.previous_gpa?.toString()       ?? undefined
            d.repeated            = b.repeated_grade
            d.repeatedDesc        = b.repeated_grade_description     ?? undefined
            d.transferReason      = b.transfer_reason?.name         ?? undefined
            d.priorUni            = b.has_prior_studies
            d.priorUniDesc        = b.prior_studies_description      ?? undefined
            d.digitalLevel        = b.digital_level?.name            ?? undefined
            d.motherEdu           = b.mother_education_level?.name   ?? undefined
            d.fatherEdu           = b.father_education_level?.name   ?? undefined
        }
        d.languages = s.languages.map(l => ({
            __id:   l.id ?? 0,
            lang:   l.language?.name        ?? undefined,
            level:  l.language_level?.name  ?? undefined,
            mother: l.is_mother_tongue,
        }))
        if (s.familyProfile) {
            const f = s.familyProfile
            d.repMarital        = f.guardian_marital_status?.name   ?? undefined
            d.repChildren       = f.children_count?.toString()      ?? undefined
            d.siblings          = f.sibling_count?.toString()       ?? undefined
            d.siblingPos        = f.sibling_position?.toString()    ?? undefined
            d.living            = f.living_arrangement?.name        ?? undefined
            d.householdHead     = f.household_head_type?.name       ?? undefined
            d.householdHeadName = f.household_head_name             ?? undefined
        }
        if (s.socioeconomicProfile) {
            const e = s.socioeconomicProfile
            d.incomeRange         = e.income_range?.name            ?? undefined
            d.incomeSource        = e.income_source?.name           ?? undefined
            d.contributors        = e.household_earners?.toString() ?? undefined
            d.socioDate           = e.study_date                    ?? undefined
            d.remit               = e.receives_remittances
            d.remitFrom           = e.remittance_country?.name      ?? undefined
            d.studentWorks        = e.student_works
            d.employmentType      = e.employment_type?.name         ?? undefined
            d.weekHours           = e.weekly_work_hours?.toString() ?? undefined
            d.externalScholarship = e.has_scholarship
            d.scholarshipName     = e.scholarship_name              ?? undefined
            d.instBenefits        = e.has_institutional_benefit
        }
        d.benefits = s.benefits.map(b => ({
            __id:    b.benefit_id ?? 0,
            benefit: b.benefit?.name    ?? undefined,
            active:  b.is_active,
            start:   b.since            ?? undefined,
            end:     b.until            ?? undefined,
        }))
        if (s.housingProfile) {
            const h = s.housingProfile
            d.housing      = h.housing_type?.name               ?? undefined
            d.tenure       = h.tenure_type?.name                ?? undefined
            d.construction = h.construction_material?.name      ?? undefined
            d.rooms        = h.room_count?.toString()            ?? undefined
            d.bathrooms    = h.bathroom_count?.toString()        ?? undefined
            d.peopleHome   = h.household_members?.toString()     ?? undefined
            d.commute      = h.commute_time?.name                ?? undefined
            d.transport    = h.transport_type?.name              ?? undefined
            d.svc_water    = h.services.some(sv => sv.code === 'water'        && sv.is_available)
            d.svc_elec     = h.services.some(sv => sv.code === 'electricity'  && sv.is_available)
            d.svc_gas      = h.services.some(sv => sv.code === 'gas'          && sv.is_available)
            d.svc_inet     = h.services.some(sv => sv.code === 'internet'     && sv.is_available)
        }
    }

    if (props.guardian?.profile) {
        const g = props.guardian.profile
        d.occupation      = g.occupation              ?? undefined
        d.employer        = g.employer                ?? undefined
        d.workPhone       = g.work_phone              ?? undefined
        d.guardianMarital = g.marital_status?.name    ?? undefined
        d.guardianEdu     = g.education_level?.name   ?? undefined
    }

    if (props.professor?.staffProfile) {
        const p = props.professor.staffProfile
        d.empCode     = p.employee_code                 ?? undefined
        d.degree      = p.academic_title                ?? undefined
        d.specialty   = p.specialty                     ?? undefined
        d.contract    = p.contract_type?.name           ?? undefined
        d.dedication  = p.dedication_type?.name         ?? undefined
        d.weeklyHours = p.weekly_hour_load?.toString()  ?? undefined
        d.hireDate    = p.hire_date                     ?? undefined
        d.endDate     = p.termination_date              ?? undefined
        d.emplStatus  = p.employment_status?.name       ?? undefined
        d.isCoord     = p.is_coordinator
        d.coordDept   = p.coordinated_department?.name  ?? undefined
        d.coordSince  = p.coordinator_since             ?? undefined
    }

    return d
}
