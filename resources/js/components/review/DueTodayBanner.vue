<template>
    <div v-if="load" :class="['rounded-lg border p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3', tone]">
        <div>
            <p class="font-semibold" v-if="load.due_topics > 0">
                Due today: {{ load.due_topics }} {{ load.due_topics === 1 ? 'topic' : 'topics' }} ≈ {{ load.estimated_minutes }} min
                <span v-if="load.overdue_topics > 0" class="font-normal">({{ load.overdue_topics }} overdue)</span>
            </p>
            <p class="font-semibold" v-else>Nothing due today — your reviews are up to date.</p>
            <p v-if="load.over_budget" class="text-sm mt-1">
                Over your {{ load.budget_minutes }}-minute budget. Do the oldest reviews first and let the rest carry
                over to tomorrow.
            </p>
            <p v-else-if="load.due_topics > 0" class="text-sm mt-1 opacity-80">
                Within your {{ load.budget_minutes }}-minute daily budget.
            </p>
        </div>
        <div class="flex gap-2" v-if="showStart && load.due_topics > 0">
            <router-link to="/app/review" class="btn-primary whitespace-nowrap">Start review</router-link>
        </div>
        <slot />
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    load: { type: Object, default: null },
    showStart: { type: Boolean, default: true },
})

const tone = computed(() => {
    if (!props.load || props.load.due_topics === 0) return 'bg-success-50 border-success-200 text-success-800'
    return props.load.over_budget ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-primary-50 border-primary-200 text-primary-900'
})
</script>
