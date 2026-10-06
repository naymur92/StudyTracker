<template>
    <div class="bg-white rounded-lg shadow p-6 space-y-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Study Preferences</h2>
            <p class="text-sm text-gray-600">Your daily review budget, warning thresholds and week layout.</p>
        </div>

        <div v-if="loading" class="text-center py-6">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"></div>
        </div>

        <form v-else @submit.prevent="save" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">I am a…</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="p in profiles" :key="p.value"
                        class="flex items-start gap-2 px-3 py-2 rounded-lg border text-sm cursor-pointer max-w-xs"
                        :class="form.study_profile === p.value ? 'border-primary-500 bg-primary-50 text-primary-800' : 'border-gray-200 text-gray-700'">
                        <input type="radio" class="mt-1" :value="p.value" v-model="form.study_profile" />
                        <span><span class="block font-medium">{{ p.label }}</span><span class="block text-xs opacity-80">{{ p.hint }}</span></span>
                    </label>
                </div>
                <p v-if="errors.study_profile" class="text-xs text-red-600 mt-1">{{ errors.study_profile[0] }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div v-for="field in numberFields" :key="field.key">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ field.label }}</label>
                    <input v-model.number="form[field.key]" type="number" :min="field.min" :max="field.max"
                        class="input-base" />
                    <p class="text-xs text-gray-500 mt-1">{{ field.hint }} Default: {{ defaults[field.key] }}.</p>
                    <p v-if="errors[field.key]" class="text-xs text-red-600 mt-1">{{ errors[field.key][0] }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Week starts on</label>
                    <select v-model.number="form.week_starts_on" class="input-base">
                        <option v-for="(day, idx) in dayNames" :key="day" :value="idx">{{ day }}</option>
                    </select>
                    <p v-if="errors.week_starts_on" class="text-xs text-red-600 mt-1">{{ errors.week_starts_on[0] }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ offDaysLabel }}</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="(day, idx) in dayNames" :key="day"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-sm cursor-pointer"
                        :class="form.off_days.includes(idx) ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 text-gray-700'">
                        <input type="checkbox" class="w-4 h-4" :checked="form.off_days.includes(idx)"
                            @change="toggleOffDay(idx)" />
                        {{ day }}
                    </label>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ offDaysHint }}</p>
                <p v-if="offDayError" class="text-xs text-red-600 mt-1">{{ offDayError }}</p>
            </div>

            <div class="flex gap-3">
                <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
                    {{ saving ? 'Saving...' : 'Save Preferences' }}
                </button>
                <button type="button" @click="resetToDefaults" class="btn-secondary">Use Defaults</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { usePreferencesStore } from '@/stores/preferences'
import { showError, showSuccess } from '@/helpers/alerts'

const authStore = useAuthStore()
const preferencesStore = usePreferencesStore()

const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']

const defaults = {
    review_budget_minutes: 25,
    review_debt_threshold_minutes: 30,
    minutes_per_review: 3,
    weekly_new_topic_cap: 8,
    week_starts_on: 0,
    off_days: [5, 6],
    success_threshold_percent: 80,
    study_profile: 'job_holder',
}

const profiles = [
    { value: 'job_holder', label: 'Job holder', hint: 'Weekly plans use office days and off days' },
    { value: 'student', label: 'Student', hint: 'Weekly plans use class days and free days' },
]

const numberFields = [
    { key: 'review_budget_minutes', label: 'Daily review budget (minutes)', min: 5, max: 180, hint: 'Warn when due reviews need more than this.' },
    { key: 'review_debt_threshold_minutes', label: 'Review debt threshold (minutes)', min: 5, max: 240, hint: 'Three days above this triggers the review-debt warning.' },
    { key: 'minutes_per_review', label: 'Minutes per review (estimate)', min: 1, max: 30, hint: 'Used until you have timed at least 5 reviews.' },
    { key: 'weekly_new_topic_cap', label: 'New topics per week (cap)', min: 1, max: 50, hint: 'Warn when you add more topics than this in a week.' },
    { key: 'success_threshold_percent', label: 'Weekly success line (%)', min: 50, max: 100, hint: 'Share of planned blocks that counts as a good week.' },
]

const form = reactive({ ...defaults, off_days: [...defaults.off_days] })
const errors = ref({})
const loading = ref(false)
const saving = ref(false)

const offDaysLabel = computed(() => (form.study_profile === 'student' ? 'Days without classes' : 'Off days (no office)'))
const offDaysHint = computed(() => (form.study_profile === 'student'
    ? 'Used by weekly plans to choose class-day or free-day blocks.'
    : 'Used by weekly plans to choose office-day or off-day blocks.'))

const offDayError = computed(() => {
    const keys = Object.keys(errors.value).filter((k) => k.startsWith('off_days'))
    return keys.length ? errors.value[keys[0]][0] : null
})

const applyValues = (values) => {
    Object.assign(form, { ...values, off_days: [...(values.off_days || [])] })
}

const toggleOffDay = (idx) => {
    form.off_days = form.off_days.includes(idx)
        ? form.off_days.filter((d) => d !== idx)
        : [...form.off_days, idx].sort()
}

const resetToDefaults = () => {
    applyValues(defaults)
    errors.value = {}
}

const save = async () => {
    saving.value = true
    errors.value = {}
    try {
        const api = authStore.getApiClient()
        const saved = await preferencesStore.updatePreferences(api, { ...form })
        applyValues(saved)
        await showSuccess('Study preferences saved.')
    } catch (err) {
        errors.value = err.response?.data?.errors || {}
        await showError(err.response?.data?.msg || 'Failed to save preferences')
    } finally {
        saving.value = false
    }
}

onMounted(async () => {
    loading.value = true
    try {
        const api = authStore.getApiClient()
        applyValues(await preferencesStore.fetchPreferences(api))
    } catch (err) {
        await showError('Failed to load preferences')
    } finally {
        loading.value = false
    }
})
</script>
