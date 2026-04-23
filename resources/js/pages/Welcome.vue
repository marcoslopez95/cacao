<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="CACAO — Sistema de Gestión Académica" />

    <div class="flex min-h-screen flex-col bg-papel-dark dark:bg-tinta">

        <!-- Header -->
        <header class="h-13 bg-tinta flex items-center px-6 lg:px-10 justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="grid grid-cols-3 gap-[2.5px] w-5 shrink-0">
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                    <span class="rounded-xs aspect-square bg-terracota"></span>
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                    <span class="invisible aspect-square"></span>
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                    <span class="rounded-xs aspect-square bg-white/90"></span>
                </div>
                <span class="text-[13px] font-bold tracking-[4px] text-white uppercase leading-none">CACAO</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[12px] text-gris hidden lg:block">
                    Control Académico · Cursos · Operaciones
                </span>
                <template v-if="$page.props.auth.user">
                    <Link
                        href="/dashboard"
                        class="bg-terracota hover:bg-terra-hover text-white text-[12px] font-semibold px-4 py-1.75 rounded-[7px] transition-colors"
                    >
                        Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link
                        :href="login()"
                        class="text-[12px] text-gris-light hover:text-white transition-colors"
                    >
                        Ingresar
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="bg-terracota hover:bg-terra-hover text-white text-[12px] font-semibold px-4 py-1.75 rounded-[7px] transition-colors"
                    >
                        Registrarse
                    </Link>
                </template>
            </div>
        </header>

        <!-- Hero -->
        <main class="flex-1 flex flex-col lg:flex-row items-center px-6 lg:px-10 py-12 lg:py-16 gap-10 lg:gap-16 w-full max-w-7xl mx-auto">

            <!-- Left: copy -->
            <div class="flex-1 max-w-105">
                <div class="inline-flex items-center gap-1.5 bg-terra-light dark:bg-[#3D1E0E] border border-terra-hover dark:border-[#5A2D12] rounded-full px-3 py-1 mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-terracota dark:bg-terra-hover shrink-0"></span>
                    <span class="text-[11px] font-semibold text-terra-text dark:text-terra-hover">
                        Sistema de Gestión Académica
                    </span>
                </div>

                <h1 class="text-[32px] lg:text-[38px] font-bold text-tinta dark:text-papel leading-[1.15] tracking-[-0.5px] mb-4">
                    Gestión académica<br>
                    <span class="text-terracota dark:text-terra-hover">integrada</span> para<br>
                    tu institución
                </h1>

                <p class="text-[13px] text-gris leading-[1.7] mb-8">
                    Control total sobre carreras, inscripciones, evaluaciones y comunicación. Diseñado para instituciones educativas venezolanas.
                </p>

                <div class="flex items-center gap-3">
                    <Link
                        :href="login()"
                        class="bg-tinta dark:bg-papel hover:bg-tinta-soft dark:hover:bg-papel-dark text-white dark:text-tinta text-[13px] font-semibold px-5 py-2.5 rounded-md transition-colors"
                    >
                        Acceder al sistema
                    </Link>
                    <a
                        href="#modulos"
                        class="border border-gris-borde dark:border-pizarra bg-hueso dark:bg-tinta-soft hover:bg-papel-dark dark:hover:bg-pizarra text-tinta dark:text-papel text-[13px] font-medium px-5 py-2.5 rounded-md transition-colors"
                    >
                        Ver módulos ↓
                    </a>
                </div>
            </div>

            <!-- Right: modules grid -->
            <div class="flex-1 w-full" id="modulos">
                <div class="grid grid-cols-2 gap-3">

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-lg p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3 flex items-center justify-center">
                            <span class="w-3 h-3 border-2 border-terracota dark:border-terra-hover rounded-xs"></span>
                        </div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Académico</p>
                        <p class="text-[11px] text-gris leading-normal">Carreras, pensums, materias y prelaciones</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-lg p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Personas</p>
                        <p class="text-[11px] text-gris leading-normal">Estudiantes, profesores y representantes</p>
                    </div>

                    <div class="bg-terracota dark:bg-terra-text rounded-lg p-4">
                        <div class="w-7 h-7 bg-white/20 rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-white mb-1">Inscripciones</p>
                        <p class="text-[11px] text-white/75 leading-normal">Validación de prelaciones y cupos</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-lg p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Evaluaciones</p>
                        <p class="text-[11px] text-gris leading-normal">Quiz, tests y entregas de archivos</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-lg p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Infraestructura</p>
                        <p class="text-[11px] text-gris leading-normal">Aulas, horarios y departamentos</p>
                    </div>

                    <div class="bg-hueso dark:bg-[#1E1C1A] border border-gris-borde dark:border-pizarra rounded-lg p-4">
                        <div class="w-7 h-7 bg-tinta dark:bg-pizarra rounded-[7px] mb-3"></div>
                        <p class="text-[13px] font-semibold text-tinta dark:text-papel mb-1">Recursos</p>
                        <p class="text-[11px] text-gris leading-normal">Materiales de apoyo por sección</p>
                    </div>

                </div>
            </div>

        </main>

        <!-- Footer -->
        <footer class="bg-tinta py-3.5 px-6 lg:px-10 flex items-center justify-between shrink-0">
            <span class="text-[11px] text-gris">© 2026 CACAO · Sistema de gestión académica</span>
            <span class="text-[11px] text-gris">Venezuela</span>
        </footer>

    </div>
</template>
