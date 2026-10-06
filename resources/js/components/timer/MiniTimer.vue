<template>
    <button type="button" @click="$emit('toggle')"
        class="flex items-center gap-2 pl-3 pr-4 py-2 rounded-full shadow-lg border text-sm font-semibold bg-white hover:shadow-xl transition-shadow"
        :class="ring" :aria-expanded="open" aria-label="Running block timer — show details">
        <span class="w-2.5 h-2.5 rounded-full" :class="[dot, timer.state === 'running' ? 'animate-pulse' : '']"></span>
        <span class="text-gray-700 max-w-[9rem] truncate">{{ name }}</span>
        <span class="font-mono tabular-nums text-gray-900">{{ formatClock(live.left) }}</span>
        <span v-if="timer.state === 'paused'" class="text-xs font-medium text-gray-500">paused</span>
        <span v-else-if="live.phase === 'break'" class="text-xs font-medium text-amber-700">break</span>
    </button>
</template>

<script setup>
import { computed } from 'vue'
import { slotLabels } from '@/components/weekly/weeklyMeta'
import { formatClock } from './timerMath'

const props = defineProps({
    timer: { type: Object, required: true },
    live: { type: Object, required: true },
    open: { type: Boolean, default: false },
})
defineEmits(['toggle'])

const name = computed(() => slotLabels[props.timer.block?.slot] || 'Block')
const dot = computed(() => (props.timer.state === 'paused' ? 'bg-gray-400' : props.live.phase === 'break' ? 'bg-amber-500' : 'bg-primary-600'))
const ring = computed(() => (props.live.phase === 'break' && props.timer.state === 'running' ? 'border-amber-300' : 'border-gray-200'))
</script>
