<template>
    <div class="space-y-1">
        <div class="relative h-3 rounded-full bg-gray-200 overflow-hidden">
            <div class="h-full rounded-full transition-all" :class="onTrack ? 'bg-success-500' : 'bg-amber-500'"
                :style="{ width: `${Math.min(percent ?? 0, 100)}%` }"></div>
            <div class="absolute top-0 h-full w-0.5 bg-gray-800" :style="{ left: `${threshold}%` }" :title="`Success line ${threshold}%`"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-600">
            <span>{{ percent === null ? 'Nothing counted yet' : `${percent}% of counted blocks` }}</span>
            <span>Success line {{ threshold }}%</span>
        </div>
    </div>
</template>

<script setup>
defineProps({
    percent: { type: Number, default: null },
    threshold: { type: Number, default: 80 },
    onTrack: { type: Boolean, default: false },
})
</script>
