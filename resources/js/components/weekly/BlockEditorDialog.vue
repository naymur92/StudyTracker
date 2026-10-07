<template>
    <div v-if="block" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$emit('close')">
        <form class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto" @submit.prevent="save">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Edit block</h2>
                <p class="text-sm text-gray-600">{{ dayLabel }}</p>
                <p v-if="locked" class="mt-2 text-xs rounded-lg bg-amber-50 border border-amber-200 text-amber-900 p-2">
                    This block's timer is active, so its minutes and breaks are locked until you stop it.
                </p>
                <div v-else-if="block.has_recorded_time" class="mt-2 text-xs rounded-lg bg-gray-50 border border-gray-200 text-gray-700 p-2 flex items-start justify-between gap-3">
                    <span>The timer recorded {{ block.actual_minutes }} min on this block, so it stays done or partial and its minutes can't go below that.</span>
                    <button type="button" class="shrink-0 font-semibold text-red-700 hover:underline disabled:opacity-50" :disabled="saving" @click="clearRecorded">Clear recorded time</button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="block-task">Exact task</label>
                <input id="block-task" v-model="form.planned_task" maxlength="300" class="input-base" placeholder="e.g. Write Task 2 essay #4" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="block-minutes">Minutes</label>
                    <input id="block-minutes" v-model.number="form.planned_minutes" type="number" :min="Math.max(5, block.actual_minutes || 0)" max="480" class="input-base" :disabled="locked" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="block-slot">Slot</label>
                    <select id="block-slot" v-model="form.slot" class="input-base">
                        <option v-for="(label, value) in slotLabels" :key="value" :value="value">{{ label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="block-lane">Lane</label>
                    <select id="block-lane" v-model="form.lane" class="input-base">
                        <option v-for="lane in lanes" :key="lane" :value="lane">{{ lane }}</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Topic</label>
                <TopicPicker v-model="form.topic_id" :lane="form.lane" :current="block.topic" />
                <p v-if="form.lane === 'review'" class="text-xs text-gray-500 mt-1">Review blocks work through your due reviews, so a topic is optional.</p>
            </div>

            <fieldset :disabled="locked">
                <legend class="block text-sm font-medium text-gray-700 mb-1">Breaks</legend>
                <div class="flex flex-wrap gap-2">
                    <label v-for="p in breakPresets" :key="p.value" class="px-3 py-1.5 rounded-lg border text-sm cursor-pointer"
                        :class="[breakPreset === p.value ? 'border-primary-400 bg-primary-50 text-primary-800' : 'border-gray-200 text-gray-700', locked ? 'opacity-60 cursor-not-allowed' : '']">
                        <input v-model="breakPreset" type="radio" class="sr-only" :value="p.value" />{{ p.label }}
                    </label>
                </div>
                <div v-if="breakPreset === 'custom'" class="grid grid-cols-2 gap-3 mt-2">
                    <div>
                        <label class="block text-xs text-gray-600 mb-1" for="break-every">Work minutes (10–120)</label>
                        <input id="break-every" v-model.number="form.break_every_minutes" type="number" min="10" max="120" class="input-base" required />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-600 mb-1" for="break-len">Break minutes (1–30)</label>
                        <input id="break-len" v-model.number="form.break_minutes" type="number" min="1" max="30" class="input-base" required />
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-1">Breaks fall inside the block's minutes; the timer alerts when each break starts and ends.</p>
            </fieldset>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" for="block-note">Note</label>
                <input id="block-note" v-model="form.note" maxlength="500" class="input-base" />
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1" :disabled="saving">Save</button>
                <button type="button" class="btn-secondary flex-1" @click="$emit('close')">Cancel</button>
            </div>
        </form>
    </div>
</template>

<script setup>
// Design one block: task, minutes, topic, break pattern, slot, lane and note.
import { computed, reactive, ref, watch } from 'vue'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import { useWeeklyPlanStore } from '@/stores/weeklyPlan'
import { useBlockTimerStore } from '@/stores/blockTimer'
import { showConfirm } from '@/helpers/alerts'
import { parseLocalDate } from '@/helpers/dates'
import { slotLabels } from '@/components/weekly/weeklyMeta'
import TopicPicker from '@/components/timer/TopicPicker.vue'

const props = defineProps({
    block: { type: Object, default: null },
})
const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const weeklyStore = useWeeklyPlanStore()
const lanes = ['major', 'minor', 'review', 'work']
const breakPresets = [
    { value: 'none', label: 'No breaks', every: null, brk: null },
    { value: '25-5', label: '25 + 5', every: 25, brk: 5 },
    { value: '50-10', label: '50 + 10', every: 50, brk: 10 },
    { value: 'custom', label: 'Custom' },
]

const form = reactive({ planned_task: '', planned_minutes: null, slot: 'other', lane: 'major', topic_id: null, note: '', break_every_minutes: null, break_minutes: null })
const breakPreset = ref('none')
const saving = ref(false)
const error = ref('')

const locked = computed(() => !!props.block?.timer)
const dayLabel = computed(() => (props.block ? format(parseLocalDate(props.block.block_date), 'EEEE d MMM') : ''))

watch(() => props.block, (block) => {
    if (!block) return
    Object.assign(form, {
        planned_task: block.planned_task || '',
        planned_minutes: block.planned_minutes,
        slot: block.slot,
        lane: block.lane,
        topic_id: block.topic?.id ?? null,
        note: block.note || '',
        break_every_minutes: block.break_every_minutes,
        break_minutes: block.break_minutes,
    })
    const preset = breakPresets.find((p) => p.every === block.break_every_minutes && p.brk === block.break_minutes)
    breakPreset.value = preset ? preset.value : 'custom'
    error.value = ''
}, { immediate: true })

watch(breakPreset, (value) => {
    const preset = breakPresets.find((p) => p.value === value)
    if (preset && value !== 'custom') {
        form.break_every_minutes = preset.every
        form.break_minutes = preset.brk
    } else if (value === 'custom' && !form.break_every_minutes) {
        form.break_every_minutes = 45
        form.break_minutes = 5
    }
})

const timerStore = useBlockTimerStore()

/** Delete the block's timer runs after a confirmation; it becomes planned again. */
const clearRecorded = async () => {
    const ok = await showConfirm(
        `Delete the ${props.block.actual_minutes} min the timer recorded on this block? It goes back to planned, and this can't be undone.`,
        'Clear recorded time',
    )
    if (!ok) return
    saving.value = true
    error.value = ''
    try {
        await timerStore.clearRecorded(authStore.getApiClient(), props.block.id)
        emit('saved')
        emit('close')
    } catch (err) {
        const errors = err.response?.data?.errors
        error.value = (errors && Object.values(errors).flat()[0]) || err.response?.data?.msg || 'The recorded time could not be cleared.'
    } finally {
        saving.value = false
    }
}

const save = async () => {
    saving.value = true
    error.value = ''
    const changes = {
        planned_task: form.planned_task || null,
        slot: form.slot,
        lane: form.lane,
        topic_id: form.topic_id || null,
        note: form.note || null,
    }
    // Locked fields are left out while the block's timer is active.
    if (!locked.value) {
        Object.assign(changes, {
            planned_minutes: form.planned_minutes || null,
            break_every_minutes: form.break_every_minutes || null,
            break_minutes: form.break_minutes || null,
        })
    }
    try {
        await weeklyStore.updateBlock(authStore.getApiClient(), props.block.id, changes)
        emit('saved')
        emit('close')
    } catch (err) {
        const errors = err.response?.data?.errors
        error.value = (errors && Object.values(errors).flat()[0]) || err.response?.data?.msg || 'The block could not be saved.'
    } finally {
        saving.value = false
    }
}
</script>
