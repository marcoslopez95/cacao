<script setup lang="ts">
defineOptions({ inheritAttrs: false });
</script>

<template>
    <div class="grid grid-cols-3 gap-0.75 w-6" role="img" aria-label="CACAO" v-bind="$attrs">
        <span class="rounded-xs aspect-square bg-foreground"></span>
        <span class="rounded-xs aspect-square bg-foreground"></span>
        <span class="rounded-xs aspect-square bg-terracota dark:bg-terra-hover"></span>
        <span class="rounded-xs aspect-square bg-foreground"></span>
        <span class="rounded-xs aspect-square bg-foreground"></span>
        <span class="invisible aspect-square"></span>
        <span class="rounded-xs aspect-square bg-foreground"></span>
        <span class="rounded-xs aspect-square bg-foreground"></span>
        <span class="rounded-xs aspect-square bg-foreground"></span>
    </div>
</template>
