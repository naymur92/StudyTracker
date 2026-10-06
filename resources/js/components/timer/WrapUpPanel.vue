<template>
    <form class="bg-white rounded-lg shadow p-6 border-2 border-success-300 space-y-4" @submit.prevent="save" aria-labelledby="wrapup-title">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-success-700">Block finished · {{ wrapup.minutes }} min</p>
            <h2 id="wrapup-title" class="text-xl font-bold text-gray-900">What did you learn?</h2>
            <p class="text-sm text-gray-600">
                {{ slotLabels[wrapup.slot] || 'Block' }} on {{ day }}<template v-if="wrapup.planned_task"> — {{ wrapup.planned_task }}</template>.
                Saved as a practice log for this topic.
            </p>
        </div>

        <textarea v-model="details" rows="4" maxlength="5000" class="input-base" required
            placeholder="In your own words: the key ideas, what clicked, what is still unclear…"></textarea>

        <div class="flex flex-wrap items-center gap-3">
            <label class="text-sm text-gray-700" for="wrapup-type">Practice type</label>
            <select id="wrapup-type" v-model="practiceType" class="input-base w-auto">
                <option v-for="(label, value) in practiceTypes" :key="value" :value="value">{{ label }}</option>
            </select>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn-primary" :disabled="saving || !details.trim()">Save to practice log</button>
            <button type="button" class="btn-secondary" :disabled="saving" @click="$emit('done')">Skip</button>
        </div>
    </form>
</template>

<script setup>
// Wrap-up after a block's timer finishes: one practice log with the block's
// topic, date and recorded minutes.
import { computed, ref } from 'vue'
import { format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import { usePracticeLogStore } from '@/stores/practiceLogs'
import { parseLocalDate } from '@/helpers/dates'
import { showError } from '@/helpers/alerts'
import { slotLabels } from '@/components/weekly/weeklyMeta'

const props = defineProps({
    wrapup: { type: Object, required: true },
})
const emit = defineEmits(['done', 'saved'])

// Mirrors PracticeLog::$practiceTypes.
const practiceTypes = {
    problem_solving: 'Problem Solving',
    implementation: 'Implementation',
    reading: 'Reading',
    note_making: 'Note Making',
    mock_interview: 'Mock Interview',
    other: 'Other',
}

const authStore = useAuthStore()
const practiceLogStore = usePracticeLogStore()
const details = ref('')
const practiceType = ref('other')
const saving = ref(false)

const day = computed(() => format(parseLocalDate(props.wrapup.block_date), 'EEE d MMM'))

const save = async () => {
    saving.value = true
    try {
        const log = await practiceLogStore.createPracticeLog(authStore.getApiClient(), {
            topic_id: props.wrapup.topic_id,
            practiced_on: props.wrapup.block_date,
            practice_type: practiceType.value,
            details: details.value.trim(),
            duration_minutes: Math.min(480, Math.max(1, props.wrapup.minutes || 1)),
        })
        emit('saved', log)
        emit('done')
    } catch (err) {
        const errors = err.response?.data?.errors
        await showError((errors && Object.values(errors).flat()[0]) || err.response?.data?.msg || 'The practice log could not be saved.')
    } finally {
        saving.value = false
    }
}
</script>
