<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Avatar from '@/components/base/Avatar.vue'
import Icon from '@/components/base/Icon.vue'
import Isotipo from '@/components/base/Isotipo.vue'
import { dashboard } from '@/routes'
import { index as rolesIndex } from '@/routes/security/roles'
import { index as usersIndex } from '@/routes/security/users'
import { index as coordinationsIndex } from '@/routes/security/coordinations'

const page = usePage()

const currentUrl = computed(() => page.url)

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
)

const navGroups = computed(() => {
    const groups = [
        {
            label: 'General',
            items: [
                { icon: 'grid', label: 'Dashboard', href: dashboardUrl.value },
            ],
        },
    ]

    if (
        page.props.auth?.permissions?.includes('roles.view') ||
        page.props.auth?.permissions?.includes('users.view') ||
        page.props.auth?.permissions?.includes('coordinations.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        const securityItems: { icon: string; label: string; href: string }[] = []

        if (
            page.props.auth?.permissions?.includes('roles.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            securityItems.push({ icon: 'shield', label: 'Roles', href: rolesIndex.url() })
        }

        if (
            page.props.auth?.permissions?.includes('users.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            securityItems.push({ icon: 'users', label: 'Usuarios', href: usersIndex.url() })
        }

        if (
            page.props.auth?.permissions?.includes('coordinations.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            securityItems.push({ icon: 'building-2', label: 'Coordinaciones', href: coordinationsIndex.url() })
        }

        if (securityItems.length) {
            groups.push({ label: 'Seguridad', items: securityItems })
        }
    }

    return groups
})

const user = computed(() => page.props.auth?.user)

const initials = computed(() => {
    const name = user.value?.name ?? ''
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase()
})

function isActive(href: string): boolean {
    return currentUrl.value === href || currentUrl.value.startsWith(href + '/')
}

function logout(): void {
    router.post('/logout')
}
</script>

<template>
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <Isotipo size="sm" />
            <span class="sidebar-wordmark">CACAO</span>
        </div>

        <nav style="flex:1;padding:8px;">
            <div v-for="group in navGroups" :key="group.label" class="sidebar-group">
                <div class="sidebar-group-label">{{ group.label }}</div>
                <Link
                    v-for="item in group.items"
                    :key="item.href"
                    :href="item.href"
                    :class="['sidebar-item', isActive(item.href) ? 'active' : '']"
                >
                    <Icon :name="item.icon" :size="16" />
                    {{ item.label }}
                </Link>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
                    <Avatar :initials="initials" size="sm" :color-preset="1" />
                    <span style="font-size:13px;font-weight:500;color:var(--sidebar-fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ user?.name }}
                    </span>
                </div>
                <button
                    class="btn btn-ghost btn-icon btn-sm"
                    style="color:var(--sidebar-muted);"
                    aria-label="Cerrar sesión"
                    @click="logout"
                >
                    <Icon name="logout" :size="15" />
                </button>
            </div>
        </div>
    </aside>
</template>
