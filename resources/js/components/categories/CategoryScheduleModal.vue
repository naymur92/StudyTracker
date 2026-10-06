<template>
    <div v-if="category" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$emit('close')">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-lg w-full space-y-4 max-h-[90vh] overflow-y-auto">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Review schedule — {{ category.name }}</h2>
                <p class="text-sm text-gray-600">
                    New topics in this category use this schedule. Choose gaps by how long you need to remember.
                    <router-link to="/app/guide#categories" class="text-primary-600 hover:underline">Learn more</router-link>
                </p>
            </div>

            <div class="space-y-2">
                <label v-for="preset in presets" :key="preset.key"
                    class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer"
                    :class="form.preset_key === preset.key ? 'border-primary-500 bg-primary-50' : 'border-gray-200'">
                    <input type="radio" class="mt-1" :value="preset.key" v-model="form.preset_key" @change="applyPreset(preset)" />
                    <span>
                        <span class="block font-medium text-gray-900">{{ preset.name }}</span>
                        <span class="block text-xs text-gray-600">{{ preset.description }}</span>
                    </span>
                </label>
                <label class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer"
                    :class="form.preset_key === 'custom' ? 'border-primary-500 bg-primary-50' : 'border-gray-200'">
                    <input type="radio" class="mt-1" value="custom" v-model="form.preset_key" />
                    <span class="block font-medium text-gray-900">Custom</span>
                </label>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Offsets (days, increasing)</label>
                    <input v-model="offsetsText" type="text" class="input-base" placeholder="1, 3, 7, 14"
                        @input="form.preset_key = 'custom'" />
                    <p v-if="errors.offsets" class="text-xs text-red-600 mt-1">{{ errors.offsets[0] }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Then repeat every (days)</label>
                    <input v-model.number="form.repeat_every_days" type="number" min="1" max="365" class="input-base" placeholder="none" />
                    <p v-if="errors.repeat_every_days" class="text-xs text-red-600 mt-1">{{ errors.repeat_every_days[0] }}</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Until (e.g. exam date)</label>
                    <input v-model="form.repeat_until" type="date" class="input-base" :min="today" />
                    <p v-if="errors.repeat_until" class="text-xs text-red-600 mt-1">{{ errors.repeat_until[0] }}</p>
                </div>
            </div>

            <p class="text-sm text-gray-700 bg-gray-50 rounded p-2">
                <ScheduleSummary :offsets="parsedOffsets" :repeat-every-days="form.repeat_every_days || null" :repeat-until="form.repeat_until || null" />
            </p>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" v-model="form.apply_to_existing_topics" class="w-4 h-4" />
                Also apply to existing topics in this category (takes effect at each topic's next review)
            </label>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-primary flex-1" :disabled="saving" @click="save">{{ saving ? 'Saving…' : 'Save schedule' }}</button>
                <button v-if="category.review_schedule" type="button" class="btn-secondary" :disabled="saving" @click="remove">Use default</button>
                <button type="button" class="btn-secondary" @click="$emit('close')">Cancel</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/categories'
import { showError, showSuccess } from '@/helpers/alerts'
import { todayLocal } from '@/helpers/dates'
import ScheduleSummary from '@/components/topics/ScheduleSummary.vue'

const props = defineProps({
    category: { type: Object, default: null },
    presets: { type: Array, default: () => [] },
})
const emit = defineEmits(['close'])

const authStore = useAuthStore()
const categoryStore = useCategoryStore()
const today = todayLocal()

const form = reactive({ preset_key: 'standard', repeat_every_days: null, repeat_until: '', apply_to_existing_topics: false })
const offsetsText = ref('1, 7, 30, 90')
const errors = ref({})
const saving = ref(false)

const parsedOffsets = computed(() => offsetsText.value.split(/[\s,]+/).filter(Boolean).map(Number).filter((n) => Number.isInteger(n)))

const applyPreset = (preset) => {
    offsetsText.value = preset.offsets.join(', ')
    form.repeat_every_days = preset.repeat_every_days
}

watch(() => props.category, (category) => {
    errors.value = {}
    if (!category) return
    const schedule = category.review_schedule
    form.preset_key = schedule?.preset_key || 'standard'
    offsetsText.value = (schedule?.offsets || [1, 7, 30, 90]).join(', ')
    form.repeat_every_days = schedule?.repeat_every_days ?? null
    form.repeat_until = schedule?.repeat_until || ''
    form.apply_to_existing_topics = false
}, { immediate: true })

const save = async () => {
    saving.value = true
    errors.value = {}
    try {
        const api = authStore.getApiClient()
        await categoryStore.saveSchedule(api, props.category.id, {
            preset_key: form.preset_key,
            offsets: parsedOffsets.value,
            repeat_every_days: form.repeat_every_days || null,
            repeat_until: form.repeat_until || null,
            apply_to_existing_topics: form.apply_to_existing_topics,
        })
        await showSuccess('Review schedule saved.')
        emit('close')
    } catch (err) {
        errors.value = err.response?.data?.errors || {}
        await showError(err.response?.data?.msg || 'Failed to save schedule')
    } finally {
        saving.value = false
    }
}

const remove = async () => {
    saving.value = true
    try {
        const api = authStore.getApiClient()
        await categoryStore.deleteSchedule(api, props.category.id)
        await showSuccess('This category now uses your default schedule.')
        emit('close')
    } catch (err) {
        await showError(err.response?.data?.msg || 'Failed to remove schedule')
    } finally {
        saving.value = false
    }
}
</script>
