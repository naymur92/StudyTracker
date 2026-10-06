<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$emit('close')">
        <form @submit.prevent="submit" class="bg-white rounded-lg shadow-lg p-6 max-w-lg w-full space-y-4 max-h-[90vh] overflow-y-auto">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ mistake ? 'Edit mistake' : 'Log a mistake' }}</h2>
                <p class="text-sm text-gray-600">Every wrong answer — a mock, an exercise, an interview question — becomes a short review: +1, +3 and +7 days.</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Question *</label>
                <textarea v-model="form.question" required maxlength="500" rows="2" class="input-base"></textarea>
                <p v-if="errors.question" class="text-xs text-red-600 mt-1">{{ errors.question[0] }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your answer</label>
                    <textarea v-model="form.my_answer" maxlength="2000" rows="2" class="input-base"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correct answer *</label>
                    <textarea v-model="form.correct_answer" required maxlength="2000" rows="2" class="input-base"></textarea>
                    <p v-if="errors.correct_answer" class="text-xs text-red-600 mt-1">{{ errors.correct_answer[0] }}</p>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Why was it wrong? *</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="c in causes" :key="c.value" class="flex items-center gap-2 px-3 py-1.5 rounded-lg border text-sm cursor-pointer"
                        :class="form.cause === c.value ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200'">
                        <input type="radio" :value="c.value" v-model="form.cause" required />
                        {{ c.label }}
                    </label>
                </div>
                <p v-if="errors.cause" class="text-xs text-red-600 mt-1">{{ errors.cause[0] }}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent topic</label>
                    <select v-model="form.parent_topic_id" class="input-base">
                        <option :value="null">None</option>
                        <option v-for="t in topics" :key="t.id" :value="t.id">{{ t.title }}</option>
                    </select>
                    <p v-if="errors.parent_topic_id" class="text-xs text-red-600 mt-1">{{ errors.parent_topic_id[0] }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                    <input v-model="form.source" type="text" maxlength="200" class="input-base" placeholder="e.g. IELTS mock 3" />
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1" :disabled="saving">{{ saving ? 'Saving…' : (mistake ? 'Save' : 'Log mistake') }}</button>
                <button type="button" class="btn-secondary" @click="$emit('close')">Cancel</button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useMistakeStore } from '@/stores/mistakes'
import { showError, showSuccess } from '@/helpers/alerts'

const props = defineProps({
    open: { type: Boolean, default: false },
    mistake: { type: Object, default: null },
    parentTopicId: { type: String, default: null },
})
const emit = defineEmits(['close', 'saved'])

const authStore = useAuthStore()
const mistakeStore = useMistakeStore()

const causes = [
    { value: 'concept', label: 'Did not understand the concept' },
    { value: 'memory', label: 'Forgot it' },
    { value: 'careless', label: 'Careless slip' },
]

const empty = () => ({ question: '', my_answer: '', correct_answer: '', cause: null, parent_topic_id: null, source: '' })
const form = reactive(empty())
const errors = ref({})
const saving = ref(false)
const topics = ref([])

watch(() => [props.open, props.mistake, props.parentTopicId], () => {
    if (!props.open) return
    errors.value = {}
    Object.assign(form, empty())
    if (props.mistake) {
        Object.assign(form, {
            question: props.mistake.question,
            my_answer: props.mistake.my_answer || '',
            correct_answer: props.mistake.correct_answer || '',
            cause: props.mistake.cause,
            parent_topic_id: props.mistake.parent_topic?.id || null,
            source: props.mistake.source || '',
        })
    } else if (props.parentTopicId) {
        form.parent_topic_id = props.parentTopicId
    }
}, { immediate: true })

const submit = async () => {
    saving.value = true
    errors.value = {}
    try {
        const api = authStore.getApiClient()
        const payload = { ...form, my_answer: form.my_answer || null, source: form.source || null }
        const saved = props.mistake
            ? await mistakeStore.updateMistake(api, props.mistake.id, payload)
            : await mistakeStore.createMistake(api, payload)
        await showSuccess(props.mistake ? 'Mistake updated.' : 'Mistake logged — first review tomorrow.')
        emit('saved', saved)
        emit('close')
    } catch (err) {
        errors.value = err.response?.data?.errors || {}
        await showError(err.response?.data?.msg || 'Failed to save the mistake')
    } finally {
        saving.value = false
    }
}

onMounted(async () => {
    try {
        const response = await authStore.getApiClient().get('/study/topics', { params: { per_page: 100, status: 'active' } })
        topics.value = response.data.data || []
    } catch {
        topics.value = []
    }
})
</script>
