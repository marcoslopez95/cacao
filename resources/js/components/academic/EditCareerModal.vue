<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/academic/careers'
import type { Career, CareerCategory } from '@/types/academic'

const props = defineProps<{
    open: boolean
    career: Career
    categories: CareerCategory[]
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
    <Modal :open="open" title="Editar carrera" size="sm" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form(career)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ec-category" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Categoría
                    </label>
                    <select id="ec-category" name="career_category_id" class="input" required>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.id"
                            :selected="cat.id === career.category.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                    <InputError :message="errors.career_category_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ec-name" name="name" class="input" :value="career.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Código
                    </label>
                    <input
                        id="ec-code"
                        name="code"
                        class="input"
                        :value="career.code"
                        maxlength="10"
                        style="text-transform:uppercase;"
                        required
                    />
                    <InputError :message="errors.code" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-active" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Estado
                    </label>
                    <select id="ec-active" name="active" class="input">
                        <option value="1" :selected="career.active">Activa</option>
                        <option value="0" :selected="!career.active">Inactiva</option>
                    </select>
                    <InputError :message="errors.active" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
