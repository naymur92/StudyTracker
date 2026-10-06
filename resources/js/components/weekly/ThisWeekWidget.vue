<template>
    <div class="card p-6 space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-xl font-bold text-gray-900">This week</h2>
            <router-link to="/app/week" class="text-sm text-primary-600 hover:underline">Weekly plan →</router-link>
        </div>

        <div v-if="!week" class="text-gray-500 text-sm">Loading…</div>

        <div v-else-if="!week.plan" class="text-sm text-gray-700">
            No plan for this week yet.
            <router-link to="/app/week" class="font-semibold text-primary-700 underline">Plan this week</router-link>
            — pick a gear and pre-decide your blocks.
        </div>

        <template v-else>
            <div class="flex items-center gap-3">
                <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold', gear.chip]" :title="gear.desc">{{ gear.label }}<template v-if="gear.hours"> · {{ gear.hours }}</template></span>
                <div class="flex-1">
                    <ScoreBar :percent="week.score.percent" :threshold="week.success_threshold_percent" :on-track="week.score.on_track" />
                </div>
            </div>

            <div class="rounded-lg bg-primary-50 border border-primary-200 p-3 text-sm text-primary-900">
                <span class="font-semibold">Next up: </span>
                <template v-if="nextUp">{{ slotLabels[nextUp.slot] }} — {{ nextUp.planned_task }}</template>
                <template v-else-if="hasDueReviews">
                    nothing pre-decided —
                    <router-link to="/app/review" class="underline font-semibold">start with your oldest due review</router-link>
                </template>
                <template v-else>nothing pre-decided. Write tomorrow's first task in the Weekly Plan tonight.</template>
            </div>

            <div v-if="todayBlocks.length" class="space-y-2">
                <div v-for="block in todayBlocks" :key="block.id" class="flex items-center justify-between gap-2 border border-gray-200 rounded-lg px-3 py-2">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ slotLabels[block.slot] }} <span class="text-xs text-gray-500">· {{ block.planned_minutes || '–' }} min</span></p>
                        <p class="text-xs text-gray-600 truncate">{{ block.planned_task || 'No task decided' }}</p>
                    </div>
                    <span class="flex gap-1 shrink-0">
                        <button v-for="s in quickStatuses" :key="s.value" @click="mark(block, s.value)" :title="s.label"
                            :class="['w-7 h-7 rounded text-xs font-bold', block.status === s.value ? s.style : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">{{ s.short }}</button>
                    </span>
                </div>
            </div>
            <p v-else class="text-sm text-gray-500">No blocks planned for today.</p>
        </template>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useWeeklyPlanStore } from '@/stores/weeklyPlan'
import { todayLocal } from '@/helpers/dates'
import { showError } from '@/helpers/alerts'
import { gearsWithOptions, slotLabels, statuses } from '@/components/weekly/weeklyMeta'
import ScoreBar from '@/components/weekly/ScoreBar.vue'

const props = defineProps({
    reviewLoad: { type: Object, default: null },
})

const authStore = useAuthStore()
const store = useWeeklyPlanStore()
const week = ref(null)
const today = todayLocal()

const quickStatuses = statuses.filter((s) => s.value !== 'red')
const gear = computed(() => gearsWithOptions(week.value?.gear_options || []).find((g) => g.value === week.value?.plan?.gear) || gearsWithOptions()[0])
const todayBlocks = computed(() => (week.value?.blocks || []).filter((b) => b.block_date === today))
const nextUp = computed(() => todayBlocks.value.find((b) => b.status === 'planned' && b.planned_task))
const hasDueReviews = computed(() => (props.reviewLoad?.due_topics || 0) > 0)

const load = async () => {
    try {
        week.value = await store.fetchWeek(authStore.getApiClient(), today)
    } catch {
        week.value = { plan: null }
    }
}

const mark = async (block, status) => {
    try {
        await store.updateBlock(authStore.getApiClient(), block.id, { status: block.status === status ? 'planned' : status })
        await load()
    } catch (err) {
        await showError(err.response?.data?.msg || 'Failed to update the block')
    }
}

onMounted(load)
</script>
