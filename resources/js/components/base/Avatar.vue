<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
    src?: string
    initials?: string
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    colorPreset?: 1 | 2 | 3 | 4 | 5
}>(), {
    size: 'md',
    colorPreset: 1,
})

const sizeMap = { xs: 20, sm: 28, md: 36, lg: 48, xl: 64 }
const fontMap = { xs: 9, sm: 11, md: 13, lg: 16, xl: 22 }

const bgMap: Record<number, string> = {
    1: '#F7E4D7',
    2: '#DCE8F2',
    3: '#EAF3DE',
    4: '#EDE0F5',
    5: '#FBF0D3',
}
const fgMap: Record<number, string> = {
    1: '#7A3010',
    2: '#133C58',
    3: '#2E4B12',
    4: '#4A1F6E',
    5: '#6B4500',
}

const px = computed(() => sizeMap[props.size])
const fs = computed(() => fontMap[props.size])
const style = computed(() => ({
    width: `${px.value}px`,
    height: `${px.value}px`,
    fontSize: `${fs.value}px`,
    background: bgMap[props.colorPreset],
    color: fgMap[props.colorPreset],
    borderRadius: '50%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontWeight: 600,
    flexShrink: 0,
    overflow: 'hidden',
}))
</script>

<template>
    <div :style="style">
        <img v-if="src" :src="src" :alt="initials" style="width:100%;height:100%;object-fit:cover;" />
        <span v-else>{{ initials }}</span>
    </div>
</template>
