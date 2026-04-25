<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CreateTeamModal from '@/components/CreateTeamModal.vue';
import Heading from '@/components/Heading.vue';
import Badge from '@/components/UI/AppBadge.vue';
import Button from '@/components/UI/AppButton.vue';
import Icon from '@/components/UI/AppIcon.vue';
import { edit, index } from '@/routes/teams';
import type { Team } from '@/types';

type Props = {
    teams: Team[];
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Teams',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Teams" />

    <h1 class="sr-only">Teams</h1>

    <div style="display:flex;flex-direction:column;gap:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <Heading
                variant="small"
                title="Teams"
                description="Manage your teams and team memberships"
            />

            <CreateTeamModal>
                <Button data-test="teams-new-team-button">
                    <template #icon>
                        <Icon name="plus" :size="16" />
                    </template>
                    New team
                </Button>
            </CreateTeamModal>
        </div>

        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <div
                v-for="team in teams"
                :key="team.id"
                data-test="team-row"
                style="display:flex;align-items:center;justify-content:space-between;border-radius:0.5rem;border:1px solid var(--border);padding:1rem;"
            >
                <div style="display:flex;align-items:center;gap:1rem;">
                    <div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <span style="font-weight:500;">{{ team.name }}</span>
                            <Badge v-if="team.isPersonal" variant="neutral">
                                Personal
                            </Badge>
                        </div>
                        <span style="font-size:var(--text-sm);color:var(--text-muted);">
                            {{ team.roleLabel }}
                        </span>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <Link :href="edit(team.slug)" title="View/Edit team">
                        <Button
                            :data-test="team.role === 'member' ? 'team-view-button' : 'team-edit-button'"
                            variant="ghost"
                            size="sm"
                            :icon-only="true"
                        >
                            <template #icon>
                                <Icon :name="team.role === 'member' ? 'eye' : 'edit'" :size="14" />
                            </template>
                        </Button>
                    </Link>
                </div>
            </div>

            <p
                v-if="teams.length === 0"
                style="padding:2rem 0;text-align:center;color:var(--text-muted);"
            >
                You don't belong to any teams yet.
            </p>
        </div>
    </div>
</template>
