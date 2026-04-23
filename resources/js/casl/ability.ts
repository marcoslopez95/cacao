import { AbilityBuilder, createMongoAbility, type MongoAbility } from '@casl/ability';

export type AppActions = 'manage' | 'view' | 'create' | 'edit' | 'delete';

export type AppSubjects =
    | 'all'
    | 'Academic'
    | 'People'
    | 'Enrollment'
    | 'Evaluation'
    | 'Infrastructure'
    | 'Resource'
    | 'User'
    | 'Settings';

export type AppAbility = MongoAbility<[AppActions, AppSubjects]>;

export function buildRules(roles: string[]) {
    const { can, build } = new AbilityBuilder<AppAbility>(createMongoAbility);

    if (roles.includes('admin')) {
        can('manage', 'all');
    }

    if (roles.includes('professor')) {
        can('view', ['Academic', 'People', 'Enrollment']);
        can(['view', 'create', 'edit'], 'Evaluation');
        can(['view', 'create', 'edit', 'delete'], 'Resource');
    }

    if (roles.includes('student')) {
        can('view', ['Academic', 'Enrollment', 'Evaluation', 'Resource']);
        can('create', 'Evaluation');
    }

    if (roles.includes('guardian')) {
        can('view', ['Enrollment', 'Evaluation']);
    }

    return build().rules;
}

export const ability = createMongoAbility<AppAbility>();
