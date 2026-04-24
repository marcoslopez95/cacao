import { abilitiesPlugin } from '@casl/vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ability, buildRules } from '@/casl/ability';
import { initializeTheme } from '@/composables/useAppearance';
import { i18n } from '@/i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'CACAO';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
            case name.startsWith('teams/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        const auth = (props.initialPage?.props?.auth as { roles?: string[]; permissions?: string[] }) ?? {};
        ability.update(buildRules(auth.permissions ?? [], auth.roles ?? []));

        router.on('success', (event) => {
            const updatedAuth = (event.detail.page.props.auth as { roles?: string[]; permissions?: string[] }) ?? {};
            ability.update(buildRules(updatedAuth.permissions ?? [], updatedAuth.roles ?? []));
        });

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .use(abilitiesPlugin, ability, { useGlobalProperties: true })
            .mount(el);
    },
    progress: {
        color: '#C8521A',
    },
});

initializeTheme();
initializeFlashToast();
