<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import CacaoAvatar from '@/components/UI/AppAvatar.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index as professorDashboard } from '@/actions/App/Http/Controllers/Professor/DashboardController';
import { index as studentDashboard } from '@/actions/App/Http/Controllers/Student/DashboardController';
import { index as guardianDashboard } from '@/actions/App/Http/Controllers/Guardian/DashboardController';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);

const dashboardUrl = computed(() => {
    const roles = page.props.auth?.roles ?? []
    if (roles.includes('Admin') && page.props.currentTeam) {
        return dashboard(page.props.currentTeam.slug).url
    }
    if (roles.some((r: string) => ['Profesor', 'Coordinador de Area'].includes(r))) return professorDashboard.url()
    if (roles.includes('Estudiante')) return studentDashboard.url()
    if (roles.includes('Representante')) return guardianDashboard.url()
    return '/'
});

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboardUrl.value,
    },
]);

const rightNavItems: NavItem[] = [];
</script>

<template>
    <div>
        <div style="border-bottom:1px solid var(--border);">
            <div style="margin:0 auto;display:flex;height:4rem;align-items:center;padding:0 1rem;max-width:80rem;">
                <Link :href="dashboardUrl" style="display:flex;align-items:center;gap:0.5rem;">
                    <AppLogo />
                </Link>

                <div style="display:flex;align-items:center;gap:0.5rem;margin-left:auto;">
                    <CacaoAvatar
                        :initials="getInitials(auth.user?.name)"
                        :src="auth.user?.avatar ?? undefined"
                        size="sm"
                    />
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            style="display:flex;width:100%;border-bottom:1px solid var(--border);"
        >
            <div
                style="margin:0 auto;display:flex;height:3rem;width:100%;align-items:center;justify-content:flex-start;padding:0 1rem;color:var(--text-muted);max-width:80rem;"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </div>
        </div>
    </div>
</template>
