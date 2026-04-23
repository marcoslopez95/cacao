<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { update } from '@/routes/security/roles';
import type { Role } from '@/types';
import { groupPermissions, permissionGroupLabel } from '@/utils/permissions';

type Props = {
    role: Role;
    permissions: string[];
    canAssignPermissions: boolean;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const formKey = ref(0);
const checkedPerms = ref<string[]>([...props.role.permissions]);

watch(
    [() => props.open, () => props.role.id],
    ([open]) => {
        if (open) {
            checkedPerms.value = [...props.role.permissions];
        }
    },
);

function handleOpenChange(value: boolean) {
    emit('update:open', value);
    if (!value) {
        formKey.value++;
    }
}

</script>

<template>
    <Dialog :open="props.open" @update:open="handleOpenChange">
        <DialogContent>
            <template v-if="props.role.isAdmin">
                <DialogHeader>
                    <DialogTitle>Editar rol</DialogTitle>
                    <DialogDescription>
                        El rol <strong>Admin</strong> no puede ser modificado.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="secondary" @click="handleOpenChange(false)">Cerrar</Button>
                </DialogFooter>
            </template>

            <Form
                v-else
                :key="formKey"
                v-bind="update.form(props.role.id)"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="handleOpenChange(false)"
            >
                <DialogHeader>
                    <DialogTitle>Editar rol</DialogTitle>
                    <DialogDescription>
                        Actualiza el nombre y los permisos del rol.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="edit-name">Nombre</Label>
                    <Input
                        id="edit-name"
                        name="name"
                        :default-value="props.role.name"
                        placeholder="Nombre del rol"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div v-if="canAssignPermissions && permissions.length > 0" class="grid gap-3">
                    <Label>Permisos</Label>
                    <div
                        v-for="(groupPerms, group) in groupPermissions(permissions)"
                        :key="group"
                        class="space-y-1"
                    >
                        <p class="text-sm font-medium text-muted-foreground">
                            {{ permissionGroupLabel(group) }}
                        </p>
                        <div class="ml-3 space-y-1">
                            <div
                                v-for="permission in groupPerms"
                                :key="permission"
                                class="flex items-center gap-2"
                            >
                                <input
                                    :id="`edit-${permission}`"
                                    v-model="checkedPerms"
                                    type="checkbox"
                                    name="permissions[]"
                                    :value="permission"
                                    class="h-4 w-4 rounded border-gris-borde"
                                />
                                <label :for="`edit-${permission}`" class="text-sm">
                                    {{ permission }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button type="submit" :disabled="processing">
                        Guardar cambios
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
