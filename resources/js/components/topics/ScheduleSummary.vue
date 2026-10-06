<template>
    <span>{{ text }}</span>
</template>

<script setup>
import { computed } from 'vue'
import { format } from 'date-fns'

const props = defineProps({
    offsets: { type: Array, default: () => [] },
    repeatEveryDays: { type: Number, default: null },
    repeatUntil: { type: String, default: null },
})

const describeRepeat = (days) => {
    if (days === 7) return 'weekly'
    if (days === 1) return 'daily'
    return `every ${days} days`
}

const text = computed(() => {
    if (!props.offsets?.length) return 'Default schedule'
    let out = `Reviews at ${props.offsets.map((d) => `+${d}`).join(', ')} days`
    if (props.repeatEveryDays) {
        out += `, then ${describeRepeat(props.repeatEveryDays)}`
        if (props.repeatUntil) out += ` until ${format(new Date(`${props.repeatUntil}T00:00:00`), 'd MMM yyyy')}`
    }
    return out
})
</script>
