<script setup lang="ts">
import AuthSplitLayout from '@/layouts/auth/AuthSplitLayout.vue'

defineProps<{
    title?: string
    description?: string
    panelQuote?: string
    panelHighlight?: string
    panelRole?: string
    panelContext?: string
}>()
</script>

<template>
    <AuthSplitLayout
        :title="title"
        :description="description"
        :panel-quote="panelQuote"
        :panel-highlight="panelHighlight"
        :panel-role="panelRole"
        :panel-context="panelContext"
    >
        <slot />
    </AuthSplitLayout>
</template>
