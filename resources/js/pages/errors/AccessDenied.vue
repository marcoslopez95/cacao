<script setup lang="ts">
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps<{ status: 401 | 403 }>()

const config = computed(() => ({
    401: {
        label: 'Necesitas iniciar sesión',
        title: 'Esta página está <em>bajo llave</em>.',
        lead: 'Para continuar necesitas iniciar sesión con una cuenta CACAO. Si ya tienes acceso, solo vuelve a entrar.',
        primary: { label: 'Iniciar sesión', href: '/login' },
        secondary: { label: 'Ir al inicio', href: '/' },
    },
    403: {
        label: 'Sin permisos para esta sección',
        title: 'Aquí <em>no puedes pasar</em>.',
        lead: 'Tu cuenta no tiene permiso para ver esta sección. Si crees que es un error, contacta a un administrador del sistema.',
        primary: { label: 'Contactar admin', href: 'mailto:admin@cacao.edu.ve?subject=403%20-%20solicito%20acceso' },
        secondary: { label: 'Ir al inicio', href: '/' },
    },
}[props.status]))
</script>

<template>
  <Head :title="`${status} — ${config.label}`" />
  <div class="ep-root">
    <main class="ep-shell">
      <div class="ep-error">

        <div class="ep-art">
          <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
            <rect class="ep-paper" x="10" y="10" width="280" height="280" rx="14"/>

            <!-- Isotipo 3×3 — cell 56, gap 8, anchor (66,66). [0,2] is the lock. [1,2] empty. -->
            <rect class="ep-cell ep-c1" x="66"  y="66"  width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c2" x="130" y="66"  width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <!-- [1,2] intentionally empty -->
            <rect class="ep-cell ep-c4" x="66"  y="130" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c5" x="130" y="130" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c6" x="66"  y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c7" x="130" y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c8" x="194" y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>

            <!-- Lock at [0,2]: outer group positions at (194,66), inner .ep-lock animates via CSS only -->
            <g transform="translate(194, 66)">
              <g class="ep-lock">
                <path class="ep-lock-shackle" d="M16,34 V22 a12,12 0 0 1 24,0 V34"/>
                <rect class="ep-lock-body" x="6" y="26" width="44" height="30" rx="6"/>
                <circle class="ep-lock-keyhole" cx="28" cy="38" r="3.2"/>
                <rect class="ep-lock-keystem" x="26.8" y="39.4" width="2.4" height="9" rx="0.8"/>
              </g>
            </g>
          </svg>
        </div>

        <div class="ep-copy">
          <span class="ep-status">
            <span class="ep-dot"></span>
            <span class="ep-code">{{ status }}</span>
            <span class="ep-sep"></span>
            <span>{{ config.label }}</span>
          </span>

          <h1 class="ep-title" v-html="config.title"></h1>
          <p class="ep-lead">{{ config.lead }}</p>

          <div class="ep-actions">
            <a :href="config.primary.href" class="ep-btn ep-btn--primary">{{ config.primary.label }}</a>
            <a :href="config.secondary.href" class="ep-btn ep-btn--ghost">{{ config.secondary.label }}</a>
          </div>
        </div>

      </div>
    </main>

    <footer class="ep-footer">
      <span class="ep-brand">
        <span class="ep-mark">
          <span></span><span></span><span class="ep-m-accent"></span>
          <span></span><span></span><span class="ep-m-empty"></span>
          <span></span><span></span><span></span>
        </span>
        CACAO · Sistema de Inscripción
      </span>
      <span class="ep-service">
        <span class="ep-live"></span>
        Servicio operativo
      </span>
    </footer>
  </div>
</template>

<style>
@import '@/../css/error-pages.css';

/* Staggered cell drops — 7 cells (no [0,2], taken by lock) */
.ep-c1 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.04s both; }
.ep-c2 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.10s both; }
.ep-c4 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.16s both; }
.ep-c5 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.22s both; }
.ep-c6 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.28s both; }
.ep-c7 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.34s both; }
.ep-c8 { animation: ep-cellDrop 0.5s cubic-bezier(.2,.9,.25,1.15) 0.40s both; }

/* Lock — outer <g transform> positions it; .ep-lock only has CSS animation */
.ep-lock {
  transform-box: fill-box;
  transform-origin: center 36px;
  animation:
    ep-lockIn 0.55s cubic-bezier(.2,.9,.25,1.15) 0.6s both,
    ep-lockJiggle 6s ease-in-out 3.8s infinite;
}

.ep-lock-shackle {
  fill: none;
  stroke: var(--accent);
  stroke-width: 5;
  stroke-linecap: round;
  transform-box: fill-box;
  transform-origin: 28px 22px;
  animation: ep-shackleClick 0.9s cubic-bezier(.4,0,.2,1) 1.05s both;
}

.ep-lock-body   { fill: var(--accent); }
.ep-lock-keyhole { fill: var(--bg-surface); }
.ep-lock-keystem { fill: var(--bg-surface); }

@keyframes ep-lockIn {
  0%   { opacity: 0; transform: translateY(-12px) scale(0.5); }
  60%  { opacity: 1; transform: translateY(2px)   scale(1.05); }
  100% { opacity: 1; transform: translateY(0)     scale(1); }
}

@keyframes ep-shackleClick {
  0%, 50% { transform: translateY(-10px); }
  80%     { transform: translateY(2px); }
  100%    { transform: translateY(0); }
}

@keyframes ep-lockJiggle {
  0%, 92%, 100% { transform: translate(0,0) rotate(0deg); }
  94%           { transform: translate(-1.5px,0) rotate(-3deg); }
  96%           { transform: translate(1.5px,0)  rotate(3deg); }
  98%           { transform: translate(0,0)      rotate(0deg); }
}

@media (prefers-reduced-motion: reduce) {
  .ep-c1,.ep-c2,.ep-c4,.ep-c5,.ep-c6,.ep-c7,.ep-c8,
  .ep-lock,.ep-lock-shackle {
    animation: none !important;
    opacity: 1 !important;
  }
}
</style>
