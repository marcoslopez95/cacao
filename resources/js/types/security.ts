import type { PaginationMeta, PaginationLink } from './pagination'

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

export interface UserCollection {
    data: UserRow[];
    meta: PaginationMeta;
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

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

export interface CoordinationCollection {
    data: CoordinationRow[];
    meta: PaginationMeta;
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}
