<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/academic/pensums'
import type { Career } from '@/types/academic'

defineProps<{
    open: boolean
    career: Career
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
    }
}
</script>

<template>
    <Modal :open="open" title="Nuevo pensum" size="sm" @update:open="close">
        <Form :key="formKey" v-bind="store.form(career)" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cp-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cp-name" name="name" class="input" placeholder="Ej: Plan de Estudios 2024" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cp-period-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo de período
                    </label>
                    <select id="cp-period-type" name="period_type" class="input" required>
                        <option value="">Seleccionar tipo</option>
                        <option value="semester">Semestral</option>
                        <option value="year">Anual</option>
                    </select>
                    <InputError :message="errors.period_type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cp-total-periods" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Total de períodos
                    </label>
                    <input id="cp-total-periods" name="total_periods" type="number" min="1" max="20" class="input" placeholder="Ej: 10" required />
                    <InputError :message="errors.total_periods" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cp-active" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Estado
                    </label>
                    <select id="cp-active" name="is_active" class="input">
                        <option value="1" selected>Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                    <InputError :message="errors.is_active" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear pensum</Button>
            </div>
        </Form>
    </Modal>
</template>
