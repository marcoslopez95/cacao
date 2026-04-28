<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/scheduling/professors'
import type { AvailableUser } from '@/types/scheduling'

const props = defineProps<{ open: boolean; availableUsers: AvailableUser[] }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        user_id:           null as number | null,
        weekly_hour_limit: 20,
    })
}

const form = ref(makeForm())

function close(v: boolean): void {
    emit('update:open', v)
}

watch(
    () => props.open,
    (opened) => {
        if (opened) {
            form.value = makeForm()
        }
    },
)

function submit(): void {
    form.value.post(store.url(), { onSuccess: () => close(false) })
}
</script>

<template>
    <Modal :open="open" title="Nuevo profesor" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cp-user" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Usuario
                    </label>
                    <select id="cp-user" v-model="form.user_id" class="input" required>
                        <option :value="null" disabled>Selecciona un usuario</option>
                        <option v-for="u in availableUsers" :key="u.id" :value="u.id">
                            {{ u.name }} — {{ u.email }}
                        </option>
                    </select>
                    <InputError :message="form.errors.user_id" />
                </div>
                <div style="display:grid;gap:6px;">
                    <label for="cp-hours" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Límite horas/semana
                    </label>
                    <input id="cp-hours" v-model.number="form.weekly_hour_limit" type="number" min="1" max="60" class="input" required />
                    <InputError :message="form.errors.weekly_hour_limit" />
                </div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear profesor</Button>
            </div>
        </form>
    </Modal>
</template>
