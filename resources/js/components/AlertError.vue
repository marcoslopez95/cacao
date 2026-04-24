<script setup lang="ts">
import { computed } from 'vue';
import Alert from '@/components/feedback/Alert.vue';

type Props = {
    errors: string[];
    title?: string;
};

const props = withDefaults(defineProps<Props>(), {
    title: 'Something went wrong.',
});

const uniqueErrors = computed(() => Array.from(new Set(props.errors)));
</script>

<template>
    <Alert variant="danger" :title="props.title">
        <ul style="list-style:disc;padding-left:1.25rem;font-size:var(--text-sm);">
            <li v-for="(error, index) in uniqueErrors" :key="index">
                {{ error }}
            </li>
        </ul>
    </Alert>
</template>
