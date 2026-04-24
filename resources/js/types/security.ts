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
