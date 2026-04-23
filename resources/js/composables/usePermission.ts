import { useAbility } from '@casl/vue';
import type { AppAbility } from '@/casl/ability';

export function usePermission() {
    const { can, cannot } = useAbility<AppAbility>();

    return {
        can: (permission: string) => can(permission, 'all'),
        cannot: (permission: string) => cannot(permission, 'all'),
    };
}
