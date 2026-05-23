<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3'
import { computed } from 'vue'
import { index } from '@/routes/security/grade-configs'
import { useGradeConfigForm } from '@/composables/forms/useGradeConfigForm'
import type { GradeConfig } from '@/types/grade-config'

type Props = {
    config?: GradeConfig
    scaleTypes: Array<{ value: string; label: string }>
    levels: Array<{ value: string; label: string }>
}

const props = defineProps<Props>()
const isEditing = computed(() => !!props.config)

const { form, addSlot, removeSlot, addLetterValue, removeLetterValue, nonRemedialWeightTotal, submit } =
    useGradeConfigForm(props.config)

const weightTotal = computed(() => nonRemedialWeightTotal())
const weightOk = computed(() => Math.abs(weightTotal.value - 100) < 0.01)

setLayoutProps({
    breadcrumbs: [
        { title: 'Seguridad', href: '#' },
        { title: 'Configuración de notas', href: index.url() },
        { title: isEditing.value ? 'Editar' : 'Nueva' },
    ],
})
</script>

<template>
    <Head :title="isEditing ? 'Editar configuración de notas' : 'Nueva configuración de notas'" />

    <div style="max-width:680px;">
        <form @submit.prevent="submit(config?.id)" style="display:flex;flex-direction:column;gap:28px;">

            <!-- Nivel (solo en creación) -->
            <div v-if="!isEditing" style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Nivel educativo</label>
                <select v-model="form.level" style="padding:8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);">
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <span v-if="form.errors.level" style="font-size:var(--text-xs);color:#c0392b;">{{ form.errors.level }}</span>
            </div>

            <!-- Escala -->
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Tipo de escala</label>
                <div style="display:flex;gap:16px;">
                    <label v-for="st in scaleTypes" :key="st.value" style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" :value="st.value" v-model="form.scale_type" />
                        <span style="font-size:var(--text-sm);">{{ st.label }}</span>
                    </label>
                </div>
            </div>

            <!-- Rango numérico -->
            <div v-if="form.scale_type === 'numeric'" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;">
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Nota mínima</label>
                    <input type="number" v-model="form.scale_min" step="0.01"
                        style="padding:8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);" />
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Nota máxima</label>
                    <input type="number" v-model="form.scale_max" step="0.01"
                        style="padding:8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);" />
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Mínimo aprobatorio</label>
                    <input type="number" v-model="form.passing_value" step="0.01"
                        style="padding:8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);" />
                </div>
            </div>

            <!-- Tabla de letras -->
            <div v-if="form.scale_type === 'letter'" style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Equivalencias de letras</label>
                    <button type="button" @click="addLetterValue"
                        style="font-size:var(--text-xs);color:var(--color-terracota);background:none;border:none;cursor:pointer;">
                        + Añadir letra
                    </button>
                </div>
                <div v-if="form.letter_values.length === 0"
                    style="font-size:var(--text-sm);color:var(--text-muted);padding:12px;background:var(--color-papel);border-radius:6px;">
                    Añade las letras de la escala (ej. A=20, B=16…)
                </div>
                <div v-for="(lv, i) in form.letter_values" :key="i"
                    style="display:grid;grid-template-columns:60px 1fr 1fr auto;gap:10px;align-items:center;">
                    <input v-model="lv.letter" placeholder="A" maxlength="2"
                        style="padding:6px 10px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);text-align:center;" />
                    <input v-model.number="lv.numeric_equiv" type="number" step="0.01" placeholder="Equivalencia"
                        style="padding:6px 10px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);" />
                    <label style="display:flex;align-items:center;gap:6px;font-size:var(--text-sm);cursor:pointer;">
                        <input type="checkbox" v-model="lv.is_passing" /> Aprueba
                    </label>
                    <button type="button" @click="removeLetterValue(i)"
                        style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:16px;">✕</button>
                </div>
                <div style="display:flex;flex-direction:column;gap:6px;margin-top:4px;">
                    <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Mínimo aprobatorio (valor numérico)</label>
                    <input type="number" v-model="form.passing_value" step="0.01"
                        style="padding:8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);max-width:160px;" />
                </div>
            </div>

            <!-- Slots de evaluación -->
            <div style="display:flex;flex-direction:column;gap:10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <label style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Evaluaciones</label>
                        <span :style="`margin-left:10px;font-size:var(--text-xs);${weightOk ? 'color:#27ae60' : 'color:#c0392b'}`">
                            Total: {{ weightTotal.toFixed(2) }}% {{ weightOk ? '✓' : '(debe ser 100%)' }}
                        </span>
                    </div>
                    <button type="button" @click="addSlot"
                        style="font-size:var(--text-xs);color:var(--color-terracota);background:none;border:none;cursor:pointer;">
                        + Añadir evaluación
                    </button>
                </div>
                <span v-if="form.errors.slots" style="font-size:var(--text-xs);color:#c0392b;">{{ form.errors.slots }}</span>

                <div v-for="(slot, i) in form.slots" :key="i"
                    style="display:grid;grid-template-columns:1fr 100px 120px auto;gap:10px;align-items:center;">
                    <input v-model="slot.name" placeholder="Nombre (ej. Primer Parcial)"
                        style="padding:8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);" />
                    <div style="position:relative;">
                        <input v-model.number="slot.weight" type="number" step="0.01" min="0" max="100"
                            style="padding:8px 28px 8px 12px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);width:100%;box-sizing:border-box;" />
                        <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:var(--text-sm);color:var(--text-muted);">%</span>
                    </div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:var(--text-sm);cursor:pointer;">
                        <input type="checkbox" v-model="slot.is_remedial" /> Reparación
                    </label>
                    <button type="button" @click="removeSlot(i)"
                        style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:16px;">✕</button>
                </div>
            </div>

            <!-- Acciones -->
            <div style="display:flex;gap:12px;padding-top:8px;border-top:1px solid var(--color-borde);">
                <button type="submit" :disabled="form.processing || !weightOk"
                    style="padding:9px 20px;background:var(--color-terracota);color:white;border:none;border-radius:6px;font-size:var(--text-sm);font-weight:500;cursor:pointer;opacity:1;"
                    :style="{ opacity: (form.processing || !weightOk) ? 0.6 : 1 }">
                    {{ form.processing ? 'Guardando…' : isEditing ? 'Guardar cambios' : 'Crear configuración' }}
                </button>
                <Link :href="index.url()"
                    style="padding:9px 16px;border:1px solid var(--color-borde);border-radius:6px;font-size:var(--text-sm);color:var(--text-secondary);text-decoration:none;">
                    Cancelar
                </Link>
            </div>

        </form>
    </div>
</template>
