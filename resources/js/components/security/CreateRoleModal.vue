<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
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
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { store } from '@/routes/security/roles';
import { groupPermissions, permissionGroupLabel } from '@/utils/permissions';

type Props = {
    permissions: string[];
    canAssignPermissions?: boolean;
};

withDefaults(defineProps<Props>(), {
    canAssignPermissions: false,
});

const open = ref(false);
const formKey = ref(0);

function handleOpenChange(value: boolean) {
    open.value = value;
    if (!value) {
        formKey.value++;
    }
}

</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <Form
                :key="formKey"
                v-bind="store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader>
                    <DialogTitle>Nuevo rol</DialogTitle>
                    <DialogDescription>
                        Crea un nuevo rol para asignar a los usuarios del sistema.
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        name="name"
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
                                    :id="permission"
                                    type="checkbox"
                                    name="permissions[]"
                                    :value="permission"
                                    class="h-4 w-4 rounded border-gris-borde"
                                />
                                <label :for="permission" class="text-sm">
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
                        Guardar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
