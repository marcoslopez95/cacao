<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/routes/security/users'

defineProps<{
    open: boolean
    roles: string[]
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)
const passwordMode = ref<'link' | 'manual' | 'random'>('link')

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) {
        formKey.value++
        passwordMode.value = 'link'
    }
}
</script>

<template>
    <Modal
        :open="open"
        title="Nuevo usuario"
        description="Crea una cuenta directamente. El usuario podrá cambiar sus datos después."
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <input type="hidden" name="password_mode" :value="passwordMode" />

            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:6px;">
                    <label
                        for="cu-name"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Nombre completo
                    </label>
                    <input
                        id="cu-name"
                        name="name"
                        class="input"
                        placeholder="María González"
                        required
                    />
                    <InputError :message="errors.name" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label
                        for="cu-email"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Correo institucional
                    </label>
                    <input
                        id="cu-email"
                        name="email"
                        type="email"
                        class="input"
                        placeholder="maria@institucion.edu.ve"
                        required
                    />
                    <InputError :message="errors.email" />
                </div>

                <div style="display:grid;gap:6px;">
                    <label
                        for="cu-role"
                        style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);"
                    >
                        Rol
                    </label>
                    <select id="cu-role" name="role" class="input" required>
                        <option value="">Seleccionar rol...</option>
                        <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
                    </select>
                    <InputError :message="errors.role" />
                </div>

                <div style="display:grid;gap:8px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Contraseña inicial
                    </span>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        <button
                            v-for="[mode, label] in ([['link', 'Enviar link'], ['manual', 'Escribir'], ['random', 'Generar']] as const)"
                            :key="mode"
                            type="button"
                            :style="[
                                'flex:1;padding:6px 10px;border-radius:6px;font-size:var(--text-sm);border:1px solid var(--border);cursor:pointer;transition:all 0.15s;',
                                passwordMode === mode
                                    ? 'background:var(--accent);color:#fff;border-color:var(--accent);'
                                    : 'background:var(--bg-soft);color:var(--text-secondary);',
                            ]"
                            @click="passwordMode = mode"
                        >
                            {{ label }}
                        </button>
                    </div>

                    <p
                        v-if="passwordMode === 'random'"
                        style="font-size:var(--text-xs);color:var(--text-muted);margin:0;"
                    >
                        La contraseña se mostrará una sola vez al confirmar.
                    </p>

                    <template v-if="passwordMode === 'manual'">
                        <input
                            name="password"
                            type="password"
                            class="input"
                            placeholder="Contraseña (mín. 8 caracteres)"
                            minlength="8"
                            required
                        />
                        <input
                            name="password_confirmation"
                            type="password"
                            class="input"
                            placeholder="Confirmar contraseña"
                            required
                        />
                        <InputError :message="errors.password" />
                    </template>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:16px;border-top:1px solid var(--border);">
                    <Button variant="secondary" type="button" @click="close(false)">Cancelar</Button>
                    <Button type="submit" variant="primary" :loading="processing">Crear usuario</Button>
                </div>
            </div>
        </Form>
    </Modal>
</template>
