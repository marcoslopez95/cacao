<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Avatar from '@/components/UI/AppAvatar.vue'
import Icon from '@/components/UI/AppIcon.vue'
import Isotipo from '@/components/UI/AppIsotipo.vue'
import { dashboard } from '@/routes'
import { index as rolesIndex } from '@/routes/security/roles'
import { index as usersIndex } from '@/routes/security/users'
import { index as coordinationsIndex } from '@/routes/security/coordinations'
import { index as careerCategoriesIndex } from '@/routes/academic/career-categories'
import { index as careersIndex } from '@/routes/academic/careers'
import { index as buildingsIndex } from '@/routes/infrastructure/buildings'
import { index as classroomsIndex } from '@/routes/infrastructure/classrooms'
import { index as periodsIndex } from '@/routes/scheduling/periods'
import { edit as profileEdit } from '@/routes/profile'

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
            securityItems.push({ icon: 'building', label: 'Coordinaciones', href: coordinationsIndex.url() })
        }

        if (securityItems.length) {
            groups.push({ label: 'Seguridad', items: securityItems })
        }
    }

    if (
        page.props.auth?.permissions?.includes('career-categories.view') ||
        page.props.auth?.permissions?.includes('careers.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        const academicItems: { icon: string; label: string; href: string }[] = []

        if (
            page.props.auth?.permissions?.includes('career-categories.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            academicItems.push({ icon: 'folder', label: 'Categorías', href: careerCategoriesIndex.url() })
        }

        if (
            page.props.auth?.permissions?.includes('careers.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            academicItems.push({ icon: 'book', label: 'Carreras', href: careersIndex.url() })
        }

        if (academicItems.length) {
            groups.push({ label: 'Académico', items: academicItems })
        }
    }

    if (
        page.props.auth?.permissions?.includes('buildings.view') ||
        page.props.auth?.permissions?.includes('classrooms.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        const infraItems: { icon: string; label: string; href: string }[] = []

        if (
            page.props.auth?.permissions?.includes('buildings.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            infraItems.push({ icon: 'building', label: 'Edificios', href: buildingsIndex.url() })
        }

        if (
            page.props.auth?.permissions?.includes('classrooms.view') ||
            page.props.auth?.roles?.includes('Admin')
        ) {
            infraItems.push({ icon: 'grid', label: 'Aulas', href: classroomsIndex.url() })
        }

        if (infraItems.length) {
            groups.push({ label: 'Infraestructura', items: infraItems })
        }
    }

    if (
        page.props.auth?.permissions?.includes('periods.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        groups.push({
            label: 'Horarios',
            items: [
                { icon: 'calendar', label: 'Períodos', href: periodsIndex.url() },
            ],
        })
    }

    groups.push({
        label: 'Mi cuenta',
        items: [
            { icon: 'settings', label: 'Configuración', href: profileEdit.url() },
        ],
    })

    return groups
})

const user = computed(() => page.props.auth?.user)

const initials = computed(() => {
    const name = user.value?.name ?? ''
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase()
})

function isActive(href: string): boolean {
    if (href === profileEdit.url()) {
        return currentUrl.value.startsWith('/settings/')
    }
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
