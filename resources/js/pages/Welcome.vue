<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { login, register } from '@/routes'

withDefaults(defineProps<{
    canRegister?: boolean
}>(), { canRegister: true })

const scrolled = ref(false)

function onScroll() {
    scrolled.value = window.scrollY > 10
}

onMounted(() => window.addEventListener('scroll', onScroll))
onUnmounted(() => window.removeEventListener('scroll', onScroll))

const aulas = [65, 30, 88, 45, 92, 60, 78, 40, 55, 95, 70, 82]
</script>

<template>
    <Head title="CACAO — Sistema de Gestión Académica" />

    <div class="min-h-screen bg-papel-dark dark:bg-tinta text-tinta dark:text-papel" style="font-family:var(--font-sans);">

        <!-- Nav -->
        <nav
            class="sticky top-0 z-50 flex items-center justify-between px-6 lg:px-12 py-5 transition-all"
            :class="scrolled ? 'border-b border-gris-borde dark:border-pizarra' : 'border-b border-transparent'"
            style="backdrop-filter:blur(12px);background:color-mix(in srgb,var(--bg-page) 85%,transparent);"
        >
            <div class="flex items-center gap-2.5 font-bold text-[18px] tracking-[-0.02em]">
                <div class="grid grid-cols-3 gap-[2.5px] w-5 shrink-0">
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                    <span class="rounded-xs aspect-square bg-terracota"></span>
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                    <span class="invisible aspect-square"></span>
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                    <span class="rounded-xs aspect-square bg-tinta dark:bg-papel"></span>
                </div>
                <span>CACAO</span>
            </div>

            <div class="hidden lg:flex items-center gap-8 text-[14px] font-medium">
                <a href="#producto" class="text-gris hover:text-tinta dark:hover:text-papel transition-colors" style="text-decoration:none;">Producto</a>
                <a href="#roles" class="text-gris hover:text-tinta dark:hover:text-papel transition-colors" style="text-decoration:none;">Para quién</a>
                <a href="#como" class="text-gris hover:text-tinta dark:hover:text-papel transition-colors" style="text-decoration:none;">Cómo funciona</a>
            </div>

            <div class="flex items-center gap-3">
                <Link :href="login()" class="px-4 py-2 text-[13px] font-medium text-gris hover:text-tinta dark:hover:text-papel transition-colors" style="text-decoration:none;">
                    Iniciar sesión
                </Link>
                <Link v-if="canRegister" :href="register()" class="px-4 py-2 text-[13px] font-semibold bg-terracota hover:bg-terra-hover text-white rounded-md transition-colors" style="text-decoration:none;">
                    Solicitar acceso
                </Link>
            </div>
        </nav>

        <!-- Hero -->
        <section class="px-6 lg:px-12 py-20 lg:py-28" style="max-width:1280px;margin:0 auto;">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-terra-light dark:bg-[#3D1E0E] border border-terracota/30 text-[12px] font-semibold text-terracota dark:text-terra-hover tracking-[0.04em] uppercase mb-8">
                <span class="w-1.5 h-1.5 rounded-full bg-terracota"></span>
                Control académico moderno
            </div>
            <h1 class="font-semibold leading-[1.02] tracking-[-0.035em] mb-8 max-w-[12ch]" style="font-size:clamp(44px,6vw,84px);text-wrap:balance;">
                El control académico <em class="not-italic text-terracota">que tu institución</em> realmente necesita.
            </h1>
            <p class="text-[20px] leading-[1.55] text-gris max-w-[58ch] mb-12" style="text-wrap:pretty;">
                CACAO gestiona carreras, pensums, períodos, secciones e inscripciones con las reglas reales del mundo académico — prelaciones, capacidades, evaluaciones — todo en un solo sistema.
            </p>
            <div class="flex gap-3 flex-wrap">
                <Link v-if="canRegister" :href="register()" class="px-6 py-3.5 text-[15px] font-semibold bg-terracota hover:bg-terra-hover text-white rounded-md transition-colors" style="text-decoration:none;">
                    Solicitar acceso
                </Link>
                <a href="#como" class="px-6 py-3.5 text-[15px] font-medium border border-gris-borde dark:border-pizarra hover:border-tinta dark:hover:border-papel rounded-md transition-colors" style="text-decoration:none;">
                    Ver cómo funciona →
                </a>
            </div>

            <!-- Product preview -->
            <div class="mt-20 rounded-xl overflow-hidden border border-gris-borde dark:border-pizarra bg-papel dark:bg-tinta-soft" style="box-shadow:0 40px 80px -30px rgba(0,0,0,0.25),0 20px 40px -20px rgba(0,0,0,0.15);position:relative;">
                <div class="absolute inset-x-0 top-0 h-px" style="background:linear-gradient(90deg,transparent,var(--accent),transparent);opacity:0.4;"></div>
                <div class="flex items-center gap-3 px-4 py-3 border-b border-gris-borde dark:border-pizarra bg-papel-dark dark:bg-[#1E1C1A]">
                    <div class="flex gap-1.5">
                        <span class="w-2.5 h-2.5 rounded-full bg-gris-borde dark:bg-pizarra"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gris-borde dark:bg-pizarra"></span>
                        <span class="w-2.5 h-2.5 rounded-full bg-gris-borde dark:bg-pizarra"></span>
                    </div>
                    <span class="flex-1 text-center font-mono text-[12px] text-gris">cacao.tuinstitucion.edu · Dashboard</span>
                </div>
                <div class="grid gap-4 p-6" style="grid-template-columns:180px 1fr;min-height:460px;">
                    <!-- Sidebar preview -->
                    <aside class="rounded-md p-3 flex flex-col gap-1" style="background:#131110;">
                        <div class="flex items-center gap-2 px-2 pb-4 pt-1 font-bold text-[14px]" style="color:#F4F2EF;">
                            <div class="grid grid-cols-3 gap-[2px] w-4 shrink-0">
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                                <span class="rounded-[1px] aspect-square bg-terracota"></span>
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                                <span class="invisible aspect-square"></span>
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                                <span class="rounded-[1px] aspect-square" style="background:#F4F2EF;"></span>
                            </div>
                            CACAO
                        </div>
                        <div v-for="item in ['Dashboard','Carreras','Secciones','Estudiantes','Profesores','Períodos']" :key="item"
                            class="flex items-center gap-2 px-2.5 py-1.5 rounded text-[12px]"
                            :style="item === 'Dashboard' ? 'background:#2A2826;color:#F4F2EF;' : 'color:#888780;'"
                        >
                            <span class="w-1 h-1 rounded-full opacity-60" style="background:currentColor;"></span>
                            {{ item }}
                        </div>
                    </aside>
                    <!-- Main preview -->
                    <main class="pt-2">
                        <div class="grid grid-cols-4 gap-3 mb-5">
                            <div v-for="kpi in [{l:'Estudiantes',v:'2,847',d:'+124 este período',neg:false},{l:'Secciones',v:'186',d:'94% ocupación',neg:false},{l:'Inscripciones',v:'12,408',d:'+8.2%',neg:false},{l:'Retiros',v:'47',d:'+12 vs ayer',neg:true}]"
                                :key="kpi.l"
                                class="p-3 bg-papel dark:bg-tinta rounded-md border border-gris-borde dark:border-pizarra"
                            >
                                <div class="text-[10px] uppercase tracking-[0.08em] text-gris">{{ kpi.l }}</div>
                                <div class="text-[22px] font-semibold tracking-[-0.02em] mt-1">{{ kpi.v }}</div>
                                <div class="text-[10px] mt-0.5" :style="kpi.neg ? 'color:var(--danger)' : 'color:var(--success)'">{{ kpi.d }}</div>
                            </div>
                        </div>
                        <div class="bg-papel dark:bg-tinta rounded-md border border-gris-borde dark:border-pizarra p-4" style="height:180px;display:flex;flex-direction:column;">
                            <div class="flex justify-between items-center mb-3">
                                <span class="text-[13px] font-semibold">Inscripciones por día</span>
                                <span class="text-[11px] text-gris font-mono">últimos 30 días</span>
                            </div>
                            <svg style="flex:1;width:100%;" viewBox="0 0 400 100" preserveAspectRatio="none">
                                <defs>
                                    <linearGradient id="cg" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="var(--accent)" stop-opacity="0.3"/>
                                        <stop offset="100%" stop-color="var(--accent)" stop-opacity="0"/>
                                    </linearGradient>
                                </defs>
                                <path d="M 0 80 L 20 72 L 40 85 L 60 60 L 80 55 L 100 48 L 120 62 L 140 40 L 160 45 L 180 30 L 200 38 L 220 25 L 240 35 L 260 22 L 280 28 L 300 18 L 320 25 L 340 15 L 360 20 L 380 12 L 400 18 L 400 100 L 0 100 Z" fill="url(#cg)"/>
                                <path d="M 0 80 L 20 72 L 40 85 L 60 60 L 80 55 L 100 48 L 120 62 L 140 40 L 160 45 L 180 30 L 200 38 L 220 25 L 240 35 L 260 22 L 280 28 L 300 18 L 320 25 L 340 15 L 360 20 L 380 12 L 400 18" fill="none" stroke="var(--accent)" stroke-width="1.5"/>
                            </svg>
                        </div>
                    </main>
                </div>
            </div>
        </section>

        <!-- Para quién -->
        <section id="roles" class="px-6 lg:px-12 py-24" style="max-width:1280px;margin:0 auto;">
            <div class="text-[12px] font-semibold tracking-[0.12em] uppercase text-terracota mb-4">Para quién</div>
            <h2 class="font-semibold tracking-[-0.03em] leading-[1.1] mb-5 max-w-[22ch]" style="font-size:clamp(32px,4vw,48px);text-wrap:balance;">Tres perfiles, una plataforma.</h2>
            <p class="text-[18px] leading-[1.55] text-gris max-w-[58ch] mb-14">
                Cada rol ve exactamente lo que necesita para trabajar. Sin menús sobrecargados, sin campos irrelevantes.
            </p>
            <div class="grid lg:grid-cols-3 gap-5">
                <div
                    v-for="role in [
                        { num:'01 / Admin', title:'Administración académica', desc:'Configura carreras, pensums y períodos. Asigna profesores y supervisa la operación.', items:['Gestión de carreras y pensums','Apertura de secciones por período','Asignación de profesores','Reportes de ocupación y retiros'] },
                        { num:'02 / Profesor', title:'Docentes', desc:'Cargan notas, controlan asistencia y revisan el histórico de secciones dictadas.', items:['Carga de notas por sección','Listado de alumnos inscritos','Control de asistencia','Histórico de secciones dictadas'] },
                        { num:'03 / Estudiante', title:'Estudiantes', desc:'Se inscriben en secciones habilitadas, consultan su pensum y evalúan a sus profesores.', items:['Inscripción en secciones disponibles','Consulta de pensum y prelaciones','Histórico académico completo','Evaluación al profesor'] },
                    ]"
                    :key="role.num"
                    class="p-8 bg-papel dark:bg-tinta-soft rounded-lg border border-gris-borde dark:border-pizarra hover:border-terracota transition-all duration-200 hover:-translate-y-0.5"
                >
                    <div class="font-mono text-[12px] text-gris tracking-[0.08em] mb-6">{{ role.num }}</div>
                    <h3 class="text-[24px] font-semibold tracking-[-0.02em] mb-3">{{ role.title }}</h3>
                    <p class="text-[14px] leading-[1.6] text-gris mb-6 min-h-[66px]">{{ role.desc }}</p>
                    <ul class="flex flex-col gap-2.5">
                        <li v-for="item in role.items" :key="item" class="flex items-start gap-2.5 text-[13px]">
                            <span class="w-2 h-2 bg-terracota rounded-[2px] mt-1.5 shrink-0"></span>
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Funcionalidades -->
        <section id="producto" class="px-6 lg:px-12 py-24" style="max-width:1280px;margin:0 auto;">
            <div class="text-[12px] font-semibold tracking-[0.12em] uppercase text-terracota mb-4">Funcionalidades</div>
            <h2 class="font-semibold tracking-[-0.03em] leading-[1.1] mb-5 max-w-[22ch]" style="font-size:clamp(32px,4vw,48px);text-wrap:balance;">Construido sobre las reglas reales del control académico.</h2>
            <p class="text-[18px] leading-[1.55] text-gris max-w-[58ch] mb-14">
                Prelaciones, pensums, períodos e inscripciones modelados como un solo sistema coherente — no módulos sueltos pegados con cinta.
            </p>
            <div class="grid gap-5" style="grid-template-columns:repeat(12,1fr);">
                <!-- Prelaciones — span 7 -->
                <div class="bg-papel dark:bg-tinta-soft rounded-lg border border-gris-borde dark:border-pizarra p-7 flex flex-col" style="grid-column:span 7;min-height:320px;">
                    <h3 class="text-[20px] font-semibold tracking-[-0.015em] mb-2.5">Prelaciones que se respetan solas</h3>
                    <p class="text-[14px] leading-[1.55] text-gris">Las materias con prelación solo se habilitan cuando el alumno tiene sus pre-requisitos aprobados. Sin intervención manual, sin excepciones olvidadas.</p>
                    <div class="mt-auto pt-6">
                        <div class="flex items-center gap-2 p-3.5 bg-papel-dark dark:bg-tinta rounded-md border border-gris-borde dark:border-pizarra overflow-x-auto">
                            <template v-for="(node, i) in [
                                {code:'MAT-101',active:false,locked:false},
                                {code:'→',active:false,locked:false,arrow:true},
                                {code:'MAT-201',active:false,locked:false},
                                {code:'→',active:false,locked:false,arrow:true},
                                {code:'MAT-301',active:true,locked:false},
                                {code:'→',active:false,locked:false,arrow:true},
                                {code:'MAT-401',active:false,locked:true},
                            ]" :key="i">
                                <span v-if="node.arrow" class="text-gris text-[14px] shrink-0">→</span>
                                <span v-else
                                    class="shrink-0 px-3 py-2 rounded font-mono text-[11px] border"
                                    :class="node.active ? 'bg-terracota text-white border-terracota' : node.locked ? 'opacity-50 italic border-gris-borde dark:border-pizarra bg-papel dark:bg-tinta' : 'border-gris-borde dark:border-pizarra bg-papel dark:bg-tinta'"
                                >{{ node.code }}</span>
                            </template>
                        </div>
                        <div class="text-[11px] text-gris font-mono mt-3">MAT-401 bloqueada hasta aprobar MAT-301</div>
                    </div>
                </div>
                <!-- Inscripciones — span 5 -->
                <div class="bg-papel dark:bg-tinta-soft rounded-lg border border-gris-borde dark:border-pizarra p-7 flex flex-col" style="grid-column:span 5;min-height:320px;">
                    <h3 class="text-[20px] font-semibold tracking-[-0.015em] mb-2.5">Inscripciones con capacidad</h3>
                    <p class="text-[14px] leading-[1.55] text-gris">Cada sección tiene cupo. El sistema impide inscripciones duplicadas o en secciones llenas.</p>
                    <div class="mt-auto pt-6 flex flex-col gap-2 p-3.5 bg-papel-dark dark:bg-tinta rounded-md border border-gris-borde dark:border-pizarra">
                        <div v-for="row in [
                            {code:'MAT-301·S01',name:'Cálculo III',cap:'24 / 30',selected:true,full:false},
                            {code:'MAT-301·S02',name:'Cálculo III',cap:'30 / 30',selected:false,full:true},
                            {code:'FIS-201·S01',name:'Física II',cap:'18 / 25',selected:false,full:false},
                        ]" :key="row.code"
                            class="flex items-center justify-between px-3 py-2.5 rounded text-[12px] border"
                            :class="row.selected ? 'border-terracota bg-terra-light dark:bg-[#3D1E0E]' : 'border-gris-borde dark:border-pizarra bg-papel dark:bg-tinta'"
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="font-mono text-[11px] text-gris">{{ row.code }}</span>
                                <span class="font-medium">{{ row.name }}</span>
                            </div>
                            <span class="font-mono text-[11px]" :style="row.full ? 'color:var(--danger)' : 'color:var(--text-muted)'">{{ row.cap }}</span>
                        </div>
                    </div>
                </div>
                <!-- Evaluación — span 4 -->
                <div class="bg-papel dark:bg-tinta-soft rounded-lg border border-gris-borde dark:border-pizarra p-7 flex flex-col" style="grid-column:span 4;min-height:260px;">
                    <h3 class="text-[20px] font-semibold tracking-[-0.015em] mb-2.5">Evaluación al profesor</h3>
                    <p class="text-[14px] leading-[1.55] text-gris">Al cierre del período, cada alumno evalúa a sus profesores. Feedback agregado, nunca individual.</p>
                    <div class="mt-auto pt-6 flex flex-col gap-3.5 p-4 bg-papel-dark dark:bg-tinta rounded-md border border-gris-borde dark:border-pizarra">
                        <div v-for="row in [{label:'Claridad',stars:4},{label:'Preparación',stars:5},{label:'Puntualidad',stars:3}]" :key="row.label" class="flex items-center justify-between">
                            <span class="text-[13px]">{{ row.label }}</span>
                            <div class="flex gap-1">
                                <svg v-for="i in 5" :key="i" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="currentColor" :style="i <= row.stars ? 'color:var(--accent)' : 'color:var(--border)'">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Ocupación — span 4 -->
                <div class="bg-papel dark:bg-tinta-soft rounded-lg border border-gris-borde dark:border-pizarra p-7 flex flex-col" style="grid-column:span 4;min-height:260px;">
                    <h3 class="text-[20px] font-semibold tracking-[-0.015em] mb-2.5">Ocupación de aulas</h3>
                    <p class="text-[14px] leading-[1.55] text-gris">Visión en tiempo real de la capacidad por aula y franja horaria.</p>
                    <div class="mt-auto pt-6 grid gap-1.5" style="grid-template-columns:repeat(6,1fr);">
                        <div v-for="(v, i) in aulas" :key="i"
                            class="relative overflow-hidden rounded border border-gris-borde dark:border-pizarra"
                            style="aspect-ratio:1;background:var(--bg-surface-2);"
                        >
                            <div class="absolute inset-x-0 bottom-0 transition-all"
                                :style="`height:${v}%;opacity:0.8;background:${v < 40 ? 'var(--success)' : v > 85 ? 'var(--danger)' : 'var(--accent)'}`"
                            ></div>
                        </div>
                    </div>
                </div>
                <!-- Períodos — span 4 -->
                <div class="bg-papel dark:bg-tinta-soft rounded-lg border border-gris-borde dark:border-pizarra p-7 flex flex-col" style="grid-column:span 4;min-height:260px;">
                    <h3 class="text-[20px] font-semibold tracking-[-0.015em] mb-2.5">Gestión por períodos</h3>
                    <p class="text-[14px] leading-[1.55] text-gris">Cada período se abre, se cierra, se archiva. Sin mezclar datos históricos con los vigentes.</p>
                    <div class="mt-auto pt-6 flex flex-col gap-2">
                        <div v-for="p in [{label:'2025-II',badge:'Activo',active:true,opacity:'1'},{label:'2025-I',badge:'Cerrado',active:false,opacity:'0.7'},{label:'2024-II',badge:'Archivado',active:false,opacity:'0.5'}]"
                            :key="p.label"
                            class="flex items-center justify-between px-3 py-2.5 rounded text-[12px] border border-gris-borde dark:border-pizarra bg-papel-dark dark:bg-tinta"
                            :style="`opacity:${p.opacity};`"
                        >
                            <span class="font-medium">{{ p.label }}</span>
                            <span class="badge" :class="p.active ? 'badge-accent' : 'badge-neutral'">{{ p.badge }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Cómo funciona -->
        <section id="como" style="background:var(--text-primary);color:var(--bg-page);">
            <div class="px-6 lg:px-12 py-24" style="max-width:1280px;margin:0 auto;">
                <div class="text-[12px] font-semibold tracking-[0.12em] uppercase mb-4" style="color:var(--accent);">Cómo funciona</div>
                <h2 class="font-semibold tracking-[-0.03em] leading-[1.1] mb-5 max-w-[22ch]" style="font-size:clamp(32px,4vw,48px);text-wrap:balance;color:var(--bg-page);">El período académico, de principio a fin.</h2>
                <p class="text-[18px] leading-[1.55] max-w-[58ch] mb-14" style="color:color-mix(in srgb,var(--bg-page) 65%,transparent);">
                    Cuatro pasos que reflejan cómo realmente funciona el control académico. Sin atajos, sin pasos inventados.
                </p>
                <div class="rounded-lg overflow-hidden border" style="display:grid;grid-template-columns:repeat(4,1fr);border-color:var(--border);">
                    <div
                        v-for="(step, i) in [
                            {n:'01',t:'Configurá carreras y pensums',d:'Definí estructura académica: carreras, materias, prelaciones, aulas.'},
                            {n:'02',t:'Abrí el período',d:'Creá secciones por materia, asigná profesores y cupos.'},
                            {n:'03',t:'Los alumnos se inscriben',d:'Solo ven materias habilitadas por sus prelaciones aprobadas.'},
                            {n:'04',t:'Cerrá y archivá',d:'Los profesores cargan notas. El período se cierra y queda en el histórico.'},
                        ]"
                        :key="step.n"
                        class="p-7 relative"
                        :style="`background:var(--bg-surface);${i < 3 ? 'border-right:1px solid var(--border);' : ''}`"
                    >
                        <div class="inline-flex items-center justify-center w-9 h-9 rounded-full font-mono text-[14px] font-semibold mb-5 border" style="color:var(--accent);border-color:var(--accent);">{{ step.n }}</div>
                        <h3 class="text-[17px] font-semibold tracking-[-0.01em] mb-2">{{ step.t }}</h3>
                        <p class="text-[13px] leading-[1.55]" style="color:var(--text-secondary);">{{ step.d }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section style="background:var(--text-primary);color:var(--bg-page);padding:100px 48px;text-align:center;position:relative;overflow:hidden;">
            <div style="max-width:720px;margin:0 auto;position:relative;z-index:2;">
                <div class="grid grid-cols-3 gap-[3.5px] mx-auto mb-8" style="width:72px;">
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                    <span class="rounded aspect-square bg-terracota"></span>
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                    <span class="invisible aspect-square"></span>
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                    <span class="rounded aspect-square" style="background:var(--bg-page);"></span>
                </div>
                <h2 class="font-semibold tracking-[-0.03em] leading-[1.05] mb-6" style="font-size:clamp(36px,5vw,60px);text-wrap:balance;color:var(--bg-page);">
                    ¿Listo para ordenar el control académico de tu institución?
                </h2>
                <p class="text-[18px] mb-10" style="color:color-mix(in srgb,var(--bg-page) 65%,transparent);">
                    Agendá una demo de 30 minutos con el equipo. Te mostramos el sistema con datos reales de tu institución.
                </p>
                <div class="flex gap-3 justify-center flex-wrap">
                    <Link v-if="canRegister" :href="register()" class="px-6 py-3.5 text-[15px] font-semibold bg-terracota hover:bg-terra-hover text-white rounded-md transition-colors" style="text-decoration:none;">
                        Solicitar acceso
                    </Link>
                    <a href="#como" class="px-6 py-3.5 text-[15px] font-medium rounded-md transition-colors" style="text-decoration:none;border:1px solid color-mix(in srgb,var(--bg-page) 30%,transparent);color:var(--bg-page);">
                        Agendar demo
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-gris-borde dark:border-pizarra" style="background:var(--bg-page);padding:48px 48px 32px;">
            <div style="max-width:1280px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:48px;">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-2.5 font-bold text-[18px]">
                        <div class="grid grid-cols-3 gap-[2px] w-5 shrink-0">
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                            <span class="rounded-[1.5px] aspect-square bg-terracota"></span>
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                            <span class="invisible aspect-square"></span>
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                            <span class="rounded-[1.5px] aspect-square bg-tinta dark:bg-papel"></span>
                        </div>
                        CACAO
                    </div>
                    <p class="text-[13px] text-gris leading-[1.5] max-w-[34ch]">Control Académico, Curricular y de Actividades Organizadas. Un sistema para administrar la vida académica de tu institución.</p>
                </div>
                <div v-for="col in [
                    {title:'Producto',links:['Funcionalidades','Roles','Integraciones','Changelog']},
                    {title:'Recursos',links:['Documentación','Guías','Soporte','Estado del servicio']},
                    {title:'Empresa',links:['Sobre CACAO','Contacto','Términos','Privacidad']},
                ]" :key="col.title">
                    <div class="text-[11px] font-semibold tracking-[0.12em] uppercase text-gris mb-4">{{ col.title }}</div>
                    <ul class="flex flex-col gap-2.5">
                        <li v-for="link in col.links" :key="link">
                            <a href="#" class="text-[13px] hover:text-terracota transition-colors" style="text-decoration:none;">{{ link }}</a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="flex justify-between items-center text-[12px] text-gris" style="max-width:1280px;margin:48px auto 0;padding-top:24px;border-top:1px solid var(--border);">
                <span>© 2025 CACAO · Todos los derechos reservados</span>
                <span>v1.0 · hecho con rigor académico</span>
            </div>
        </footer>
    </div>
</template>
