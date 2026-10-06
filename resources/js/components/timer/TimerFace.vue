<template>
    <div class="space-y-3" :class="variant === 'popout' ? 'p-3' : ''">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide" :class="phaseText">
                    {{ timer.state === 'paused' ? 'Paused' : live.phase === 'break' ? 'Break' : 'Focus' }}
                </p>
                <p class="font-semibold text-gray-900 truncate">{{ name }}</p>
                <p class="text-xs text-gray-500">{{ day }} · <span class="capitalize">{{ block.lane }}</span> lane</p>
            </div>
            <span v-if="variant === 'panel'" class="flex gap-1 shrink-0">
                <button v-if="canPopOut" type="button" @click="$emit('popout')" class="px-2 py-1 rounded text-xs text-gray-600 hover:bg-gray-100" title="Keep the timer above other apps">Pop out</button>
                <button type="button" @click="$emit('close')" class="px-2 py-1 rounded text-gray-500 hover:bg-gray-100" aria-label="Close timer details">✕</button>
            </span>
        </div>

        <div class="text-center">
            <p class="font-mono font-bold text-gray-900 tabular-nums" :class="variant === 'popout' ? 'text-4xl' : 'text-5xl'">{{ formatClock(live.left) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ formatClock(live.used) }} used of {{ Math.round(timer.planned_seconds / 60) }} min</p>
        </div>

        <div class="h-2 rounded-full bg-gray-100 overflow-hidden" role="progressbar" :aria-valuenow="Math.round(live.progress * 100)" aria-valuemin="0" aria-valuemax="100">
            <div class="h-full transition-all" :class="phaseBar" :style="{ width: `${Math.min(100, live.progress * 100)}%` }"></div>
        </div>

        <p class="text-sm" :class="live.phase === 'break' ? 'text-amber-800' : 'text-gray-700'">
            <template v-if="live.phase === 'break'">Break — {{ formatClock(live.phaseLeft) }} left. Stand up and look away.</template>
            <template v-else-if="live.nextBreakAt !== null">Next break in {{ formatClock(live.nextBreakAt - live.used) }} ({{ timer.break_minutes }} min)</template>
            <template v-else-if="timer.break_every_minutes">No more breaks in this block.</template>
            <template v-else>No breaks planned.</template>
        </p>

        <div v-if="variant === 'panel'" class="rounded-lg bg-gray-50 p-3 text-sm space-y-1">
            <p><span class="text-gray-500">Task:</span> {{ block.planned_task || 'No task decided' }}</p>
            <p v-if="block.topic">
                <span class="text-gray-500">Topic:</span>
                <a href="#" class="text-primary-700 underline" @click.prevent="$emit('open-topic', block.topic)">{{ block.topic.title }}</a>
            </p>
            <p v-else-if="block.lane === 'review'" class="text-gray-500">Working through due reviews.</p>
        </div>
        <p v-else class="text-xs text-gray-600 truncate" :title="block.planned_task">{{ block.planned_task || block.topic?.title || '' }}</p>

        <div class="flex gap-2">
            <button v-if="timer.state === 'running'" type="button" @click="$emit('pause')" :disabled="busy" class="flex-1 btn-secondary py-1.5 text-sm">Pause</button>
            <button v-else type="button" @click="$emit('resume')" :disabled="busy || !canResume" :title="canResume ? '' : 'A paused timer can be resumed only on its day'" class="flex-1 btn-primary py-1.5 text-sm disabled:opacity-50">Resume</button>
            <button type="button" @click="onStop" :disabled="busy" class="flex-1 py-1.5 text-sm rounded-lg font-medium" :class="armedStop ? 'bg-red-600 text-white' : 'bg-gray-800 text-white hover:bg-gray-900'">
                {{ armedStop ? 'Tap to confirm' : 'Stop' }}
            </button>
            <button v-if="variant === 'panel'" type="button" @click="$emit('discard')" :disabled="busy" class="px-3 py-1.5 text-sm rounded-lg text-red-700 hover:bg-red-50">Discard</button>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { format } from 'date-fns'
import { parseLocalDate } from '@/helpers/dates'
import { slotLabels } from '@/components/weekly/weeklyMeta'
import { formatClock } from './timerMath'

const props = defineProps({
    timer: { type: Object, required: true },
    live: { type: Object, required: true },
    variant: { type: String, default: 'panel' }, // panel | popout
    busy: { type: Boolean, default: false },
    canPopOut: { type: Boolean, default: false },
    today: { type: String, default: '' },
})

const emit = defineEmits(['pause', 'resume', 'stop', 'discard', 'popout', 'close', 'open-topic'])

const block = computed(() => props.timer.block || {})
const name = computed(() => slotLabels[block.value.slot] || 'Block')
const day = computed(() => (block.value.block_date ? format(parseLocalDate(block.value.block_date), 'EEE d MMM') : ''))
const canResume = computed(() => !props.today || block.value.block_date === props.today)

const phaseText = computed(() => (props.timer.state === 'paused' ? 'text-gray-500' : props.live.phase === 'break' ? 'text-amber-600' : 'text-primary-600'))
const phaseBar = computed(() => (props.timer.state === 'paused' ? 'bg-gray-400' : props.live.phase === 'break' ? 'bg-amber-500' : 'bg-primary-600'))

// The panel confirms in the page (SweetAlert); the pop-out window has no
// dialogs, so Stop there needs a second tap within 3 seconds.
const armedStop = ref(false)
let disarm = null
const onStop = () => {
    if (props.variant === 'panel' || armedStop.value) {
        armedStop.value = false
        clearTimeout(disarm)
        emit('stop')
        return
    }
    armedStop.value = true
    disarm = setTimeout(() => (armedStop.value = false), 3000)
}
</script>
