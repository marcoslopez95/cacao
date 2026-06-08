import { vi } from 'vitest';
import { ref } from 'vue';

// Mock @inertiajs/vue3
vi.mock('@inertiajs/vue3', () => ({
    useForm: vi.fn((initialValues: Record<string, unknown> = {}) => ({
        ...initialValues,
        errors: {},
        processing: false,
        wasSuccessful: false,
        recentlySuccessful: false,
        isDirty: false,
        post: vi.fn(),
        put: vi.fn(),
        patch: vi.fn(),
        delete: vi.fn(),
        get: vi.fn(),
        submit: vi.fn(),
        reset: vi.fn(),
        clearErrors: vi.fn(),
        setError: vi.fn(),
        transform: vi.fn(),
    })),
    usePage: vi.fn(() => ({
        props: {
            auth: {
                user: {
                    id: 1,
                    name: 'Test User',
                    email: 'test@example.com',
                    roles: ['admin'],
                },
            },
        },
        url: '/',
        component: 'Test',
        version: '1',
    })),
    router: {
        get: vi.fn(() => undefined),
        post: vi.fn(() => undefined),
        put: vi.fn(() => undefined),
        patch: vi.fn(() => undefined),
        delete: vi.fn(() => undefined),
        visit: vi.fn(),
        reload: vi.fn(),
        cancelAll: vi.fn(),
        on: vi.fn(),
    },
    Link: { template: '<a><slot /></a>' },
    Head: { template: '<div></div>', props: ['title'] },
}));

// Mock @casl/vue
vi.mock('@casl/vue', () => ({
    abilitiesPlugin: { install: vi.fn() },
    useAbility: vi.fn(() => ({ can: vi.fn(() => true) })),
}));

// Mock vue-i18n
vi.mock('vue-i18n', () => ({
    useI18n: vi.fn(() => ({
        t: (key: string) => key,
        locale: ref('es'),
        availableLocales: ['es', 'en'],
    })),
    createI18n: vi.fn(),
}));
