<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/academic/careers'
import type { CareerCategory } from '@/types/academic'

defineProps<{
    open: boolean
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
    <Modal :open="open" title="Nueva carrera" size="sm" @update:open="close">
        <Form :key="formKey" v-bind="store.form()" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-category" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Categoría
                    </label>
                    <select id="cc-category" name="career_category_id" class="input" required>
                        <option value="">Seleccionar categoría</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <InputError :message="errors.career_category_id" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cc-name" name="name" class="input" placeholder="Ej: Ingeniería en Sistemas" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-code" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Código
                    </label>
                    <input
                        id="cc-code"
                        name="code"
                        class="input"
                        placeholder="Ej: INF"
                        maxlength="10"
                        style="text-transform:uppercase;"
                        required
                    />
                    <InputError :message="errors.code" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear carrera</Button>
            </div>
        </Form>
    </Modal>
</template>
