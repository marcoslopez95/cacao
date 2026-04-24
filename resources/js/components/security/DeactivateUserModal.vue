<script setup lang="ts">
import type { UserRow } from '@/types'
defineProps<{ open: boolean; user: UserRow }>()
defineEmits<{ 'update:open': [v: boolean] }>()
</script>
<template><div /></template>
