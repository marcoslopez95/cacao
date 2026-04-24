<script setup lang="ts">
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
};

withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});
</script>

<template>
    <div class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
</template>
