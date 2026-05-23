<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="404 — Página no encontrada" />
  <div class="ep-root">
    <main class="ep-shell">
      <div class="ep-error">

        <!-- Left: SVG art -->
        <div class="ep-art">
          <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
            <!-- Paper background -->
            <rect class="ep-paper" x="10" y="10" width="280" height="280" rx="14"/>

            <!-- row 0 -->
            <rect class="ep-cell ep-c1" x="66"  y="66"  width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c2" x="130" y="66"  width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-cell-accent ep-c3" x="194" y="66"  width="56" height="56" rx="11" fill="var(--accent)"/>
            <!-- row 1 -->
            <rect class="ep-cell ep-c4" x="66"  y="130" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c5" x="130" y="130" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <!-- [1,2] = (194,130) intentionally empty -->
            <!-- row 2 -->
            <rect class="ep-cell ep-c6" x="66"  y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c7" x="130" y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-cell ep-c8" x="194" y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>

            <!-- Loupe: center of [1,2] = (222, 158). No SVG transform attr — position encoded in coords so CSS animations work correctly -->
            <g class="ep-loupe">
              <circle class="ep-loupe-ring" cx="222" cy="158" r="18" fill="none" stroke="var(--text-muted)" stroke-width="2.4"/>
              <line class="ep-loupe-handle" x1="235" y1="171" x2="244" y2="180" stroke="var(--text-muted)" stroke-width="2.4" stroke-linecap="round"/>
              <circle class="ep-ping" cx="222" cy="158" r="3" fill="var(--accent)"/>
            </g>
          </svg>
        </div>

        <!-- Right: copy -->
        <div class="ep-copy">
          <span class="ep-status">
            <span class="ep-dot"></span>
            <span class="ep-code">404</span>
            <span class="ep-sep"></span>
            <span>Página no encontrada</span>
          </span>

          <h1 class="ep-title">La buscamos por todas partes, pero <em>no aparece</em>.</h1>

          <p class="ep-lead">Es posible que el enlace haya cambiado, el archivo se haya movido de carpeta, o nunca haya existido. Volvamos a terreno conocido.</p>

          <div class="ep-actions">
            <a href="/" class="ep-btn ep-btn--primary">🏠 Ir al inicio</a>
            <a href="mailto:soporte@cacao.edu.ve" class="ep-btn ep-btn--link">Reportar enlace roto →</a>
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

/* Staggered cellDrop delays */
.ep-c1 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.05s both; }
.ep-c2 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.10s both; }
.ep-c3 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.15s both; }
.ep-c4 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.20s both; }
.ep-c5 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.25s both; }
.ep-c6 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.30s both; }
.ep-c7 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.35s both; }
.ep-c8 { animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.40s both; }

/* Accent cell override: also glow */
.ep-cell-accent {
  animation: ep-cellDrop 0.55s cubic-bezier(.2,.8,.2,1) 0.15s both,
             ep-accentGlow 3s ease-in-out 1.2s infinite;
}

/* Loupe — transform-box: fill-box fixes SVG transform-origin (scales from element center, not viewport) */
.ep-loupe {
  transform-box: fill-box;
  transform-origin: center;
  animation: ep-loupeIn 0.5s cubic-bezier(.2,.8,.2,1) 0.55s both,
             ep-loupeOrbit 5s ease-in-out 1.1s infinite;
}

.ep-ping {
  transform-box: fill-box;
  transform-origin: center;
  animation: ep-ping 4s ease-in-out 1.5s infinite;
}

@keyframes ep-accentGlow {
  0%,100% { filter: none; }
  50%     { filter: drop-shadow(0 0 6px color-mix(in oklab, var(--accent) 65%, transparent)); }
}

@keyframes ep-loupeIn {
  0%   { opacity: 0; transform: scale(0.4); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes ep-loupeOrbit {
  0%   { transform: translate(0,0) rotate(0deg); }
  25%  { transform: translate(-4px,-3px) rotate(-4deg); }
  50%  { transform: translate(3px,-5px) rotate(3deg); }
  75%  { transform: translate(5px,2px) rotate(5deg); }
  100% { transform: translate(0,0) rotate(0deg); }
}

@keyframes ep-ping {
  0%,18%,100% { opacity: 0; transform: scale(0.4); }
  22%         { opacity: 1; transform: scale(1.4); }
  32%         { opacity: 0; transform: scale(2.4); }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .ep-c1,.ep-c2,.ep-c3,.ep-c4,.ep-c5,.ep-c6,.ep-c7,.ep-c8,
  .ep-cell-accent, .ep-loupe, .ep-ping {
    animation: none !important;
    opacity: 1 !important;
  }
}
</style>
