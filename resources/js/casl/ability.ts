import { AbilityBuilder, createMongoAbility, type MongoAbility } from '@casl/ability';

export type AppActions = 'manage' | 'view' | 'create' | 'edit' | 'delete' | string;

export type AppSubjects =
    | 'all'
    | 'Academic'
    | 'People'
    | 'Enrollment'
    | 'Evaluation'
    | 'Infrastructure'
    | 'Resource'
    | 'User'
    | 'Settings'
    | string;

export type AppAbility = MongoAbility<[AppActions, AppSubjects]>;

export function buildRules(permissions: string[], roles: string[]) {
    const { can, build } = new AbilityBuilder<AppAbility>(createMongoAbility);

    if (roles.includes('Admin')) {
        can('manage', 'all');
    } else {
        permissions.forEach((p) => can(p, 'all'));
    }

    return build().rules;
}

export const ability = createMongoAbility<AppAbility>();
