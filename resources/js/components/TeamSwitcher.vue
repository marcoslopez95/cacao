<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import CreateTeamModal from '@/components/CreateTeamModal.vue';
import { switchMethod } from '@/routes/teams';
import type { Team } from '@/types';

const props = withDefaults(
    defineProps<{
        inHeader?: boolean;
    }>(),
    {
        inHeader: false,
    },
);

const page = usePage();
const isMobile = ref(false);
let mediaQuery: MediaQueryList | null = null;
const updateIsMobile = () => {
    if (mediaQuery) {
        isMobile.value = mediaQuery.matches;
    }
};

const currentTeam = computed(() => page.props.currentTeam);
const teams = computed(() => page.props.teams ?? []);
const isOpen = ref(false);
const createModalOpen = ref(false);

const switchTeam = (team: Team) => {
    const previousTeamSlug = currentTeam.value?.slug;
    isOpen.value = false;

    router.visit(switchMethod(team.slug), {
        onFinish: () => {
            if (!previousTeamSlug || typeof window === 'undefined') {
                router.reload();

                return;
            }

            const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
            const segment = `/${previousTeamSlug}`;

            if (currentUrl.includes(segment)) {
                router.visit(currentUrl.replace(segment, `/${team.slug}`), {
                    replace: true,
                });

                return;
            }

            router.reload();
        },
    });
};

onMounted(() => {
    mediaQuery = window.matchMedia('(max-width: 767px)');
    updateIsMobile();
    mediaQuery.addEventListener('change', updateIsMobile);
});

onUnmounted(() => {
    mediaQuery?.removeEventListener('change', updateIsMobile);
});
</script>

<template>
    <div style="position:relative;">
        <button
            data-test="team-switcher-trigger"
            @click="isOpen = !isOpen"
            style="display:flex;align-items:center;gap:0.5rem;padding:0.25rem 0.5rem;border-radius:0.375rem;border:none;background:transparent;cursor:pointer;font-size:var(--text-sm);color:var(--text-primary);"
        >
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;font-weight:500;">
                {{ currentTeam?.name ?? 'Select team' }}
            </span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M8 9l4-4 4 4"/><path d="M16 15l-4 4-4-4"/></svg>
        </button>

        <div
            v-if="isOpen"
            @click.stop
            style="position:absolute;right:0;top:100%;margin-top:0.25rem;min-width:14rem;border-radius:0.5rem;border:1px solid var(--border);background:var(--bg-card);box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:50;padding:0.25rem;"
        >
            <div style="padding:0.375rem 0.5rem;font-size:var(--text-xs);color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">
                Teams
            </div>
            <button
                v-for="team in teams"
                :key="team.id"
                data-test="team-switcher-item"
                @click="switchTeam(team)"
                style="display:flex;width:100%;align-items:center;gap:0.5rem;padding:0.375rem 0.5rem;border-radius:0.375rem;border:none;background:transparent;cursor:pointer;font-size:var(--text-sm);color:var(--text-primary);"
            >
                <span style="flex:1;text-align:left;">{{ team.name }}</span>
                <svg v-if="currentTeam?.id === team.id" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
            <div style="height:1px;background:var(--border);margin:0.25rem 0;" />
            <span @click="isOpen = false" style="display:block;">
                <CreateTeamModal>
                    <button
                        data-test="team-switcher-new-team"
                        style="display:flex;width:100%;align-items:center;gap:0.5rem;padding:0.375rem 0.5rem;border-radius:0.375rem;border:none;background:transparent;cursor:pointer;font-size:var(--text-sm);color:var(--text-muted);"
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        <span>New team</span>
                    </button>
                </CreateTeamModal>
            </span>
        </div>

        <!-- Overlay to close dropdown -->
        <div
            v-if="isOpen"
            @click="isOpen = false"
            style="position:fixed;inset:0;z-index:49;"
        />
    </div>
</template>
