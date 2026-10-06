<template>
    <div class="min-h-screen bg-white text-gray-900">
        <TimerFace v-if="store.timer && live" :timer="store.timer" :live="live" variant="popout" :busy="busy" :today="today"
            @pause="act('pause')" @resume="act('resume')" @stop="act('stop-confirmed')" />
        <p v-else class="p-4 text-sm text-gray-600">No block is running.</p>
    </div>
</template>

<script setup>
// Root of the always-on-top Document Picture-in-Picture window. It shares the
// page's Pinia instance, so it stays in step with the in-page timer.
import { computed, onBeforeUnmount, ref } from 'vue'
import { useBlockTimerStore } from '@/stores/blockTimer'
import TimerFace from './TimerFace.vue'
import { liveTimer } from './timerMath'

const props = defineProps({
    win: { type: Object, required: true }, // the pop-out window
    onAction: { type: Function, required: true },
    today: { type: String, default: '' },
})

const store = useBlockTimerStore()
const now = ref(store.serverNow())
const busy = ref(false)
const live = computed(() => liveTimer(store.timer, store.syncedAt, now.value))

// Tick from the pop-out window itself: it stays visible (and unthrottled)
// while the page's tab is hidden behind another app.
const tick = props.win.setInterval(() => (now.value = store.serverNow()), 1000)
onBeforeUnmount(() => props.win.clearInterval(tick))

const act = async (name) => {
    busy.value = true
    try {
        await props.onAction(name)
    } finally {
        busy.value = false
    }
}
</script>
