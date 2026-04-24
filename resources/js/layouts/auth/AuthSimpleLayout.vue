<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Isotipo from '@/components/base/Isotipo.vue'
import { home } from '@/routes'

defineProps<{
    title?: string
    description?: string
}>()
</script>

<template>
    <div style="min-height:100svh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;background:var(--bg-page);padding:24px;">
        <div style="width:100%;max-width:400px;">
            <div style="display:flex;flex-direction:column;gap:32px;">
                <div style="display:flex;flex-direction:column;align-items:center;gap:16px;">
                    <Link :href="home()" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                        <Isotipo size="sm" variant="on-light" />
                        <span style="font-size:15px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:var(--text-primary);">
                            CACAO
                        </span>
                    </Link>
                    <div style="text-align:center;">
                        <h1 style="font-size:var(--text-xl);font-weight:600;color:var(--text-primary);margin:0 0 6px;">{{ title }}</h1>
                        <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">{{ description }}</p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
