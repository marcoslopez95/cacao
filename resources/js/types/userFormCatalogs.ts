// ============================================================
// CACAO · User form catalogs — port of user-form-catalogs.jsx
// ============================================================

export type RoleKey = 'admin' | 'student' | 'professor' | 'guardian'

export interface RoleMeta {
    key: RoleKey
    label: string
    description: string
    icon: string
}

export interface TabDef {
    key: string
    label: string
    sections: number[]
}

export interface SectionMeta {
    title: string
    sub: string
    roles: string[]
    repeat?: boolean
    comfy?: boolean
    note?: string
}

export interface CountryOption {
    key: string
    label: string
    dial: string
}

export interface SelectOption {
    key: string
    label: string
}

// ─── Document types ──────────────────────────────────────────
export const UF_DOC_TYPES: SelectOption[] = [
    { key: 'V', label: 'V-CI' },
    { key: 'E', label: 'E-CI' },
    { key: 'P', label: 'PAS' },
    { key: 'J', label: 'J-CI' },
]

// ─── Genders ─────────────────────────────────────────────────
export const UF_GENDERS: SelectOption[] = [
    { key: 'f', label: 'Femenino' },
    { key: 'm', label: 'Masculino' },
    { key: 'o', label: 'Otro' },
    { key: 'na', label: 'Prefiero no decir' },
]

// ─── Countries ───────────────────────────────────────────────
export const UF_COUNTRIES: CountryOption[] = [
    { key: 've', label: 'Venezuela', dial: '+58' },
    { key: 'co', label: 'Colombia', dial: '+57' },
    { key: 'pe', label: 'Perú', dial: '+51' },
    { key: 'es', label: 'España', dial: '+34' },
    { key: 'us', label: 'Estados Unidos', dial: '+1' },
    { key: 'br', label: 'Brasil', dial: '+55' },
    { key: 'ec', label: 'Ecuador', dial: '+593' },
    { key: 'cl', label: 'Chile', dial: '+56' },
    { key: 'ar', label: 'Argentina', dial: '+54' },
]

// ─── Venezuelan geography ─────────────────────────────────────
export const UF_STATES_VE: string[] = [
    'Distrito Capital', 'Miranda', 'Zulia', 'Carabobo', 'Aragua', 'Lara',
    'Anzoátegui', 'Bolívar', 'Mérida', 'Táchira', 'Trujillo', 'Falcón',
    'Portuguesa', 'Yaracuy', 'Cojedes', 'Guárico', 'Sucre', 'Monagas',
    'Nueva Esparta', 'Vargas', 'Apure', 'Barinas', 'Amazonas', 'Delta Amacuro',
]

export const UF_MUNICIPIOS_DTTO: string[] = [
    'Libertador', 'Chacao', 'Baruta', 'El Hatillo', 'Sucre',
]

export const UF_PARROQUIAS: string[] = [
    'Altagracia', 'Candelaria', 'Catedral', 'La Pastora', 'San Agustín',
]

export const UF_ZONES: string[] = [
    'Urbana', 'Rural', 'Periurbana', 'Indígena',
]

// ─── Cultural / demographic ───────────────────────────────────
export const UF_LANGUAGES: string[] = [
    'Español', 'Inglés', 'Portugués', 'Wayuunaiki', 'Pemón', 'Warao',
    'Francés', 'Italiano', 'Mandarín',
]

export const UF_RELIGIONS: string[] = [
    'Católica', 'Evangélica', 'Cristiana no denominacional', 'Judía',
    'Musulmana', 'Espiritualidad indígena', 'Otra', 'Ninguna',
]

// ─── Health ───────────────────────────────────────────────────
export const UF_BLOOD: string[] = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-']

export const UF_DISABILITY_TYPES: string[] = [
    'Motora', 'Visual', 'Auditiva', 'Intelectual', 'Psicosocial', 'Múltiple',
]

export const UF_INSURANCE: string[] = [
    'HCM público', 'HCM privado', 'Seguro educativo', 'Otro',
]

// ─── Documents ────────────────────────────────────────────────
export const UF_ATTACH_TYPES: string[] = [
    'Cédula', 'Partida de nacimiento', 'Notas certificadas', 'Título de bachiller',
    'Constancia de residencia', 'RIF', 'Foto carnet', 'Constancia médica',
]

// ─── Academic ─────────────────────────────────────────────────
export const UF_ACADEMIC_STATUSES: string[] = [
    'Activo', 'Egresado', 'Retirado', 'Inscrito', 'Suspendido', 'Graduado',
]

export const UF_STUDY_MODALITIES: string[] = [
    'Presencial', 'Semipresencial', 'A distancia', 'Híbrida',
]

export const UF_SHIFTS: string[] = ['Mañana', 'Tarde', 'Noche', 'Fin de semana']

export const UF_ADMISSION: string[] = [
    'Nuevo ingreso', 'Traslado', 'Reingreso', 'Equivalencia',
]

export const UF_GRADES: string[] = ['1°', '2°', '3°', '4°', '5°', '6°']

export const UF_INSTITUTION_TYPES: string[] = [
    'Pública nacional', 'Pública estadal', 'Privada', 'Subvencionada', 'Comunitaria',
]

export const UF_TRANSFER_REASONS: string[] = [
    'Cambio de domicilio', 'Razones económicas', 'Académicas', 'Personales', 'Otra',
]

export const UF_DIGITAL_LEVELS: string[] = [
    'Básico', 'Intermedio', 'Avanzado', 'Experto',
]

export const UF_EDU_LEVELS: string[] = [
    'Sin escolaridad', 'Primaria incompleta', 'Primaria completa',
    'Bachillerato incompleto', 'Bachillerato completo', 'Técnico',
    'Universitario incompleto', 'Universitario completo', 'Postgrado',
]

export const UF_LANG_LEVELS: string[] = [
    'Básico (A1-A2)', 'Intermedio (B1-B2)', 'Avanzado (C1)', 'Nativo / C2',
]

// ─── Family ───────────────────────────────────────────────────
export const UF_MARITAL: string[] = [
    'Soltero/a', 'Casado/a', 'Unión estable', 'Divorciado/a', 'Viudo/a', 'Separado/a',
]

export const UF_LIVING: string[] = [
    'Con ambos padres', 'Solo con madre', 'Solo con padre',
    'Con familiares', 'Independiente', 'Internado',
]

export const UF_HOUSEHOLD_HEAD: string[] = [
    'Padre', 'Madre', 'Ambos padres', 'Abuelo/a', 'Hermano/a mayor',
    'Otro familiar', 'El estudiante mismo',
]

// ─── Socioeconomic ────────────────────────────────────────────
export const UF_INCOME_RANGES: string[] = [
    '0 – 100 USD', '100 – 300 USD', '300 – 600 USD',
    '600 – 1.000 USD', '1.000+ USD', 'Prefiero no decir',
]

export const UF_INCOME_SOURCES: string[] = [
    'Salario formal', 'Trabajo independiente', 'Pensión / jubilación',
    'Remesas', 'Comercio', 'Otra',
]

export const UF_EMPLOYMENT_TYPES: string[] = [
    'Tiempo completo', 'Medio tiempo', 'Por horas', 'Freelance',
    'Familiar no remunerado',
]

// ─── Benefits ─────────────────────────────────────────────────
export const UF_BENEFITS: string[] = [
    'Beca completa', 'Beca parcial', 'Exoneración de matrícula',
    'Comedor', 'Transporte', 'Material didáctico', 'Apoyo psicopedagógico',
]

// ─── Housing ──────────────────────────────────────────────────
export const UF_HOUSING: string[] = [
    'Casa', 'Apartamento', 'Habitación', 'Anexo', 'Rancho / vivienda informal',
]

export const UF_TENURE: string[] = [
    'Propia', 'Alquilada', 'Prestada', 'Familiar', 'Ocupada',
]

export const UF_CONSTRUCTION: string[] = [
    'Bloque y cemento', 'Adobe', 'Madera', 'Zinc / lámina', 'Mixta',
]

export const UF_COMMUTE: string[] = [
    '< 15 min', '15 – 30 min', '30 – 60 min', '1 – 2 h', '> 2 h',
]

export const UF_TRANSPORT: string[] = [
    'A pie', 'Bicicleta', 'Transporte público', 'Moto',
    'Vehículo propio', 'Vehículo compartido',
]

// ─── Guardians ────────────────────────────────────────────────
export const UF_KINSHIP: string[] = [
    'Madre', 'Padre', 'Madrastra', 'Padrastro', 'Abuelo/a',
    'Tío/a', 'Hermano/a mayor', 'Tutor legal', 'Otro familiar',
]

// ─── Professor ────────────────────────────────────────────────
export const UF_CONTRACT: string[] = [
    'Fijo', 'Determinado', 'Por horas', 'Honorarios', 'Suplente',
]

export const UF_DEDICATION: string[] = [
    'Tiempo completo', 'Tiempo parcial', 'Medio tiempo', 'Por horas',
]

export const UF_EMPL_STATUS: string[] = [
    'Activo', 'En licencia', 'Suspendido', 'Jubilado', 'Retirado',
]

export const UF_DEPARTMENTS: string[] = [
    'Matemáticas', 'Ciencias Naturales', 'Ciencias Sociales',
    'Lengua y Literatura', 'Idiomas', 'Educación Física',
    'Arte y Cultura', 'Tecnología',
]

// ─── Roles ────────────────────────────────────────────────────
export const UF_ROLES: Record<RoleKey, RoleMeta> = {
    admin: {
        key: 'admin',
        label: 'Administrador',
        description: 'Acceso completo al sistema y a los datos de toda la institución.',
        icon: 'admin',
    },
    student: {
        key: 'student',
        label: 'Estudiante',
        description: 'Alumno inscrito en algún nivel educativo. Es el rol con perfil más completo.',
        icon: 'student',
    },
    professor: {
        key: 'professor',
        label: 'Profesor',
        description: 'Personal docente y administrativo: datos laborales y de contrato.',
        icon: 'professor',
    },
    guardian: {
        key: 'guardian',
        label: 'Representante',
        description: 'Padre, madre o tutor legal de uno o varios estudiantes.',
        icon: 'guardian',
    },
}

export const ROLE_SECTION_COUNT: Record<RoleKey, number> = {
    admin: 7,
    student: 15,
    professor: 8,
    guardian: 8,
}

// ─── Tabs per role ────────────────────────────────────────────
export const UF_TABS: Record<RoleKey, TabDef[]> = {
    admin: [
        { key: 'identity', label: 'Identidad', sections: [1, 2, 3, 4] },
        { key: 'health', label: 'Salud', sections: [5] },
        { key: 'docs', label: 'Documentos', sections: [7, 6] },
    ],
    student: [
        { key: 'identity', label: 'Identidad', sections: [1, 2, 3, 4] },
        { key: 'health', label: 'Salud', sections: [5] },
        { key: 'academic', label: 'Académico', sections: [8, 9, 10] },
        { key: 'family', label: 'Familia', sections: [11, 15] },
        { key: 'socioecon', label: 'Socioeconómico', sections: [12, 13, 14] },
        { key: 'docs', label: 'Documentos', sections: [7, 6] },
    ],
    professor: [
        { key: 'identity', label: 'Identidad', sections: [1, 2, 3, 4] },
        { key: 'health', label: 'Salud', sections: [5] },
        { key: 'job', label: 'Profesional', sections: [17] },
        { key: 'docs', label: 'Documentos', sections: [7, 6] },
    ],
    guardian: [
        { key: 'identity', label: 'Identidad', sections: [1, 2, 3, 4] },
        { key: 'health', label: 'Salud', sections: [5] },
        { key: 'guard', label: 'Representante', sections: [16] },
        { key: 'docs', label: 'Documentos', sections: [7, 6] },
    ],
}

// ─── Section metadata ─────────────────────────────────────────
export const UF_SECTIONS: Record<number, SectionMeta> = {
    1:  { title: 'Identidad personal',        sub: 'Datos básicos del usuario.',                         roles: ['*'] },
    2:  { title: 'Credenciales de acceso',    sub: 'Correo y contraseña de inicio de sesión.',           roles: ['*'] },
    3:  { title: 'Dirección',                 sub: 'Domicilio actual. Podés agregar varias.',            roles: ['*'], repeat: true },
    4:  { title: 'Perfil demográfico',        sub: 'Nacimiento, lengua, cultura.',                       roles: ['*'] },
    5:  { title: 'Salud',                     sub: 'Información médica y contacto de emergencia.',       roles: ['*'], comfy: true },
    6:  { title: 'Consentimientos de datos',  sub: 'Permisos de tratamiento de datos personales.',       roles: ['*'], comfy: true },
    7:  { title: 'Documentos adjuntos',       sub: 'Archivos verificables: cédula, partida, notas…',    roles: ['*'], repeat: true },
    8:  { title: 'Perfil académico',          sub: 'Estatus, código y modalidad de estudio.',            roles: ['student'] },
    9:  { title: 'Antecedentes educativos',   sub: 'Institución previa y trayectoria.',                  roles: ['student'] },
    10: { title: 'Idiomas',                   sub: 'Lenguas que maneja el estudiante.',                  roles: ['student'], repeat: true },
    11: { title: 'Perfil familiar',           sub: 'Composición del hogar.',                             roles: ['student'] },
    12: { title: 'Perfil socioeconómico',     sub: 'Situación económica del grupo familiar.',            roles: ['student'] },
    13: { title: 'Beneficios institucionales',sub: 'Becas y apoyos otorgados por la institución.',       roles: ['student'], repeat: true },
    14: { title: 'Vivienda',                  sub: 'Tipo de vivienda y servicios disponibles.',          roles: ['student'] },
    15: { title: 'Representantes',            sub: 'Padres, madres o tutores legales.',                  roles: ['student'], repeat: true, note: 'Solo primaria / bachillerato' },
    16: { title: 'Perfil del representante',  sub: 'Datos laborales del representante.',                 roles: ['guardian'] },
    17: { title: 'Perfil del personal',       sub: 'Datos laborales, contrato y dedicación.',            roles: ['professor'] },
}
