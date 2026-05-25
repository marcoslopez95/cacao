// ============================================================
// CACAO · User edit props interfaces
// Mirror of Laravel Admin Resource output — snake_case throughout
// ============================================================

// ---------------------------------------------------------------------------
// Shared catalog reference (id + name; some catalogs also carry code/iso2)
// ---------------------------------------------------------------------------

export interface CatalogRef {
    id: number
    name: string
    code?: string
    iso2?: string
}

// ---------------------------------------------------------------------------
// UserEditResource
// ---------------------------------------------------------------------------

export interface UserEditData {
    id: number
    first_name: string
    last_name: string
    name: string
    email: string
    active: boolean
    roles: string[]
    created_at: string | null
}

// ---------------------------------------------------------------------------
// UserAddressResource
// ---------------------------------------------------------------------------

export interface UserAddressData {
    id: number
    user_id: number
    country_id: number | null
    state_id: number | null
    municipality_id: number | null
    parish_id: number | null
    geographic_zone_id: number | null
    address_line1: string | null
    address_line2: string | null
    is_primary: boolean
    created_at: string | null
    country: CatalogRef | null
    state: CatalogRef | null
    municipality: CatalogRef | null
    parish: CatalogRef | null
    geographic_zone: CatalogRef | null
}

// ---------------------------------------------------------------------------
// DemographicProfileResource
// ---------------------------------------------------------------------------

export interface DemographicProfileData {
    id: number
    user_id: number
    birth_city: string | null
    birth_state_id: number | null
    birth_country_id: number | null
    is_indigenous: boolean
    indigenous_community: string | null
    native_language_id: number | null
    is_returned_migrant: boolean
    previous_country_id: number | null
    religion_id: number | null
    practices_sport: boolean
    sport: string | null
    cultural_activities: string | null
    created_at: string | null
    updated_at: string | null
    birth_state: CatalogRef | null
    birth_country: CatalogRef | null
    native_language: CatalogRef | null
    previous_country: CatalogRef | null
    religion: CatalogRef | null
}

// ---------------------------------------------------------------------------
// HealthProfileResource
// ---------------------------------------------------------------------------

export interface HealthProfileData {
    id: number
    user_id: number
    blood_type_id: number | null
    weight_kg: number | null
    height_cm: number | null
    has_disability: boolean
    disability_type_id: number | null
    disability_description: string | null
    has_special_needs: boolean
    special_needs_description: string | null
    chronic_condition: string | null
    regular_medication: string | null
    allergies: string | null
    has_medical_insurance: boolean
    insurance_type_id: number | null
    emergency_contact_name: string | null
    emergency_contact_phone: string | null
    emergency_contact_relation: string | null
    created_at: string | null
    updated_at: string | null
    blood_type: CatalogRef | null
    disability_type: CatalogRef | null
    insurance_type: CatalogRef | null
}

// ---------------------------------------------------------------------------
// UserConsentResource
// ---------------------------------------------------------------------------

export interface UserConsentData {
    id: number
    user_id: number
    policy_version: string | null
    accepts_data_processing: boolean
    accepts_image_use: boolean
    accepts_whatsapp_contact: boolean
    accepts_email_contact: boolean
    ip_address: string | null
    user_agent: string | null
    granted_at: string | null
    revoked_at: string | null
}

// ---------------------------------------------------------------------------
// UserDocumentResource
// ---------------------------------------------------------------------------

export interface UserDocumentData {
    id: number
    user_id: number
    attachment_type_id: number | null
    file_url: string | null
    original_filename: string | null
    mime_type: string | null
    file_size_bytes: number | null
    is_verified: boolean
    verified_by: number | null
    verified_at: string | null
    created_at: string | null
    attachment_type: CatalogRef | null
    verified_by_user: Pick<UserEditData, 'id' | 'name'> | null
}

// ---------------------------------------------------------------------------
// StudentBackgroundResource
// ---------------------------------------------------------------------------

export interface StudentBackgroundData {
    id: number
    student_id: number
    previous_institution: string | null
    institution_type_id: number | null
    graduation_year: number | null
    previous_gpa: number | null
    repeated_grade: boolean
    repeated_grade_description: string | null
    transfer_reason_id: number | null
    has_prior_studies: boolean
    prior_studies_description: string | null
    digital_level_id: number | null
    mother_education_level_id: number | null
    father_education_level_id: number | null
    created_at: string | null
    updated_at: string | null
    institution_type: CatalogRef | null
    transfer_reason: CatalogRef | null
    digital_level: CatalogRef | null
    mother_education_level: CatalogRef | null
    father_education_level: CatalogRef | null
}

// ---------------------------------------------------------------------------
// StudentLanguageResource
// ---------------------------------------------------------------------------

export interface StudentLanguageData {
    id: number | null
    student_id: number
    language_id: number | null
    language_level_id: number | null
    is_mother_tongue: boolean
    language: CatalogRef | null
    language_level: CatalogRef | null
}

// ---------------------------------------------------------------------------
// FamilyProfileResource
// ---------------------------------------------------------------------------

export interface FamilyProfileData {
    id: number
    student_id: number
    guardian_marital_status_id: number | null
    children_count: number | null
    sibling_position: number | null
    sibling_count: number | null
    living_arrangement_id: number | null
    household_head_type_id: number | null
    household_head_name: string | null
    created_at: string | null
    updated_at: string | null
    guardian_marital_status: CatalogRef | null
    living_arrangement: CatalogRef | null
    household_head_type: CatalogRef | null
}

// ---------------------------------------------------------------------------
// SocioeconomicProfileResource
// ---------------------------------------------------------------------------

export interface SocioeconomicProfileData {
    id: number
    student_id: number
    income_range_id: number | null
    income_source_id: number | null
    household_earners: number | null
    receives_remittances: boolean
    remittance_country_id: number | null
    student_works: boolean
    employment_type_id: number | null
    weekly_work_hours: number | null
    has_scholarship: boolean
    scholarship_name: string | null
    has_institutional_benefit: boolean
    recorded_by: number | null
    study_date: string | null
    created_at: string | null
    updated_at: string | null
    income_range: CatalogRef | null
    income_source: CatalogRef | null
    remittance_country: CatalogRef | null
    employment_type: CatalogRef | null
    recorded_by_user: Pick<UserEditData, 'id' | 'name'> | null
}

// ---------------------------------------------------------------------------
// StudentBenefitResource
// ---------------------------------------------------------------------------

export interface StudentBenefitData {
    student_id: number
    benefit_id: number | null
    is_active: boolean
    since: string | null
    until: string | null
    benefit: CatalogRef | null
}

// ---------------------------------------------------------------------------
// HousingProfileResource
// ---------------------------------------------------------------------------

export interface HousingServiceData {
    id: number
    code: string
    name: string
    is_available: boolean
}

export interface HousingProfileData {
    id: number
    student_id: number
    housing_type_id: number | null
    tenure_type_id: number | null
    construction_material_id: number | null
    room_count: number | null
    bathroom_count: number | null
    household_members: number | null
    is_overcrowded: boolean
    commute_time_id: number | null
    transport_type_id: number | null
    created_at: string | null
    updated_at: string | null
    housing_type: CatalogRef | null
    tenure_type: CatalogRef | null
    construction_material: CatalogRef | null
    commute_time: CatalogRef | null
    transport_type: CatalogRef | null
    services: HousingServiceData[]
}

// ---------------------------------------------------------------------------
// GuardianProfileResource
// ---------------------------------------------------------------------------

export interface GuardianProfileData {
    id: number
    guardian_id: number
    occupation: string | null
    employer: string | null
    work_phone: string | null
    education_level_id: number | null
    marital_status_id: number | null
    created_at: string | null
    updated_at: string | null
    education_level: CatalogRef | null
    marital_status: CatalogRef | null
}

// ---------------------------------------------------------------------------
// StaffProfileResource
// ---------------------------------------------------------------------------

export interface StaffProfileData {
    id: number
    professor_id: number
    employee_code: string | null
    academic_title: string | null
    specialty: string | null
    contract_type_id: number | null
    dedication_type_id: number | null
    weekly_hour_load: number | null
    hire_date: string | null
    termination_date: string | null
    employment_status_id: number | null
    is_coordinator: boolean
    coordinated_department_id: number | null
    coordinator_since: string | null
    created_at: string | null
    updated_at: string | null
    contract_type: CatalogRef | null
    dedication_type: CatalogRef | null
    employment_status: CatalogRef | null
    coordinated_department: Pick<CatalogRef, 'id' | 'name'> | null
}

// ---------------------------------------------------------------------------
// Main Inertia props interface for the Edit page
// ---------------------------------------------------------------------------

export interface UserEditProps {
    user: UserEditData
    addresses: UserAddressData[]
    demographicProfile: DemographicProfileData | null
    healthProfile: HealthProfileData | null
    consent: UserConsentData | null
    documents: UserDocumentData[]
    student?: {
        id: number
        background: StudentBackgroundData | null
        languages: StudentLanguageData[]
        familyProfile: FamilyProfileData | null
        socioeconomicProfile: SocioeconomicProfileData | null
        benefits: StudentBenefitData[]
        housingProfile: HousingProfileData | null
    }
    professor?: {
        id: number
        staffProfile: StaffProfileData | null
    }
    guardian?: {
        id: number
        profile: GuardianProfileData | null
    }
}
