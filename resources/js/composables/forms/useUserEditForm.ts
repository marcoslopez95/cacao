import { computed, reactive, ref } from 'vue'
import type { UserEditProps } from '@/types/userEdit'
import type { UserFormData } from '@/types/userForm'
import { UF_SECTIONS, UF_TABS } from '@/types/userFormCatalogs'
import type { RoleKey, TabDef } from '@/types/userFormCatalogs'
import type { SectionStatus } from '@/composables/forms/useUserFormPage'

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
        } catch {
            autosave.value = { status: 'idle', when: null }
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

    // Section save handlers — stubs; wired in tasks 06-13
    const handlers: Partial<Record<number, () => Promise<void>>> = {
        1:  () => Promise.resolve(),
        2:  () => Promise.resolve(),
        3:  () => Promise.resolve(),
        4:  () => Promise.resolve(),
        5:  () => Promise.resolve(),
        6:  () => Promise.resolve(),
        7:  () => Promise.resolve(),
        8:  () => Promise.resolve(),
        9:  () => Promise.resolve(),
        10: () => Promise.resolve(),
        11: () => Promise.resolve(),
        12: () => Promise.resolve(),
        13: () => Promise.resolve(),
        14: () => Promise.resolve(),
        15: () => Promise.resolve(),
        16: () => Promise.resolve(),
        17: () => Promise.resolve(),
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

function initialSavedSections(props: UserEditProps): number[] {
    const saved: number[] = []
    // S1 always saved (user exists)
    saved.push(1)
    // S2 — credentials exist
    saved.push(2)
    if (props.addresses.length)      saved.push(3)
    if (props.demographicProfile)    saved.push(4)
    if (props.healthProfile)         saved.push(5)
    if (props.consent)               saved.push(6)
    if (props.documents.length)      saved.push(7)
    if (props.student) {
        const s = props.student
        if (s.background)            saved.push(8, 9)
        if (s.languages.length)      saved.push(10)
        if (s.familyProfile)         saved.push(11)
        if (s.socioeconomicProfile)  saved.push(12)
        if (s.benefits.length)       saved.push(13)
        if (s.housingProfile)        saved.push(14)
    }
    if (props.guardian?.profile)     saved.push(16)
    if (props.professor?.staffProfile) saved.push(17)
    return saved
}

function buildInitialFormData(props: UserEditProps): UserFormData {
    const d: UserFormData = {}

    // S1 — Identity
    d.firstName = props.user.first_name
    d.lastName  = props.user.last_name

    // S2 — Credentials
    d.email = props.user.email

    // S3 — Addresses
    d.addresses = props.addresses.map(a => ({
        __id:    a.id,
        country: a.country?.name  ?? undefined,
        state:   a.state?.name    ?? undefined,
        muni:    a.municipality?.name ?? undefined,
        parish:  a.parish?.name   ?? undefined,
        zone:    a.geographic_zone?.name ?? undefined,
        line1:   a.address_line1  ?? undefined,
        line2:   a.address_line2  ?? undefined,
        primary: a.is_primary,
    }))

    // S4 — Demographic profile
    if (props.demographicProfile) {
        const p = props.demographicProfile
        d.birthCity       = p.birth_city ?? undefined
        d.birthState      = p.birth_state?.name  ?? undefined
        d.birthCountry    = p.birth_country?.name ?? undefined
        d.indigenous      = p.is_indigenous
        d.indigenousComm  = p.indigenous_community ?? undefined
        d.nativeLang      = p.native_language?.name ?? undefined
        d.returnedMigrant = p.is_returned_migrant
        d.returnFrom      = p.previous_country?.name ?? undefined
        d.religion        = p.religion?.name ?? undefined
        d.sport           = p.practices_sport
        d.sportName       = p.sport ?? undefined
        d.culture         = p.cultural_activities ?? undefined
    }

    // S5 — Health profile
    if (props.healthProfile) {
        const h = props.healthProfile
        d.bloodType       = h.blood_type?.name    ?? undefined
        d.weight          = h.weight_kg?.toString()  ?? undefined
        d.height          = h.height_cm?.toString()  ?? undefined
        d.disability      = h.has_disability
        d.disabilityType  = h.disability_type?.name ?? undefined
        d.disabilityDesc  = h.disability_description ?? undefined
        d.specialNeeds    = h.has_special_needs
        d.specialNeedsDesc = h.special_needs_description ?? undefined
        d.chronic         = h.chronic_condition   ?? undefined
        d.medication      = h.regular_medication  ?? undefined
        d.allergies       = h.allergies           ?? undefined
        d.insurance       = h.has_medical_insurance
        d.insuranceType   = h.insurance_type?.name ?? undefined
        d.emergencyName   = h.emergency_contact_name     ?? undefined
        d.emergencyPhone  = h.emergency_contact_phone    ?? undefined
        d.emergencyRel    = h.emergency_contact_relation ?? undefined
    }

    // S6 — Consents
    if (props.consent) {
        d.consent_data     = props.consent.accepts_data_processing
        d.consent_image    = props.consent.accepts_image_use
        d.consent_whatsapp = props.consent.accepts_whatsapp_contact
        d.consent_email    = props.consent.accepts_email_contact
    }

    // S7 — Documents
    d.attachments = props.documents.map(doc => ({
        __id:       doc.id,
        type:       doc.attachment_type?.name ?? undefined,
        filename:   doc.original_filename     ?? undefined,
        verified:   doc.is_verified,
        verifiedBy: doc.verified_by_user?.name ?? null,
        verifiedAt: doc.verified_at           ?? null,
    }))

    // Student-specific
    if (props.student) {
        const s = props.student

        // S9 — Educational background
        if (s.background) {
            const b = s.background
            d.prevInstitution     = b.previous_institution      ?? undefined
            d.prevInstitutionType = b.institution_type?.name    ?? undefined
            d.gradYear            = b.graduation_year?.toString() ?? undefined
            d.prevGpa             = b.previous_gpa?.toString()  ?? undefined
            d.repeated            = b.repeated_grade
            d.repeatedDesc        = b.repeated_grade_description ?? undefined
            d.transferReason      = b.transfer_reason?.name     ?? undefined
            d.priorUni            = b.has_prior_studies
            d.priorUniDesc        = b.prior_studies_description  ?? undefined
            d.digitalLevel        = b.digital_level?.name       ?? undefined
            d.motherEdu           = b.mother_education_level?.name ?? undefined
            d.fatherEdu           = b.father_education_level?.name ?? undefined
        }

        // S10 — Languages
        d.languages = s.languages.map(l => ({
            __id:   l.id ?? 0,
            lang:   l.language?.name       ?? undefined,
            level:  l.language_level?.name ?? undefined,
            mother: l.is_mother_tongue,
        }))

        // S11 — Family profile
        if (s.familyProfile) {
            const f = s.familyProfile
            d.repMarital      = f.guardian_marital_status?.name ?? undefined
            d.repChildren     = f.children_count?.toString()    ?? undefined
            d.siblings        = f.sibling_count?.toString()     ?? undefined
            d.siblingPos      = f.sibling_position?.toString()  ?? undefined
            d.living          = f.living_arrangement?.name      ?? undefined
            d.householdHead   = f.household_head_type?.name     ?? undefined
            d.householdHeadName = f.household_head_name         ?? undefined
        }

        // S12 — Socioeconomic profile
        if (s.socioeconomicProfile) {
            const e = s.socioeconomicProfile
            d.incomeRange        = e.income_range?.name      ?? undefined
            d.incomeSource       = e.income_source?.name     ?? undefined
            d.contributors       = e.household_earners?.toString() ?? undefined
            d.socioDate          = e.study_date              ?? undefined
            d.remit              = e.receives_remittances
            d.remitFrom          = e.remittance_country?.name ?? undefined
            d.studentWorks       = e.student_works
            d.employmentType     = e.employment_type?.name   ?? undefined
            d.weekHours          = e.weekly_work_hours?.toString() ?? undefined
            d.externalScholarship = e.has_scholarship
            d.scholarshipName    = e.scholarship_name        ?? undefined
            d.instBenefits       = e.has_institutional_benefit
        }

        // S13 — Benefits
        d.benefits = s.benefits.map(b => ({
            __id:    b.benefit_id ?? 0,
            benefit: b.benefit?.name ?? undefined,
            active:  b.is_active,
            start:   b.since  ?? undefined,
            end:     b.until  ?? undefined,
        }))

        // S14 — Housing profile
        if (s.housingProfile) {
            const h = s.housingProfile
            d.housing      = h.housing_type?.name           ?? undefined
            d.tenure       = h.tenure_type?.name            ?? undefined
            d.construction = h.construction_material?.name  ?? undefined
            d.rooms        = h.room_count?.toString()        ?? undefined
            d.bathrooms    = h.bathroom_count?.toString()    ?? undefined
            d.peopleHome   = h.household_members?.toString() ?? undefined
            d.commute      = h.commute_time?.name            ?? undefined
            d.transport    = h.transport_type?.name          ?? undefined
            d.svc_water = h.services.some(sv => sv.code === 'water'       && sv.is_available)
            d.svc_elec  = h.services.some(sv => sv.code === 'electricity' && sv.is_available)
            d.svc_gas   = h.services.some(sv => sv.code === 'gas'         && sv.is_available)
            d.svc_inet  = h.services.some(sv => sv.code === 'internet'    && sv.is_available)
        }
    }

    // Guardian-specific
    if (props.guardian?.profile) {
        const g = props.guardian.profile
        d.occupation      = g.occupation         ?? undefined
        d.employer        = g.employer            ?? undefined
        d.workPhone       = g.work_phone          ?? undefined
        d.guardianMarital = g.marital_status?.name ?? undefined
        d.guardianEdu     = g.education_level?.name ?? undefined
    }

    // Professor-specific
    if (props.professor?.staffProfile) {
        const p = props.professor.staffProfile
        d.empCode     = p.employee_code         ?? undefined
        d.degree      = p.academic_title        ?? undefined
        d.specialty   = p.specialty             ?? undefined
        d.contract    = p.contract_type?.name   ?? undefined
        d.dedication  = p.dedication_type?.name ?? undefined
        d.weeklyHours = p.weekly_hour_load?.toString()  ?? undefined
        d.hireDate    = p.hire_date             ?? undefined
        d.endDate     = p.termination_date      ?? undefined
        d.emplStatus  = p.employment_status?.name ?? undefined
        d.isCoord     = p.is_coordinator
        d.coordDept   = p.coordinated_department?.name ?? undefined
        d.coordSince  = p.coordinator_since     ?? undefined
    }

    return d
}
