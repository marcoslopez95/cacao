export const Role = {
    Admin: 'Admin',
    Professor: 'Profesor',
    Student: 'Estudiante',
    Coordinator: 'Coordinador de Area',
} as const;

export type RoleValue = typeof Role[keyof typeof Role];
