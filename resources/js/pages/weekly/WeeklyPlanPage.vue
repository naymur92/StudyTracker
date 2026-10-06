<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Weekly Plan</h1>
                <p class="text-gray-600">Choose the week's gear, pre-decide your blocks, and score the week against your success line.</p>
            </div>
        </div>

        <!-- Week navigation -->
        <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
            <button @click="shiftWeek(-7)" class="p-2 rounded-lg hover:bg-gray-100" aria-label="Previous week">←</button>
            <div class="text-center">
                <p class="font-semibold text-gray-900" v-if="data">{{ rangeLabel }}</p>
                <button v-if="!isCurrentWeek" @click="goToToday" class="text-xs text-primary-600 hover:underline">This week</button>
            </div>
            <button @click="shiftWeek(7)" class="p-2 rounded-lg hover:bg-gray-100" aria-label="Next week">→</button>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- No plan yet -->
        <div v-else-if="data && !data.plan" class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-xl font-bold text-gray-900">Plan this week</h2>
            <p class="text-gray-600 text-sm">
                Pick a gear before the week starts. Blocks are generated from your {{ terms.workdays }} and
                {{ terms.offs }} (Study Settings<span v-if="data.study_profile === 'student'"> — student profile</span>).
            </p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label v-for="g in gearList" :key="g.value" class="p-4 border-2 rounded-lg cursor-pointer"
                    :class="newPlan.gear === g.value ? g.soft : 'border-gray-200'">
                    <input type="radio" class="sr-only" :value="g.value" v-model="newPlan.gear" />
                    <span class="flex items-center justify-between"><span class="font-semibold">{{ g.label }}</span><span class="text-sm text-gray-600">{{ g.hours }}</span></span>
                    <span class="block text-xs text-gray-600 mt-1">{{ g.desc }}</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <input v-model="newPlan.major_focus" maxlength="200" class="input-base" placeholder="Major focus (e.g. IELTS Writing)" />
                <input v-model="newPlan.minor_focus" maxlength="200" class="input-base" placeholder="Minor focus (e.g. one paper a week)" />
            </div>
            <button class="btn-primary" :disabled="saving" @click="createPlan">Create plan</button>
        </div>

        <template v-else-if="data && data.plan">
            <!-- Gear, focus and score -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="bg-white rounded-lg shadow p-5 space-y-3">
                    <p class="text-sm font-semibold text-gray-900">Gear</p>
                    <div class="flex gap-2">
                        <button v-for="g in gearList" :key="g.value" @click="changeGear(g.value)" :title="`${g.desc} (${g.hours})`"
                            :class="['px-3 py-1.5 rounded-lg text-sm font-semibold border', data.plan.gear === g.value ? g.chip : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50']">
                            {{ g.label }} <span class="font-normal opacity-80">{{ g.hours }}</span>
                        </button>
                    </div>
                    <input v-model="planForm.major_focus" @change="savePlan" maxlength="200" class="input-base" placeholder="Major focus" />
                    <input v-model="planForm.minor_focus" @change="savePlan" maxlength="200" class="input-base" placeholder="Minor focus" />
                </div>
                <div class="bg-white rounded-lg shadow p-5 space-y-3 lg:col-span-2">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="text-sm font-semibold text-gray-900">Score so far</p>
                        <p class="text-sm text-gray-600">
                            {{ data.score.done }} done · {{ data.score.partial }} partial · {{ data.score.missed }} missed
                            · {{ data.score.red }} red · {{ data.score.planned_total }} planned this week
                        </p>
                    </div>
                    <ScoreBar :percent="data.score.percent" :threshold="data.success_threshold_percent" :on-track="data.score.on_track" />
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm pt-2">
                        <div><p class="text-gray-500">Overdue reviews</p><p class="text-lg font-semibold" :class="data.stats.overdue_topics ? 'text-red-600' : ''">{{ data.stats.overdue_topics }}</p></div>
                        <div><p class="text-gray-500">Reviews done</p><p class="text-lg font-semibold">{{ data.stats.reviews_completed }}</p></div>
                        <div><p class="text-gray-500">Review minutes</p><p class="text-lg font-semibold">{{ data.stats.review_minutes }}</p></div>
                        <div><p class="text-gray-500">New topics</p><p class="text-lg font-semibold">{{ data.stats.new_topics }} / {{ data.stats.weekly_new_topic_cap }}</p></div>
                    </div>
                </div>
            </div>

            <!-- Seven-day grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-7 gap-3">
                <div v-for="day in days" :key="day.date" class="bg-white rounded-lg shadow p-3 space-y-2"
                    :class="day.date === today ? 'ring-2 ring-primary-400' : ''">
                    <div class="flex items-baseline justify-between">
                        <p class="font-semibold text-gray-900 text-sm">{{ day.label }}</p>
                        <p class="text-xs text-gray-500">{{ day.short }} · {{ day.isOff ? terms.off : terms.workday }}</p>
                    </div>
                    <div v-for="block in day.blocks" :key="block.id" class="border rounded-lg p-2 space-y-1.5"
                        :class="block.status === 'red' ? 'bg-red-50 border-red-200' : 'border-gray-200'">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-xs font-medium text-gray-700">{{ slotLabels[block.slot] }}</span>
                            <span :class="['text-[10px] px-1.5 py-0.5 rounded-full', laneStyles[block.lane]]">{{ block.lane }}</span>
                        </div>
                        <input :value="block.planned_task || ''" @change="(e) => saveBlock(block, { planned_task: e.target.value || null })"
                            maxlength="300" class="w-full text-xs border border-gray-200 rounded px-1.5 py-1" placeholder="Exact task…" />
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-gray-500">{{ block.planned_minutes || '–' }} min</span>
                            <span class="flex gap-0.5">
                                <button v-for="s in statuses" :key="s.value" @click="saveBlock(block, { status: block.status === s.value ? 'planned' : s.value })"
                                    :title="s.label"
                                    :class="['w-6 h-6 rounded text-xs font-bold', block.status === s.value ? s.style : 'bg-gray-100 text-gray-600 hover:bg-gray-200']">{{ s.short }}</button>
                                <button @click="removeBlock(block)" title="Remove block" class="w-6 h-6 rounded text-xs text-red-600 hover:bg-red-50">🗑</button>
                            </span>
                        </div>
                    </div>
                    <button @click="addBlock(day.date)" class="w-full text-xs py-1 rounded border border-dashed border-gray-300 text-gray-600 hover:bg-gray-50">+ Add block</button>
                </div>
            </div>

            <!-- Weekly review -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h2 class="text-xl font-bold text-gray-900">Weekly review</h2>
                <p class="text-sm text-gray-600">Score the week, learn from it, choose next week's gear and pre-decide its tasks.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">What got in the way?</label>
                        <textarea v-model="planForm.reflection" maxlength="2000" rows="3" class="input-base"></textarea>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">My if-then plan</label>
                            <input v-model="planForm.if_then_plan" maxlength="500" class="input-base" placeholder="If the office runs late, then I do 10 minutes of due reviews only." />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Output shipped this week</label>
                            <input v-model="planForm.output_note" maxlength="300" class="input-base" placeholder="e.g. Essay #4 marked" />
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button class="btn-primary" :disabled="saving" @click="savePlan">Save review</button>
                    <span class="text-sm text-gray-600">Plan next week:</span>
                    <select v-model="nextGear" class="input-base w-auto">
                        <option v-for="g in gearList" :key="g.value" :value="g.value">{{ g.label }} ({{ g.hours }})</option>
                    </select>
                    <button class="btn-secondary" :disabled="saving" @click="planNextWeek">Plan next week</button>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import { useWeeklyPlanStore } from '@/stores/weeklyPlan'
import { todayLocal, shiftDate, parseLocalDate } from '@/helpers/dates'
import { showConfirm, showError } from '@/helpers/alerts'
import { gearsWithOptions, dayTerms, slotLabels, laneStyles, statuses } from '@/components/weekly/weeklyMeta'
import { usePreferencesStore } from '@/stores/preferences'
import ScoreBar from '@/components/weekly/ScoreBar.vue'

const authStore = useAuthStore()
const store = useWeeklyPlanStore()
const api = () => authStore.getApiClient()

const today = todayLocal()
const date = ref(today)
const data = ref(null)
const loading = ref(false)
const saving = ref(false)
const nextGear = ref('green')
const newPlan = reactive({ gear: 'green', major_focus: '', minor_focus: '' })
const planForm = reactive({ major_focus: '', minor_focus: '', reflection: '', if_then_plan: '', output_note: '' })

const preferencesStore = usePreferencesStore()
const offDays = ref([])
const gearList = computed(() => gearsWithOptions(data.value?.gear_options || []))
const terms = computed(() => dayTerms(data.value?.study_profile))

const isCurrentWeek = computed(() => data.value && today >= data.value.week_start && today <= data.value.week_end)
const rangeLabel = computed(() => data.value
    ? `${format(parseLocalDate(data.value.week_start), 'd MMM')} – ${format(parseLocalDate(data.value.week_end), 'd MMM yyyy')}`
    : '')

const days = computed(() => {
    if (!data.value) return []
    return Array.from({ length: 7 }, (_, i) => {
        const d = shiftDate(data.value.week_start, i)
        return {
            date: d,
            label: format(parseLocalDate(d), 'EEEE'),
            short: format(parseLocalDate(d), 'd MMM'),
            blocks: data.value.blocks.filter((b) => b.block_date === d),
            isOff: offDays.value.includes(parseLocalDate(d).getDay()),
        }
    })
})

const apply = (payload) => {
    data.value = payload
    const plan = payload.plan || {}
    Object.assign(planForm, {
        major_focus: plan.major_focus || '',
        minor_focus: plan.minor_focus || '',
        reflection: plan.reflection || '',
        if_then_plan: plan.if_then_plan || '',
        output_note: plan.output_note || '',
    })
}

const fail = (err, fallback) => showError(err.response?.data?.msg || fallback)

const load = async () => {
    loading.value = true
    try {
        apply(await store.fetchWeek(api(), date.value))
    } catch (err) {
        await fail(err, 'Failed to load the weekly plan')
    } finally {
        loading.value = false
    }
}

const shiftWeek = (days) => {
    date.value = shiftDate(date.value, days)
    load()
}

const goToToday = () => {
    date.value = today
    load()
}

const createPlan = async () => {
    saving.value = true
    try {
        apply(await store.createWeek(api(), { week_start: data.value.week_start, ...newPlan }))
    } catch (err) {
        await fail(err, 'Failed to create the plan')
    } finally {
        saving.value = false
    }
}

const savePlan = async () => {
    saving.value = true
    try {
        apply(await store.updateWeek(api(), data.value.plan.id, { ...planForm }))
    } catch (err) {
        await fail(err, 'Failed to save the plan')
    } finally {
        saving.value = false
    }
}

const changeGear = async (gear) => {
    if (gear === data.value.plan.gear) return
    const ok = await showConfirm(`Switch to ${gear}? Blocks from today on that are still planned will be replaced; past and marked blocks stay.`, 'Change gear')
    if (!ok) return
    try {
        apply(await store.regenerate(api(), data.value.plan.id, gear))
    } catch (err) {
        await fail(err, 'Failed to change gear')
    }
}

const saveBlock = async (block, changes) => {
    try {
        await store.updateBlock(api(), block.id, changes)
        apply(await store.fetchWeek(api(), date.value))
    } catch (err) {
        await fail(err, 'Failed to update the block')
    }
}

const addBlock = async (blockDate) => {
    try {
        await store.addBlock(api(), data.value.plan.id, { block_date: blockDate, slot: 'other', lane: 'major', planned_minutes: 30 })
        apply(await store.fetchWeek(api(), date.value))
    } catch (err) {
        await fail(err, 'Failed to add a block')
    }
}

const removeBlock = async (block) => {
    try {
        await store.deleteBlock(api(), block.id)
        apply(await store.fetchWeek(api(), date.value))
    } catch (err) {
        await fail(err, 'Failed to remove the block')
    }
}

const planNextWeek = async () => {
    saving.value = true
    try {
        const nextStart = shiftDate(data.value.week_start, 7)
        await store.createWeek(api(), { week_start: nextStart, gear: nextGear.value })
        date.value = nextStart
        await load()
    } catch (err) {
        await fail(err, 'Failed to plan next week')
    } finally {
        saving.value = false
    }
}

onMounted(async () => {
    await load()
    try {
        offDays.value = (await preferencesStore.fetchPreferences(api())).off_days || []
    } catch {
        offDays.value = []
    }
})
</script>
