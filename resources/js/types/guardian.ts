import type { PaginationMeta, PaginationLink } from './pagination'

export type GuardianListItem = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    students_count: number;
};

export type GuardianStudentRow = {
    id: number;
    name: string;
    email: string;
    educational_level: string | null;
    kinship: string | null;
    primary: boolean;
    emergency_contact: boolean;
};

export type GuardianShowData = {
    id: number;
    user_id: number;
    name: string;
    email: string;
    students: GuardianStudentRow[];
};

export interface GuardianCollection {
    data: GuardianListItem[];
    meta: PaginationMeta;
    links: PaginationLink[];
}
