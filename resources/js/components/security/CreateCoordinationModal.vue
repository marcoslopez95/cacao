<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { store } from '@/routes/security/coordinations'

defineProps<{
    open: boolean
    careers: { id: number; name: string }[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)
const selectedType = ref('')
const selectedSecondaryType = ref('')

const gradeYearMax = computed(() => (selectedSecondaryType.value === 'bachillerato' ? 6 : 5))

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
        selectedType.value = ''
        selectedSecondaryType.value = ''
    }
}
</script>

<template>
    <Modal :open="open" title="Nueva coordinación" size="md" @update:open="close">
        <Form :key="formKey" v-bind="store.form()" v-slot="{ errors, processing }" @success="close(false)">
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="cc-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="cc-name" name="name" class="input" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="cc-type" v-model="selectedType" name="type" class="input" required>
                        <option value="" disabled>Seleccionar tipo...</option>
                        <option value="career">Carrera</option>
                        <option value="grade">Año escolar</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="cc-level" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nivel educativo
                    </label>
                    <select id="cc-level" name="education_level" class="input" required>
                        <option value="" disabled>Seleccionar nivel...</option>
                        <option value="university">Universitario</option>
                        <option value="secondary">Media / Básica</option>
                    </select>
                    <InputError :message="errors.education_level" />
                </div>

                <template v-if="selectedType === 'grade'">
                    <div style="display:grid;gap:6px;">
                        <label for="cc-secondary-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Modalidad
                        </label>
                        <select id="cc-secondary-type" v-model="selectedSecondaryType" name="secondary_type" class="input" required>
                            <option value="" disabled>Seleccionar modalidad...</option>
                            <option value="media_general">Media General</option>
                            <option value="bachillerato">Bachillerato</option>
                        </select>
                        <InputError :message="errors.secondary_type" />
                    </div>

                    <div style="display:grid;gap:6px;">
                        <label for="cc-grade-year" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Año (1–{{ gradeYearMax }})
                        </label>
                        <select id="cc-grade-year" name="grade_year" class="input" required>
                            <option value="" disabled>Seleccionar año...</option>
                            <option v-for="n in gradeYearMax" :key="n" :value="n">{{ n }}°</option>
                        </select>
                        <InputError :message="errors.grade_year" />
                    </div>
                </template>

                <template v-if="selectedType === 'career'">
                    <div style="display:grid;gap:6px;">
                        <label for="cc-career" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Carrera
                        </label>
                        <select id="cc-career" name="career_id" class="input">
                            <option value="">Sin carrera asignada</option>
                            <option v-for="career in careers" :key="career.id" :value="career.id">
                                {{ career.name }}
                            </option>
                        </select>
                        <InputError :message="errors.career_id" />
                    </div>
                </template>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Crear coordinación</Button>
            </div>
        </Form>
    </Modal>
</template>
