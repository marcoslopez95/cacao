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
