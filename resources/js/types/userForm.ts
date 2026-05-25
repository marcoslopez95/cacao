// ============================================================
// CACAO · User form data interfaces
// ============================================================

export interface AddressItem {
    __id: number
    country?: string
    state?: string
    muni?: string
    parish?: string
    zone?: string
    line1?: string
    line2?: string
    primary?: boolean
}

export interface LanguageItem {
    __id: number
    lang?: string
    level?: string
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
    benefit?: string
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
    docType?: string
    docNumber?: string
    birthDate?: string
    gender?: string
    nationality?: string
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
    birthState?: string
    birthCountry?: string
    indigenous?: boolean
    indigenousComm?: string
    nativeLang?: string
    returnedMigrant?: boolean
    returnFrom?: string
    religion?: string
    sport?: boolean
    sportName?: string
    culture?: string

    // S5 — Salud
    bloodType?: string
    weight?: string
    height?: string
    disability?: boolean
    disabilityType?: string
    disabilityDesc?: string
    specialNeeds?: boolean
    specialNeedsDesc?: string
    chronic?: string
    medication?: string
    allergies?: string
    insurance?: boolean
    insuranceType?: string
    emergencyName?: string
    emergencyPhone?: string
    emergencyDial?: string
    emergencyRel?: string

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
    prevInstitutionType?: string
    gradYear?: string
    prevGpa?: string
    transferReason?: string
    digitalLevel?: string
    repeated?: boolean
    repeatedDesc?: string
    priorUni?: boolean
    priorUniDesc?: string
    motherEdu?: string
    fatherEdu?: string

    // S10 — Idiomas (repetible, student)
    languages?: LanguageItem[]

    // S11 — Perfil familiar (student)
    repMarital?: string
    repChildren?: string
    siblings?: string
    siblingPos?: string
    living?: string
    householdHead?: string
    householdHeadName?: string

    // S12 — Perfil socioeconómico (student)
    incomeRange?: string
    incomeSource?: string
    contributors?: string
    socioDate?: string
    remit?: boolean
    remitFrom?: string
    studentWorks?: boolean
    employmentType?: string
    weekHours?: string
    externalScholarship?: boolean
    scholarshipName?: string
    instBenefits?: boolean

    // S13 — Beneficios institucionales (repetible, student)
    benefits?: BenefitItem[]

    // S14 — Vivienda (student)
    housing?: string
    tenure?: string
    construction?: string
    rooms?: string
    bathrooms?: string
    peopleHome?: string
    commute?: string
    transport?: string
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
    guardianMarital?: string
    guardianEdu?: string

    // S17 — Perfil del personal (professor)
    empCode?: string
    degree?: string
    specialty?: string
    contract?: string
    dedication?: string
    weeklyHours?: string
    hireDate?: string
    endDate?: string
    emplStatus?: string
    isCoord?: boolean
    coordDept?: string
    coordSince?: string
}
