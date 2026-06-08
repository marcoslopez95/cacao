<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { index } from '@/routes/security/users'
import type { RoleKey } from '@/types/userFormCatalogs'
import { UF_ROLES, ROLE_SECTION_COUNT } from '@/types/userFormCatalogs'

const emit = defineEmits<{ pick: [RoleKey] }>()

const selectedRole = ref<RoleKey | ''>('')

function select(key: RoleKey): void {
    selectedRole.value = key
}

function confirm(): void {
    if (selectedRole.value) {
emit('pick', selectedRole.value)
}
}

const ROLE_KEYS: RoleKey[] = ['admin', 'student', 'professor', 'guardian']
</script>

<template>
    <div class="uf-role-screen">
        <div class="uf-role-screen-head">
            <p class="uf-step-hint">Paso 1 de 2 · Elegir rol</p>
            <h1>¿Qué tipo de usuario vas a crear?</h1>
            <p class="uf-step-sub">El rol determina qué secciones del perfil se habilitan y qué acciones puede realizar en el sistema.</p>
        </div>

        <div class="uf-role-grid">
            <button
                v-for="key in ROLE_KEYS"
                :key="key"
                type="button"
                class="uf-role-card"
                :class="{ selected: selectedRole === key }"
                @click="select(key)"
                @dblclick="emit('pick', key)"
            >
                <span class="uf-role-check">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                </span>

                <span class="uf-role-icon">
                    <!-- admin -->
                    <svg v-if="key === 'admin'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <!-- student -->
                    <svg v-else-if="key === 'student'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                        <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                    </svg>
                    <!-- professor -->
                    <svg v-else-if="key === 'professor'" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <path d="M8 21h8M12 17v4"/>
                        <path d="M7 8l3 3 4-4"/>
                    </svg>
                    <!-- guardian -->
                    <svg v-else width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </span>

                <h3>{{ UF_ROLES[key].label }}</h3>
                <p>{{ UF_ROLES[key].description }}</p>
                <div class="uf-role-count">{{ ROLE_SECTION_COUNT[key] }} secciones</div>
            </button>
        </div>

        <div class="uf-role-actions">
            <button type="button" class="uf-btn ghost" @click="router.visit(index().url)">
                Cancelar
            </button>
            <button
                type="button"
                class="uf-btn primary"
                :disabled="!selectedRole"
                @click="confirm"
            >
                Continuar con {{ selectedRole ? UF_ROLES[selectedRole as RoleKey].label : '…' }}
            </button>
        </div>
    </div>
</template>
