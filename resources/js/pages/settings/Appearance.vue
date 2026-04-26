<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Icon from '@/components/UI/AppIcon.vue';
import SettingsTabs from '@/components/settings/SettingsTabs.vue';
import { useAppearance } from '@/composables/useAppearance';
import { edit } from '@/routes/appearance';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Configuración', href: edit() }],
    },
});

const { appearance, updateAppearance } = useAppearance();

const themes = [
    { value: 'light' as const, label: 'Claro', icon: 'sun' },
    { value: 'dark' as const, label: 'Oscuro', icon: 'moon' },
    { value: 'system' as const, label: 'Sistema', icon: 'settings' },
];
</script>

<template>
    <Head title="Apariencia · Configuración" />
    <h1 class="sr-only">Configuración — Apariencia</h1>

    <SettingsTabs />

    <div class="sp-section">
        <div class="sp-section-head">
            <h2>Tema de la interfaz</h2>
            <p>Elige cómo quieres ver CACAO. La preferencia se aplica en todos tus dispositivos.</p>
        </div>
        <div class="sp-section-body">
            <div class="sp-themes" role="radiogroup" aria-label="Tema de la interfaz">
                <button
                    v-for="theme in themes"
                    :key="theme.value"
                    type="button"
                    class="sp-theme"
                    :class="{ active: appearance === theme.value }"
                    :data-variant="theme.value"
                    role="radio"
                    :aria-checked="appearance === theme.value"
                    @click="updateAppearance(theme.value)"
                >
                    <span class="sp-theme-check">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6 9 17l-5-5"/>
                        </svg>
                    </span>
                    <div class="sp-theme-preview">
                        <div class="side">
                            <div class="ln"/>
                            <div class="ln active"/>
                            <div class="ln"/>
                            <div class="ln"/>
                        </div>
                        <div class="body">
                            <div class="bar short"/>
                            <div class="bar"/>
                            <div class="card"/>
                        </div>
                    </div>
                    <div class="sp-theme-label">
                        <Icon :name="theme.icon" :size="14" />
                        {{ theme.label }}
                    </div>
                </button>
            </div>

            <div class="sp-help" style="margin-top:16px;">
                <template v-if="appearance === 'system'">
                    Sigue automáticamente la configuración de tu sistema operativo.
                </template>
                <template v-else>
                    La interfaz usará siempre el tema {{ appearance === 'light' ? 'claro' : 'oscuro' }}.
                </template>
            </div>
        </div>
    </div>
</template>
