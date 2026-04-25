<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CancelInvitationModal from '@/components/CancelInvitationModal.vue';
import DeleteTeamModal from '@/components/DeleteTeamModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import InviteMemberModal from '@/components/InviteMemberModal.vue';
import RemoveMemberModal from '@/components/RemoveMemberModal.vue';
import Avatar from '@/components/UI/AppAvatar.vue';
import Badge from '@/components/UI/AppBadge.vue';
import Button from '@/components/UI/AppButton.vue';
import Icon from '@/components/UI/AppIcon.vue';
import { useInitials } from '@/composables/useInitials';
import { edit, index, update } from '@/routes/teams';
import { update as updateMember } from '@/routes/teams/members';
import type {
    RoleOption,
    Team,
    TeamInvitation,
    TeamMember,
    TeamPermissions,
} from '@/types';

type Props = {
    team: Team;
    members: TeamMember[];
    invitations: TeamInvitation[];
    permissions: TeamPermissions;
    availableRoles: RoleOption[];
};

const props = defineProps<Props>();

defineOptions({
    layout: (props: { team: Team }) => ({
        breadcrumbs: [
            {
                title: 'Teams',
                href: index(),
            },
            {
                title: props.team.name,
                href: edit(props.team.slug),
            },
        ],
    }),
});

const { getInitials } = useInitials();

const inviteDialogOpen = ref(false);
const deleteDialogOpen = ref(false);
const removeMemberDialogOpen = ref(false);
const memberToRemove = ref<TeamMember | null>(null);
const cancelInvitationDialogOpen = ref(false);
const invitationToCancel = ref<TeamInvitation | null>(null);

const pageTitle = computed(() =>
    props.permissions.canUpdateTeam
        ? `Edit ${props.team.name}`
        : `View ${props.team.name}`,
);

const openRoleDropdown = ref<number | null>(null);

const updateMemberRole = (member: TeamMember, newRole: string) => {
    openRoleDropdown.value = null;
    router.visit(updateMember([props.team.slug, member.id]), {
        data: { role: newRole },
        preserveScroll: true,
    });
};

const confirmRemoveMember = (member: TeamMember) => {
    memberToRemove.value = member;
    removeMemberDialogOpen.value = true;
};

const confirmCancelInvitation = (invitation: TeamInvitation) => {
    invitationToCancel.value = invitation;
    cancelInvitationDialogOpen.value = true;
};
</script>

<template>
    <Head :title="pageTitle" />

    <h1 class="sr-only">{{ pageTitle }}</h1>

    <div style="display:flex;flex-direction:column;gap:2.5rem;">
        <!-- Team Name Section -->
        <div v-if="permissions.canUpdateTeam" style="display:flex;flex-direction:column;gap:1.5rem;">
            <Heading
                variant="small"
                title="Team settings"
                description="Update your team name and settings"
            />

            <Form
                v-bind="update.form(team.slug)"
                style="display:flex;flex-direction:column;gap:1.5rem;"
                v-slot="{ errors, processing }"
            >
                <div style="display:grid;gap:0.5rem;">
                    <label for="team-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Team name</label>
                    <input
                        id="team-name"
                        name="name"
                        data-test="team-name-input"
                        :default-value="team.name"
                        required
                        class="input"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:flex;align-items:center;gap:1rem;">
                    <Button
                        type="submit"
                        data-test="team-save-button"
                        :disabled="processing"
                        :loading="processing"
                    >
                        Save
                    </Button>
                </div>
            </Form>
        </div>

        <div v-else style="display:flex;flex-direction:column;gap:1.5rem;">
            <Heading variant="small" :title="team.name" />
        </div>

        <!-- Members Section -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <Heading
                    variant="small"
                    title="Team members"
                    :description="
                        permissions.canCreateInvitation
                            ? 'Manage who belongs to this team'
                            : ''
                    "
                />

                <Button
                    v-if="permissions.canCreateInvitation"
                    data-test="invite-member-button"
                    @click="inviteDialogOpen = true"
                >
                    <template #icon>
                        <Icon name="userPlus" :size="16" />
                    </template>
                    Invite member
                </Button>
            </div>

            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <div
                    v-for="member in members"
                    :key="member.id"
                    data-test="member-row"
                    style="display:flex;align-items:center;justify-content:space-between;border-radius:0.5rem;border:1px solid var(--border);padding:1rem;"
                >
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <Avatar
                            :initials="getInitials(member.name)"
                            :src="member.avatar ?? undefined"
                            size="md"
                        />
                        <div>
                            <div style="font-weight:500;">
                                {{ member.name }}
                            </div>
                            <div style="font-size:var(--text-sm);color:var(--text-muted);">
                                {{ member.email }}
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:0.5rem;position:relative;">
                        <div
                            v-if="member.role !== 'owner' && permissions.canUpdateMember"
                            style="position:relative;"
                        >
                            <Button
                                data-test="member-role-trigger"
                                variant="secondary"
                                size="sm"
                                @click="openRoleDropdown = openRoleDropdown === member.id ? null : member.id"
                            >
                                {{ member.role_label }}
                                <template #iconRight>
                                    <Icon name="chevronDown" :size="14" />
                                </template>
                            </Button>
                            <div
                                v-if="openRoleDropdown === member.id"
                                style="position:absolute;right:0;top:100%;margin-top:0.25rem;min-width:10rem;border-radius:0.5rem;border:1px solid var(--border);background:var(--bg-card);box-shadow:0 4px 16px rgba(0,0,0,0.12);z-index:50;padding:0.25rem;"
                            >
                                <button
                                    v-for="role in availableRoles"
                                    :key="role.value"
                                    data-test="member-role-option"
                                    @click="updateMemberRole(member, role.value)"
                                    style="display:block;width:100%;padding:0.375rem 0.75rem;text-align:left;font-size:var(--text-sm);border:none;background:transparent;cursor:pointer;border-radius:0.25rem;color:var(--text-primary);"
                                >
                                    {{ role.label }}
                                </button>
                            </div>
                            <div
                                v-if="openRoleDropdown === member.id"
                                @click="openRoleDropdown = null"
                                style="position:fixed;inset:0;z-index:49;"
                            />
                        </div>
                        <Badge v-else variant="neutral">
                            {{ member.role_label }}
                        </Badge>

                        <Button
                            v-if="member.role !== 'owner' && permissions.canRemoveMember"
                            data-test="member-remove-button"
                            variant="ghost"
                            size="sm"
                            :icon-only="true"
                            title="Remove member"
                            @click="confirmRemoveMember(member)"
                        >
                            <template #icon>
                                <Icon name="x" :size="14" />
                            </template>
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Invitations Section -->
        <div v-if="invitations.length > 0" style="display:flex;flex-direction:column;gap:1.5rem;">
            <Heading
                variant="small"
                title="Pending invitations"
                description="Invitations that haven't been accepted yet"
            />

            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                <div
                    v-for="invitation in invitations"
                    :key="invitation.code"
                    data-test="invitation-row"
                    style="display:flex;align-items:center;justify-content:space-between;border-radius:0.5rem;border:1px solid var(--border);padding:1rem;"
                >
                    <div style="display:flex;align-items:center;gap:1rem;">
                        <div
                            style="display:flex;height:2.5rem;width:2.5rem;align-items:center;justify-content:center;border-radius:50%;background:var(--bg-subtle);"
                        >
                            <Icon name="mail" :size="18" style="color:var(--text-muted);" />
                        </div>
                        <div>
                            <div style="font-weight:500;">
                                {{ invitation.email }}
                            </div>
                            <div style="font-size:var(--text-sm);color:var(--text-muted);">
                                {{ invitation.role_label }}
                            </div>
                        </div>
                    </div>

                    <Button
                        v-if="permissions.canCancelInvitation"
                        data-test="invitation-cancel-button"
                        variant="ghost"
                        size="sm"
                        :icon-only="true"
                        title="Cancel invitation"
                        @click="confirmCancelInvitation(invitation)"
                    >
                        <template #icon>
                            <Icon name="x" :size="14" />
                        </template>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Danger Zone -->
        <div
            v-if="permissions.canDeleteTeam && !team.isPersonal"
            style="display:flex;flex-direction:column;gap:1.5rem;"
        >
            <Heading
                variant="small"
                title="Delete team"
                description="Permanently delete your team"
            />
            <div
                style="display:flex;flex-direction:column;gap:1rem;border-radius:0.5rem;border:1px solid #fecaca;background:#fef2f2;padding:1rem;"
            >
                <div style="color:#dc2626;">
                    <p style="font-weight:500;margin:0;">Warning</p>
                    <p style="font-size:var(--text-sm);margin:0;">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>
                <div>
                    <Button
                        data-test="delete-team-button"
                        variant="danger"
                        @click="deleteDialogOpen = true"
                    >
                        Delete team
                    </Button>
                </div>
            </div>
        </div>
    </div>

    <InviteMemberModal
        v-if="permissions.canCreateInvitation"
        :team="team"
        :available-roles="availableRoles"
        :open="inviteDialogOpen"
        @update:open="inviteDialogOpen = $event"
    />

    <RemoveMemberModal
        :team="team"
        :member="memberToRemove"
        :open="removeMemberDialogOpen"
        @update:open="removeMemberDialogOpen = $event"
    />

    <CancelInvitationModal
        :team="team"
        :invitation="invitationToCancel"
        :open="cancelInvitationDialogOpen"
        @update:open="cancelInvitationDialogOpen = $event"
    />

    <DeleteTeamModal
        v-if="permissions.canDeleteTeam && !team.isPersonal"
        :team="team"
        :open="deleteDialogOpen"
        @update:open="deleteDialogOpen = $event"
    />
</template>
