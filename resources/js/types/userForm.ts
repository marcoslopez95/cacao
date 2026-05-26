// ============================================================
// CACAO · User form data interfaces
// ============================================================

export interface AddressItem {
    __id: number
    country_id?: number
    state_id?: number
    muni?: string
    parish?: string
    zone?: string
    line1?: string
    line2?: string
    primary?: boolean
}

export interface LanguageItem {
    __id: number
    language_id?: number
    language_level_id?: number
    mother?: boolean
}

export interface AttachmentItem {
    __id: number
    type?: string
    filename?: string
    verified?: boolean
    verifiedBy?: string | null
    verifiedAt?: string | null
}

export interface BenefitItem {
    __id: number
    benefit_id?: number
    active?: boolean
    start?: string
    end?: string
}

export interface GuardianItem {
    __id: number
    search?: string
    kinship?: string
    main?: boolean
    emergency?: boolean
    __main?: boolean
}

export interface UserFormData {
    // S1 — Identidad personal
    firstName?: string
    lastName?: string
    roles?: string[]
    docTypeId?: number
    docNumber?: string
    birthDate?: string
    genderId?: number
    nationalityId?: number
    phone1?: string
    phone1Dial?: string
    phone2?: string
    phone2Dial?: string
    profilePhotoUrl?: string

    // S2 — Credenciales de acceso
    email?: string
    password?: string
    passwordMode?: 'link' | 'manual' | 'random'

    // S3 — Dirección (repetible)
    addresses?: AddressItem[]

    // S4 — Perfil demográfico
    birthCity?: string
    /** @deprecated use birthStateId */
    birthState?: string
    /** @deprecated use birthCountryId */
    birthCountry?: string
    indigenous?: boolean
    indigenousComm?: string
    /** @deprecated use nativeLangId */
    nativeLang?: string
    returnedMigrant?: boolean
    /** @deprecated use previousCountryId */
    returnFrom?: string
    /** @deprecated use religionId */
    religion?: string
    sport?: boolean
    sportName?: string
    culture?: string
    // S4 — FK ids (replaces deprecated string fields above)
    birthStateId?: number
    birthCountryId?: number
    nativeLangId?: number
    previousCountryId?: number
    religionId?: number

    // S5 — Salud
    /** @deprecated use bloodTypeId */
    bloodType?: string
    weight?: string
    height?: string
    disability?: boolean
    /** @deprecated use disabilityTypeId */
    disabilityType?: string
    disabilityDesc?: string
    specialNeeds?: boolean
    specialNeedsDesc?: string
    chronic?: string
    medication?: string
    allergies?: string
    insurance?: boolean
    /** @deprecated use insuranceTypeId */
    insuranceType?: string
    emergencyName?: string
    emergencyPhone?: string
    emergencyDial?: string
    emergencyRel?: string
    // S5 — FK ids (replaces deprecated string fields above)
    bloodTypeId?: number
    disabilityTypeId?: number
    insuranceTypeId?: number

    // S6 — Consentimientos
    consent_data?: boolean
    consent_image?: boolean
    consent_whatsapp?: boolean
    consent_email?: boolean

    // S7 — Documentos adjuntos (repetible)
    attachments?: AttachmentItem[]

    // S8 — Perfil académico (student)
    studentCode?: string
    academicStatus?: string
    enrollDate?: string
    modality?: string
    shift?: string
    admission?: string
    gpa?: string
    grade?: string

    // S9 — Antecedentes educativos (student)
    prevInstitution?: string
    /** @deprecated use prevInstitutionTypeId */
    prevInstitutionType?: string
    gradYear?: string
    prevGpa?: string
    /** @deprecated use transferReasonId */
    transferReason?: string
    /** @deprecated use digitalLevelId */
    digitalLevel?: string
    repeated?: boolean
    repeatedDesc?: string
    priorUni?: boolean
    priorUniDesc?: string
    /** @deprecated use motherEduId */
    motherEdu?: string
    /** @deprecated use fatherEduId */
    fatherEdu?: string
    // S9 — FK ids (replaces deprecated string fields above)
    prevInstitutionTypeId?: number
    transferReasonId?: number
    digitalLevelId?: number
    motherEduId?: number
    fatherEduId?: number

    // S10 — Idiomas (repetible, student)
    languages?: LanguageItem[]

    // S11 — Perfil familiar (student)
    /** @deprecated use repMaritalId */
    repMarital?: string
    repChildren?: string
    siblings?: string
    siblingPos?: string
    /** @deprecated use livingId */
    living?: string
    /** @deprecated use householdHeadId */
    householdHead?: string
    householdHeadName?: string
    // S11 — FK ids
    repMaritalId?: number
    livingId?: number
    householdHeadId?: number

    // S12 — Perfil socioeconómico (student)
    /** @deprecated use incomeRangeId */
    incomeRange?: string
    /** @deprecated use incomeSourceId */
    incomeSource?: string
    contributors?: string
    socioDate?: string
    remit?: boolean
    /** @deprecated use remitFromId */
    remitFrom?: string
    studentWorks?: boolean
    /** @deprecated use employmentTypeId */
    employmentType?: string
    weekHours?: string
    externalScholarship?: boolean
    scholarshipName?: string
    instBenefits?: boolean
    // S12 — FK ids
    incomeRangeId?: number
    incomeSourceId?: number
    remitFromId?: number
    employmentTypeId?: number

    // S13 — Beneficios institucionales (repetible, student)
    benefits?: BenefitItem[]

    // S14 — Vivienda (student)
    /** @deprecated use housingId */
    housing?: string
    /** @deprecated use tenureId */
    tenure?: string
    /** @deprecated use constructionId */
    construction?: string
    rooms?: string
    bathrooms?: string
    peopleHome?: string
    /** @deprecated use commuteId */
    commute?: string
    /** @deprecated use transportId */
    transport?: string
    // S14 — FK ids
    housingId?: number
    tenureId?: number
    constructionId?: number
    commuteId?: number
    transportId?: number
    svc_water?: boolean
    svc_elec?: boolean
    svc_gas?: boolean
    svc_inet?: boolean
    otherServices?: string

    // S15 — Representantes (repetible, student)
    guardians?: GuardianItem[]

    // S16 — Perfil del representante (guardian)
    occupation?: string
    employer?: string
    workPhone?: string
    workDial?: string
    /** @deprecated use guardianMaritalId */
    guardianMarital?: string
    /** @deprecated use guardianEduId */
    guardianEdu?: string
    // S16 — FK ids (replaces deprecated string fields above)
    guardianMaritalId?: number
    guardianEduId?: number

    // S17 — Perfil del personal (professor)
    empCode?: string
    degree?: string
    specialty?: string
    /** @deprecated use contractTypeId */
    contract?: string
    /** @deprecated use dedicationTypeId */
    dedication?: string
    weeklyHours?: string
    hireDate?: string
    endDate?: string
    /** @deprecated use emplStatusId */
    emplStatus?: string
    isCoord?: boolean
    /** @deprecated use coordDeptId */
    coordDept?: string
    coordSince?: string
    // S17 — FK ids (replaces deprecated string fields above)
    contractTypeId?: number
    dedicationTypeId?: number
    emplStatusId?: number
    coordDeptId?: number
}
