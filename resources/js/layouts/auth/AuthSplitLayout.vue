<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { home } from '@/routes'

defineProps<{
    title?: string
    description?: string
    panelQuote?: string
    panelHighlight?: string
    panelRole?: string
    panelContext?: string
}>()
</script>

<template>
    <div class="min-h-dvh grid lg:grid-cols-2">

        <!-- Editorial panel — always dark, never inverts -->
        <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden px-16 py-12" style="background:#131110;color:#F4F2EF;">

            <!-- Brand top -->
            <div class="relative z-10 flex items-center gap-2.5">
                <Link :href="home()" class="flex items-center gap-2.5" style="text-decoration:none;color:#F4F2EF;">
                    <div class="grid grid-cols-3 gap-[2.5px] w-5 shrink-0">
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                        <span class="rounded-xs aspect-square bg-terracota"></span>
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                        <span class="invisible aspect-square"></span>
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                        <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    </div>
                    <span class="text-[13px] font-bold tracking-[4px] uppercase leading-none">CACAO</span>
                </Link>
            </div>

            <!-- Quote -->
            <div class="relative z-10 max-w-md">
                <h2 class="font-medium text-[32px] xl:text-[40px] leading-[1.15] tracking-[-0.025em] text-balance" style="color:#F4F2EF;">
                    {{ panelQuote ?? 'Cada período, cada sección, cada nota.' }}
                    <em class="not-italic text-terra-hover">{{ panelHighlight ?? 'Sin hojas de cálculo sueltas.' }}</em>
                </h2>
                <div class="mt-6 flex items-center gap-3 text-[13px] font-mono" style="color:rgba(244,242,239,0.55);">
                    <span>{{ panelRole ?? 'Portal institucional' }}</span>
                    <span class="w-1 h-1 rounded-full" style="background:rgba(244,242,239,0.35);"></span>
                    <span>{{ panelContext ?? 'Acceso seguro' }}</span>
                </div>
            </div>

            <!-- Footer meta -->
            <div class="relative z-10 flex items-center justify-between text-[12px] font-mono" style="color:rgba(244,242,239,0.5);">
                <span>v1.0 · control académico</span>
                <span>CACAO · ES</span>
            </div>

            <!-- Decorative isotipo -->
            <div class="pointer-events-none absolute -bottom-20 -right-20 opacity-[0.08] origin-bottom-right scale-[3]" aria-hidden="true">
                <div class="grid grid-cols-3 gap-[6px] w-[90px]">
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    <span class="rounded-xs aspect-square bg-terracota" style="opacity:1;"></span>
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    <span class="invisible aspect-square"></span>
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                    <span class="rounded-xs aspect-square" style="background:#F4F2EF;"></span>
                </div>
            </div>
        </aside>

        <!-- Form side -->
        <div class="relative flex items-center justify-center bg-papel dark:bg-tinta px-6 py-10 lg:px-16 lg:py-12">

            <!-- Back link -->
            <div class="absolute top-6 right-6 lg:top-8 lg:right-10">
                <Link :href="home()" class="inline-flex items-center gap-1.5 text-[13px] text-gris hover:text-tinta dark:hover:text-papel transition-colors" style="text-decoration:none;">
                    <span aria-hidden="true">←</span>
                    <span>Volver al sitio</span>
                </Link>
            </div>

            <div class="w-full max-w-[400px]">
                <div v-if="title || description" class="mb-9">
                    <h1 v-if="title" class="text-[32px] font-semibold tracking-[-0.025em] leading-[1.1] text-tinta dark:text-papel">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="mt-3 text-[15px] text-gris leading-relaxed">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
