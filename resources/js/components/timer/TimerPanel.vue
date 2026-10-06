<template>
    <div class="w-[min(22rem,calc(100vw-2rem))] bg-white rounded-xl shadow-2xl border border-gray-200 p-4" role="dialog" aria-label="Block timer">
        <TimerFace :timer="timer" :live="live" variant="panel" :busy="busy" :can-pop-out="canPopOut" :today="today"
            @pause="$emit('action', 'pause')" @resume="$emit('action', 'resume')" @stop="$emit('action', 'stop')"
            @discard="$emit('action', 'discard')" @popout="$emit('action', 'popout')" @close="$emit('close')"
            @open-topic="(topic) => $emit('action', 'open-topic', topic)" />
    </div>
</template>

<script setup>
import TimerFace from './TimerFace.vue'

defineProps({
    timer: { type: Object, required: true },
    live: { type: Object, required: true },
    busy: { type: Boolean, default: false },
    canPopOut: { type: Boolean, default: false },
    today: { type: String, default: '' },
})
defineEmits(['action', 'close'])
</script>
