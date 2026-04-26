<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/academic/pensums'
import type { Career, Pensum } from '@/types/academic'

const props = defineProps<{
    open: boolean
    career: Career
    pensum: Pensum
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
    <Modal :open="open" title="Editar pensum" size="sm" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form({ career, pensum })"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ep-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ep-name" name="name" class="input" :value="props.pensum.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ep-period-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo de período
                    </label>
                    <select id="ep-period-type" name="period_type" class="input" required>
                        <option value="semester" :selected="props.pensum.periodType === 'semester'">Semestral</option>
                        <option value="year" :selected="props.pensum.periodType === 'year'">Anual</option>
                    </select>
                    <InputError :message="errors.period_type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ep-total-periods" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Total de períodos
                    </label>
                    <input id="ep-total-periods" name="total_periods" type="number" min="1" max="20" class="input" :value="props.pensum.totalPeriods" required />
                    <InputError :message="errors.total_periods" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ep-active" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Estado
                    </label>
                    <select id="ep-active" name="is_active" class="input">
                        <option value="1" :selected="props.pensum.isActive">Activo</option>
                        <option value="0" :selected="!props.pensum.isActive">Inactivo</option>
                    </select>
                    <InputError :message="errors.is_active" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
