<script setup lang="ts">
import { ref, useTemplateRef } from 'vue';
import Icon from '@/components/UI/AppIcon.vue';

defineOptions({ inheritAttrs: false });

const showPassword = ref(false);
const inputRef = useTemplateRef<HTMLInputElement>('inputRef');

defineExpose({
    $el: inputRef,
    focus: () => inputRef.value?.focus(),
});
</script>

<template>
    <div style="position:relative;">
        <input
            ref="inputRef"
            :type="showPassword ? 'text' : 'password'"
            class="input"
            style="padding-right: 2.5rem;"
            v-bind="$attrs"
        />
        <button
            type="button"
            @click="showPassword = !showPassword"
            style="position:absolute;top:0;right:0;height:100%;display:flex;align-items:center;padding:0 0.75rem;color:var(--text-muted);background:transparent;border:none;cursor:pointer;"
            :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
            :tabindex="-1"
        >
            <Icon :name="showPassword ? 'eyeOff' : 'eye'" :size="16" />
        </button>
    </div>
</template>
