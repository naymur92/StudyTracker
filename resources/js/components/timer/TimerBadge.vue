<template>
    <span v-if="live" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
        :class="store.timer.state === 'paused' ? 'bg-gray-100 text-gray-700' : live.phase === 'break' ? 'bg-amber-100 text-amber-800' : 'bg-primary-100 text-primary-800'">
        <span class="w-1.5 h-1.5 rounded-full" :class="store.timer.state === 'paused' ? 'bg-gray-400' : 'bg-current animate-pulse'"></span>
        {{ store.timer.state === 'paused' ? 'Paused' : live.phase === 'break' ? 'Break' : 'Running' }} · {{ formatClock(live.left) }} left
    </span>
</template>

<script setup>
// Live state of the active timer, shown on its block in the grid and widget.
import { computed, onBeforeUnmount, ref } from 'vue'
import { useBlockTimerStore } from '@/stores/blockTimer'
import { formatClock, liveTimer } from './timerMath'

const store = useBlockTimerStore()
const now = ref(store.serverNow())
const tick = setInterval(() => (now.value = store.serverNow()), 1000)
onBeforeUnmount(() => clearInterval(tick))

const live = computed(() => (store.timer ? liveTimer(store.timer, store.syncedAt, now.value) : null))
</script>
