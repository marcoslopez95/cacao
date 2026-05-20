import type {
    EnrollmentProfessor,
    EnrollmentSubject,
    EnrollmentRules,
    EnrollmentSelections,
} from '@/types/enrollment'

const PROFS: Record<string, EnrollmentProfessor> = {
    ms: { id: 'ms', name: 'Marco Salas',  initials: 'MS' },
    ap: { id: 'ap', name: 'Ana Pérez',    initials: 'AP' },
    lm: { id: 'lm', name: 'Luis Méndez',  initials: 'LM' },
    dq: { id: 'dq', name: 'Diana Quiroz', initials: 'DQ' },
    ht: { id: 'ht', name: 'Hugo Torres',  initials: 'HT' },
    ir: { id: 'ir', name: 'Iris Reyes',   initials: 'IR' },
    rn: { id: 'rn', name: 'Por asignar',  initials: '··' },
}

export const ENROLLMENT_RULES: EnrollmentRules = {
    creditsMin: 12,
    creditsMax: 24,
    period: '2026-I',
    studentName: 'Camila Ríos',
    studentCode: 'EST-2023-0418',
    career: 'Ing. en Sistemas',
    trimester: '5to trimestre',
    deadline: '23 de mayo',
    daysLeft: 4,
}

export const ENROLLMENT_SUBJECTS: EnrollmentSubject[] = [
    {
        code: 'ALG-302', name: 'Algoritmos y Estructuras', credits: 4,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Diseño y análisis de algoritmos, complejidad asintótica, estructuras avanzadas.',
        sections: [
            { code: '3-A', professor: PROFS.lm, room: 'Aula 201', modality: 'Teórica', capacity: 35, enrolled: 32,
              slots: [{ day: 0, start: '07:00', end: '09:00' }, { day: 2, start: '07:00', end: '09:00' }] },
            { code: '3-B', professor: PROFS.ms, room: 'Aula 105', modality: 'Teórica', capacity: 35, enrolled: 35,
              slots: [{ day: 0, start: '09:00', end: '11:00' }, { day: 2, start: '09:00', end: '11:00' }] },
            { code: '3-C', professor: PROFS.ap, room: 'Aula 203', modality: 'Teórica', capacity: 35, enrolled: 18,
              slots: [{ day: 1, start: '13:00', end: '15:00' }, { day: 3, start: '13:00', end: '15:00' }] },
        ],
    },
    {
        code: 'BDA-301', name: 'Bases de Datos I', credits: 4,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Modelo relacional, SQL, normalización, transacciones e índices.',
        sections: [
            { code: '4-A', professor: PROFS.ms, room: 'Lab 312', modality: 'Mixta', capacity: 30, enrolled: 28,
              slots: [{ day: 1, start: '14:00', end: '17:00' }, { day: 3, start: '14:00', end: '17:00' }] },
            { code: '4-B', professor: PROFS.ir, room: 'Lab 314', modality: 'Mixta', capacity: 30, enrolled: 22,
              slots: [{ day: 0, start: '14:00', end: '17:00' }, { day: 2, start: '14:00', end: '17:00' }] },
        ],
    },
    {
        code: 'CAL-301', name: 'Cálculo III', credits: 5,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Cálculo vectorial, integrales múltiples, teoremas de Green y Stokes.',
        sections: [
            { code: '3-A', professor: PROFS.ap, room: 'Aula 105', modality: 'Teórica', capacity: 35, enrolled: 30,
              slots: [{ day: 0, start: '09:00', end: '11:00' }, { day: 2, start: '09:00', end: '11:00' }, { day: 4, start: '09:00', end: '10:00' }] },
            { code: '3-B', professor: PROFS.dq, room: 'Aula 220', modality: 'Teórica', capacity: 35, enrolled: 25,
              slots: [{ day: 1, start: '09:00', end: '12:00' }, { day: 3, start: '09:00', end: '11:00' }] },
        ],
    },
    {
        code: 'ING-303', name: 'Inglés Técnico III', credits: 2,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Lectura y redacción técnica en inglés para sistemas y desarrollo.',
        sections: [
            { code: '3-A', professor: PROFS.ir, room: 'Aula 302', modality: 'Teórica', capacity: 30, enrolled: 22,
              slots: [{ day: 2, start: '13:00', end: '15:00' }, { day: 4, start: '13:00', end: '15:00' }] },
            { code: '3-B', professor: PROFS.ir, room: 'Aula 304', modality: 'Teórica', capacity: 30, enrolled: 18,
              slots: [{ day: 1, start: '10:00', end: '12:00' }, { day: 3, start: '10:00', end: '12:00' }] },
        ],
    },
    {
        code: 'EST-302', name: 'Estadística Aplicada', credits: 4,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Inferencia, regresión, pruebas de hipótesis con ejemplos prácticos.',
        sections: [
            { code: '3-A', professor: PROFS.lm, room: 'Aula 215', modality: 'Teórica', capacity: 35, enrolled: 28,
              slots: [{ day: 1, start: '11:00', end: '13:00' }, { day: 3, start: '11:00', end: '13:00' }] },
            { code: '3-B', professor: PROFS.rn, room: 'Por definir', modality: 'Por definir', capacity: 35, enrolled: 0,
              noSchedule: true, slots: [] },
        ],
    },
    {
        code: 'UX-405', name: 'Diseño de Experiencia', credits: 3,
        type: 'electiva', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Investigación con usuarios, prototipado y pruebas de usabilidad.',
        sections: [
            { code: '5-A', professor: PROFS.ap, room: 'Aula 405', modality: 'Teórica', capacity: 30, enrolled: 24,
              slots: [{ day: 2, start: '10:00', end: '13:00' }] },
            { code: '5-B', professor: PROFS.ap, room: 'Aula 405', modality: 'Teórica', capacity: 30, enrolled: 30,
              slots: [{ day: 4, start: '14:00', end: '17:00' }] },
        ],
    },
    {
        code: 'RED-401', name: 'Redes y Comunicaciones', credits: 4,
        type: 'oblig', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Modelo OSI, TCP/IP, ruteo, switching y fundamentos de seguridad de red.',
        sections: [
            { code: '4-A', professor: PROFS.ms, room: 'Lab 314', modality: 'Práctica', capacity: 30, enrolled: 24,
              slots: [{ day: 1, start: '15:00', end: '17:00' }, { day: 3, start: '15:00', end: '17:00' }] },
            { code: '4-B', professor: PROFS.lm, room: 'Lab 314', modality: 'Práctica', capacity: 30, enrolled: 18,
              slots: [{ day: 0, start: '14:00', end: '17:00' }] },
        ],
    },
    {
        code: 'IA-501', name: 'Inteligencia Artificial', credits: 4,
        type: 'electiva', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Búsqueda, agentes, aprendizaje supervisado y no supervisado.',
        sections: [
            { code: '5-A', professor: PROFS.ht, room: 'Lab 315', modality: 'Mixta', capacity: 25, enrolled: 20,
              slots: [{ day: 4, start: '09:00', end: '11:00' }, { day: 5, start: '08:00', end: '10:00' }] },
        ],
    },
    {
        code: 'ETI-501', name: 'Ética Profesional', credits: 2,
        type: 'oblig', recommendedTrim: true, prereqsOk: true, completed: false,
        description: 'Deontología, casos prácticos y dilemas profesionales en tecnología.',
        sections: [
            { code: '5-A', professor: PROFS.ir, room: 'Aula 110', modality: 'Teórica', capacity: 40, enrolled: 32,
              slots: [{ day: 0, start: '16:00', end: '18:00' }] },
            { code: '5-B', professor: PROFS.ir, room: 'Aula 110', modality: 'Teórica', capacity: 40, enrolled: 25,
              slots: [{ day: 4, start: '16:00', end: '18:00' }] },
        ],
    },
    {
        code: 'INV-401', name: 'Investigación de Operaciones', credits: 4,
        type: 'electiva', recommendedTrim: false, prereqsOk: true, completed: false,
        description: 'Optimización lineal, redes y modelos de inventarios.',
        sections: [
            { code: '4-A', professor: PROFS.rn, room: 'Por definir', modality: 'Por definir', capacity: 30, enrolled: 0,
              noSchedule: true, slots: [] },
            { code: '4-B', professor: PROFS.dq, room: 'Aula 216', modality: 'Teórica', capacity: 30, enrolled: 15,
              slots: [{ day: 2, start: '14:00', end: '16:00' }, { day: 4, start: '14:00', end: '16:00' }] },
        ],
    },
    {
        code: 'ROB-401', name: 'Robótica', credits: 3,
        type: 'electiva', recommendedTrim: false, prereqsOk: false, completed: false,
        description: 'Cinemática, sensado y control de robots móviles.',
        sections: [
            { code: '4-A', professor: PROFS.ms, room: 'Lab 512', modality: 'Laboratorio', capacity: 20, enrolled: 12,
              slots: [{ day: 5, start: '08:00', end: '12:00' }] },
        ],
    },
    {
        code: 'PRG-202', name: 'Programación II', credits: 4,
        type: 'oblig', recommendedTrim: false, prereqsOk: true, completed: true,
        description: 'POO, manejo de memoria, patrones básicos de diseño.',
        sections: [],
    },
]

export const ENROLLMENT_INITIAL_SELECTIONS: EnrollmentSelections = {
    'ALG-302': 0,
    'CAL-301': 1,
    'ING-303': 0,
    'INV-401': 0,
}
