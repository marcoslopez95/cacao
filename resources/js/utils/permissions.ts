export function groupPermissions(permissions: string[]): Record<string, string[]> {
    return permissions.reduce(
        (groups, permission) => {
            const prefix = permission.split('.')[0];

            if (!groups[prefix]) {
                groups[prefix] = [];
            }

            groups[prefix].push(permission);

            return groups;
        },
        {} as Record<string, string[]>,
    );
}

export function permissionGroupLabel(group: string): string {
    return group.charAt(0).toUpperCase() + group.slice(1);
}
