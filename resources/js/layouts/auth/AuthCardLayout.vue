<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        style="display:flex;min-height:100svh;flex-direction:column;align-items:center;justify-content:center;gap:1.5rem;background:var(--bg-page);padding:1.5rem;"
    >
        <div style="display:flex;width:100%;max-width:28rem;flex-direction:column;gap:1.5rem;">
            <Link
                :href="home()"
                style="display:flex;align-items:center;gap:0.5rem;align-self:center;font-weight:500;text-decoration:none;"
            >
                <div style="display:flex;height:2.25rem;width:2.25rem;align-items:center;justify-content:center;">
                    <AppLogoIcon
                        style="width:2.25rem;height:2.25rem;fill:currentColor;"
                    />
                </div>
            </Link>

            <div style="display:flex;flex-direction:column;gap:1.5rem;">
                <div class="card" style="border-radius:0.75rem;">
                    <div class="card-head" style="padding:2rem 2.5rem 0;text-align:center;">
                        <h2 style="font-size:var(--text-xl);font-weight:600;color:var(--text-primary);margin:0 0 0.25rem;">{{ title }}</h2>
                        <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">{{ description }}</p>
                    </div>
                    <div class="card-body" style="padding:2rem 2.5rem;">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
