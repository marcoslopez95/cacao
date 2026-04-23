<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { destroy } from '@/routes/security/roles';
import type { Role } from '@/types';

type Props = {
    role: Role;
    open: boolean;
};

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const formKey = ref(0);

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
            <template v-if="props.role.usersCount > 0">
                <DialogHeader>
                    <DialogTitle>¿Eliminar rol?</DialogTitle>
                    <DialogDescription>
                        Este rol tiene <strong>{{ props.role.usersCount }}</strong>
                        {{ props.role.usersCount === 1 ? 'usuario asignado' : 'usuarios asignados' }}
                        y no puede eliminarse.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="secondary" @click="handleOpenChange(false)">Cerrar</Button>
                </DialogFooter>
            </template>

            <Form
                v-else
                :key="formKey"
                v-bind="destroy.form(props.role.id)"
                class="space-y-6"
                v-slot="{ processing }"
                @success="handleOpenChange(false)"
            >
                <DialogHeader>
                    <DialogTitle>¿Eliminar rol?</DialogTitle>
                    <DialogDescription>
                        Esta acción eliminará el rol
                        <strong>"{{ props.role.name }}"</strong>
                        permanentemente. Esta acción no se puede deshacer.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button type="submit" variant="destructive" :disabled="processing">
                        Eliminar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
