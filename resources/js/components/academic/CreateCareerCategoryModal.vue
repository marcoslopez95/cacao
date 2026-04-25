<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/academic/career-categories'

defineProps<{ open: boolean }>()

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
    <Modal :open="open" title="Nueva categoría" size="sm" @update:open="close">
        <Form :key="formKey" v-bind="store.form()" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cc-name" name="name" class="input" required />
                    <InputError :message="errors.name" />
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear categoría</Button>
            </div>
        </Form>
    </Modal>
</template>
