<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/infrastructure/classrooms'
import type { Building } from '@/types/infrastructure'

const props = defineProps<{ buildings: Building[]; open: boolean }>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function makeForm() {
    return useForm({
        building_id: null as number | null,
        identifier:  '',
        type:        'theory' as 'theory' | 'laboratory',
        capacity:    30,
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
    <Modal :open="open" title="Nueva aula" size="sm" @update:open="close">
        <form @submit.prevent="submit">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-building" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Edificio
                    </label>
                    <select id="cc-building" v-model.number="form.building_id" class="input" required>
                        <option :value="null" disabled>Selecciona un edificio</option>
                        <option v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <InputError :message="form.errors.building_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-identifier" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Identificador
                    </label>
                    <input
                        id="cc-identifier"
                        v-model="form.identifier"
                        class="input"
                        placeholder="Ej: 301, Lab A"
                        required
                    />
                    <InputError :message="form.errors.identifier" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="cc-type" v-model="form.type" class="input" required>
                        <option value="theory">Teórica</option>
                        <option value="laboratory">Laboratorio</option>
                    </select>
                    <InputError :message="form.errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-capacity" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Capacidad
                    </label>
                    <input
                        id="cc-capacity"
                        v-model.number="form.capacity"
                        type="number"
                        min="1"
                        max="500"
                        class="input"
                        required
                    />
                    <InputError :message="form.errors.capacity" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="form.processing">Crear aula</Button>
            </div>
        </form>
    </Modal>
</template>
