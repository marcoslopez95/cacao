<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/UI/AppButton.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { resetPassword } from '@/routes/security/users'
import type { UserRow } from '@/types'

const props = defineProps<{
    open: boolean
    user: UserRow
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
        title="Cambiar contraseña"
        size="sm"
        @update:open="close"
    >
        <div style="padding:12px 0 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);margin-bottom:16px;">
            <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent);display:grid;place-items:center;font-weight:600;flex-shrink:0;">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>
            <div>
                <div style="font-weight:500;color:var(--text-primary);">{{ user.name }}</div>
                <div style="font-size:var(--text-xs);color:var(--text-muted);">{{ user.email }}</div>
            </div>
        </div>

        <Form
            :key="formKey"
            v-bind="resetPassword.form(user)"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <input type="hidden" name="password_mode" :value="passwordMode" />

            <div style="display:grid;gap:16px;">
                <div style="display:grid;gap:8px;">
                    <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Método</span>
                    <div style="display:flex;gap:6px;">
                        <button
                            v-for="mode in ([['link','Enviar link'],['manual','Escribir'],['random','Generar']] as const)"
                            :key="mode[0]"
                            type="button"
                            style="flex:1;padding:6px 10px;border-radius:6px;font-size:var(--text-sm);border:1px solid var(--border);cursor:pointer;"
                            :style="passwordMode === mode[0] ? 'background:var(--accent);color:#fff;border-color:var(--accent);' : 'background:var(--bg-soft);color:var(--text-secondary);'"
                            @click="passwordMode = mode[0]"
                        >
                            {{ mode[1] }}
                        </button>
                    </div>
                </div>

                <template v-if="passwordMode === 'manual'">
                    <input name="password" type="password" class="input" placeholder="Nueva contraseña" minlength="8" required />
                    <input name="password_confirmation" type="password" class="input" placeholder="Confirmar" required />
                    <InputError :message="errors.password" />
                </template>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:24px;">
                <Button type="button" variant="ghost" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Aplicar</Button>
            </div>
        </Form>
    </Modal>
</template>
