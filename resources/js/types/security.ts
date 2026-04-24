export type Permission = {
    id: number;
    name: string;
    guard_name: string;
};

export type Role = {
    id: number;
    name: string;
    isAdmin: boolean;
    usersCount: number;
    permissions: string[];
};

export type UserRow = {
    id: number;
    name: string;
    email: string;
    active: boolean;
    roles: string[];
    created_at: string;
};

export type UserPaginator = {
    data: UserRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

export type CoordinationRow = {
    id: number;
    name: string;
    type: 'career' | 'grade' | 'academic';
    education_level: 'university' | 'secondary';
    secondary_type: 'media_general' | 'bachillerato' | null;
    career_id: number | null;
    grade_year: number | null;
    active: boolean;
    current_coordinator: { id: number; name: string } | null;
};

export type CoordinationAssignment = {
    id: number;
    user: { id: number; name: string };
    assigned_by: { id: number; name: string };
    assigned_at: string;
    ended_at: string | null;
};

export type CoordinationPaginator = {
    data: CoordinationRow[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
