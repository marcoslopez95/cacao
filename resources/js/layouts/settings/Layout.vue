<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { index as teams } from '@/routes/teams';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Teams',
        href: teams(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div style="padding:1.5rem 1rem;">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div style="display:flex;flex-direction:column;gap:3rem;">
            <aside style="width:100%;max-width:36rem;">
                <nav
                    style="display:flex;flex-direction:column;gap:0.25rem;"
                    aria-label="Settings"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        style="display:flex;align-items:center;padding:0.375rem 0.75rem;border-radius:0.375rem;font-size:var(--text-sm);text-decoration:none;color:var(--text-secondary);"
                        :style="isCurrentOrParentUrl(item.href) ? { background: 'var(--bg-subtle)', color: 'var(--text-primary)', fontWeight: '500' } : {}"
                    >
                        <component v-if="item.icon" :is="item.icon" style="margin-right:0.5rem;width:1rem;height:1rem;" />
                        {{ item.title }}
                    </Link>
                </nav>
            </aside>

            <div style="display:flex;flex-direction:column;gap:3rem;flex:1;max-width:36rem;">
                <slot />
            </div>
        </div>
    </div>
</template>
