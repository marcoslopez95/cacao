<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

const incidentId = ref('')
const copyLabel = ref('Copiar')
const retrySpinner = ref(false)

onMounted(() => {
    const stored = sessionStorage.getItem('ep_incident')

    if (stored) {
        incidentId.value = stored
    } else {
        const id = 'CAC-' + Math.floor(Math.random() * 0xFFFFFF).toString(16).toUpperCase().padStart(6, '0')
        sessionStorage.setItem('ep_incident', id)
        incidentId.value = id
    }
})

function handleRetry(): void {
    retrySpinner.value = true
    setTimeout(() => window.location.reload(), 600)
}

async function handleCopy(): Promise<void> {
    await navigator.clipboard.writeText(incidentId.value)
    copyLabel.value = 'Copiado'
    setTimeout(() => {
 copyLabel.value = 'Copiar' 
}, 1600)
}
</script>

<template>
  <Head title="500 — Error del servidor" />
  <div class="ep-root">
    <main class="ep-shell">
      <div class="ep-error">

        <div class="ep-art">
          <svg viewBox="0 0 300 300" xmlns="http://www.w3.org/2000/svg">
            <rect class="ep-paper" x="10" y="10" width="280" height="280" rx="14"/>

            <!-- 7 tilted ink cells -->
            <rect class="ep-d1" x="66"  y="66"  width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-d2" x="130" y="66"  width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <!-- [0,2] = sigil, no rect -->
            <rect class="ep-d3" x="66"  y="130" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-d4" x="130" y="130" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <!-- [1,2] always empty -->
            <rect class="ep-d5" x="66"  y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-d6" x="130" y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>
            <rect class="ep-d7" x="194" y="194" width="56" height="56" rx="11" fill="var(--text-primary)"/>

            <!-- Sigil at [0,2]: no SVG transform attrs — all coords absolute so CSS animations work correctly -->
            <!-- [0,2] origin=(194,66), cell center=(222,94) -->
            <g class="ep-sigil-wrap">
              <rect x="194" y="66" width="56" height="56" rx="11" fill="var(--text-primary)"/>
              <g class="ep-sigil-rotor">
                <circle cx="222" cy="94" r="22" fill="none" stroke="var(--text-muted)" stroke-width="2.2"
                        stroke-dasharray="120" stroke-dashoffset="120" class="ep-sigil-stroke"/>
                <path d="M222,72 A22,22 0 0 1 244,94" fill="none" stroke="var(--text-muted)"
                      stroke-width="2.2" stroke-linecap="round" class="ep-sigil-stroke"/>
                <line x1="200" y1="94" x2="244" y2="94" stroke="var(--text-muted)" stroke-width="1.5" class="ep-sigil-stroke"/>
                <line x1="222" y1="72" x2="222" y2="116" stroke="var(--text-muted)" stroke-width="1.5" class="ep-sigil-stroke"/>
              </g>
              <circle class="ep-sigil-dot" cx="222" cy="94" r="4.2" fill="var(--accent)"/>
            </g>
          </svg>
        </div>

        <div class="ep-copy">
          <span class="ep-status">
            <span class="ep-dot"></span>
            <span class="ep-code">500</span>
            <span class="ep-sep"></span>
            <span>Error interno del servidor</span>
          </span>

          <h1 class="ep-title">Algo se <em>rompió</em> de nuestro lado.</h1>

          <p class="ep-lead">
            Nuestro equipo ya recibió el aviso. Por lo general se resuelve con
            un reintento — si vuelve a fallar, comparte el código del incidente.
          </p>

          <div class="ep-actions">
            <button class="ep-btn ep-btn--primary" :disabled="retrySpinner" @click="handleRetry">
              <span v-if="retrySpinner" class="ep-spinner">↻</span>
              <span v-else>↺</span>
              Reintentar
            </button>
            <a href="/" class="ep-btn ep-btn--ghost">🏠 Ir al inicio</a>
          </div>

          <div class="ep-incident">
            <span>Incidente</span>
            <span class="ep-incident-id">{{ incidentId }}</span>
            <button class="ep-copy-btn" @click="handleCopy">{{ copyLabel }}</button>
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
        <span class="ep-live ep-live--danger"></span>
        Incidente activo
      </span>
    </footer>
  </div>
</template>

<style>
/* Tilted cell drops — each with unique final transform */
.ep-d1 { animation: ep-drop-1 0.6s cubic-bezier(.2,.8,.2,1) 0.05s both; }
.ep-d2 { animation: ep-drop-2 0.6s cubic-bezier(.2,.8,.2,1) 0.12s both; }
.ep-d3 { animation: ep-drop-3 0.6s cubic-bezier(.2,.8,.2,1) 0.20s both; }
.ep-d4 { animation: ep-drop-4 0.6s cubic-bezier(.2,.8,.2,1) 0.28s both; }
.ep-d5 { animation: ep-drop-5 0.6s cubic-bezier(.2,.8,.2,1) 0.36s both; }
.ep-d6 { animation: ep-drop-6 0.6s cubic-bezier(.2,.8,.2,1) 0.44s both; }
.ep-d7 { animation: ep-drop-7 0.6s cubic-bezier(.2,.8,.2,1) 0.52s both; }

@keyframes ep-drop-1 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(-2deg); }
  100% { opacity: 1; transform: translate(-2px,1px) rotate(-4deg); }
}
@keyframes ep-drop-2 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(1deg); }
  100% { opacity: 1; transform: translate(2px,-1px) rotate(3deg); }
}
@keyframes ep-drop-3 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(-1deg); }
  100% { opacity: 1; transform: translate(-3px,1px) rotate(-3deg); }
}
@keyframes ep-drop-4 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(2deg); }
  100% { opacity: 1; transform: translate(2px,-1px) rotate(5deg); }
}
@keyframes ep-drop-5 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(-2deg); }
  100% { opacity: 1; transform: translate(-1px,2px) rotate(-5deg); }
}
@keyframes ep-drop-6 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(1deg); }
  100% { opacity: 1; transform: translate(1px,1px) rotate(2deg); }
}
@keyframes ep-drop-7 {
  0%   { opacity: 0; transform: translateY(-14px) scale(0.7) rotate(0deg); }
  65%  { opacity: 1; transform: translateY(1px) scale(1.03) rotate(-3deg); }
  100% { opacity: 1; transform: translate(-1px,-1px) rotate(-7deg); }
}

/* Sigil — transform-box: fill-box fixes SVG transform-origin */
.ep-sigil-wrap {
  transform-box: fill-box;
  transform-origin: center;
  animation: ep-sigilIn 0.6s cubic-bezier(.2,.8,.2,1) 0.5s both;
}

.ep-sigil-rotor {
  transform-box: fill-box;
  transform-origin: center;
  animation: ep-rotorSpin 8s linear 1.2s infinite;
}

.ep-sigil-stroke {
  animation: ep-drawSigil 1.2s ease 0.8s both;
}

.ep-sigil-dot {
  transform-box: fill-box;
  transform-origin: center;
  animation: ep-dotBreath 2.5s ease-in-out 1.5s infinite;
}

@keyframes ep-sigilIn {
  0%   { opacity: 0; transform: scale(0.5); }
  100% { opacity: 1; transform: scale(1); }
}

@keyframes ep-rotorSpin {
  to { transform: rotate(360deg); }
}

@keyframes ep-drawSigil {
  to { stroke-dashoffset: 0; }
}

@keyframes ep-dotBreath {
  0%,100% { opacity: 0.6; transform: scale(1); }
  50%     { opacity: 1;   transform: scale(1.15); }
}

/* Danger live dot */
.ep-live--danger {
  background: var(--danger);
  animation: ep-live 2s ease-in-out infinite;
}

/* Retry spinner */
.ep-spinner {
  display: inline-block;
  animation: ep-rotorSpin 0.8s linear infinite;
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .ep-d1,.ep-d2,.ep-d3,.ep-d4,.ep-d5,.ep-d6,.ep-d7,
  .ep-sigil-wrap,.ep-sigil-rotor,.ep-sigil-stroke,.ep-sigil-dot,
  .ep-spinner {
    animation: none !important;
    opacity: 1 !important;
    transform: none !important;
    stroke-dashoffset: 0 !important;
  }
}
</style>
