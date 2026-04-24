<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import Modal from '@/components/feedback/Modal.vue'
import { update } from '@/routes/security/coordinations'
import type { CoordinationRow } from '@/types/security'

const props = defineProps<{
    open: boolean
    coordination: CoordinationRow
    careers: { id: number; name: string }[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const formKey = ref(0)
const selectedType = ref(props.coordination.type)
const selectedSecondaryType = ref(props.coordination.secondary_type ?? '')

const gradeYearMax = computed(() => (selectedSecondaryType.value === 'bachillerato' ? 6 : 5))

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal :open="open" title="Editar coordinación" size="md" @update:open="close">
        <Form
            :key="formKey"
            v-bind="update.form(coordination)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label for="ec-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nombre
                    </label>
                    <input id="ec-name" name="name" class="input" :value="coordination.name" required />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Tipo
                    </label>
                    <select id="ec-type" v-model="selectedType" name="type" class="input" required>
                        <option value="career">Carrera</option>
                        <option value="grade">Año escolar</option>
                    </select>
                    <InputError :message="errors.type" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label for="ec-level" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Nivel educativo
                    </label>
                    <select id="ec-level" name="education_level" class="input" :value="coordination.education_level" required>
                        <option value="university">Universitario</option>
                        <option value="secondary">Media / Básica</option>
                    </select>
                    <InputError :message="errors.education_level" />
                </div>

                <template v-if="selectedType === 'grade'">
                    <div style="display:grid;gap:6px;">
                        <label for="ec-secondary-type" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Modalidad
                        </label>
                        <select id="ec-secondary-type" v-model="selectedSecondaryType" name="secondary_type" class="input">
                            <option value="media_general">Media General</option>
                            <option value="bachillerato">Bachillerato</option>
                        </select>
                        <InputError :message="errors.secondary_type" />
                    </div>

                    <div style="display:grid;gap:6px;">
                        <label for="ec-grade-year" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Año (1–{{ gradeYearMax }})
                        </label>
                        <select id="ec-grade-year" name="grade_year" class="input" :value="coordination.grade_year">
                            <option v-for="n in gradeYearMax" :key="n" :value="n">{{ n }}°</option>
                        </select>
                        <InputError :message="errors.grade_year" />
                    </div>
                </template>

                <template v-if="selectedType === 'career'">
                    <div style="display:grid;gap:6px;">
                        <label for="ec-career" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                            Carrera
                        </label>
                        <select id="ec-career" name="career_id" class="input" :value="coordination.career_id">
                            <option :value="null">Sin carrera asignada</option>
                            <option v-for="career in careers" :key="career.id" :value="career.id">
                                {{ career.name }}
                            </option>
                        </select>
                        <InputError :message="errors.career_id" />
                    </div>
                </template>

                <div style="display:grid;gap:6px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;">
                        <input
                            type="checkbox"
                            name="active"
                            :value="true"
                            :checked="coordination.active"
                            style="width:14px;height:14px;accent-color:var(--accent);"
                        />
                        <span style="font-weight:500;color:var(--text-primary);">Coordinación activa</span>
                    </label>
                </div>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar cambios</Button>
            </div>
        </Form>
    </Modal>
</template>
