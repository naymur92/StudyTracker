<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <router-link to="/app/topics" class="text-primary-600 hover:text-primary-700 font-medium">
                ← Back to Topics
            </router-link>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Create New Topic</h1>
        </div>

        <LoadWarnings :load="reviewLoad" :show-cap="true" class="max-w-2xl" />

        <!-- Form -->
        <div class="bg-white rounded-lg shadow p-8 max-w-2xl">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Category -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <select v-model="form.category_id" required class="input-base">
                        <option value="">Select a category</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <p class="text-sm text-gray-600 -mt-4" v-if="appliedSchedule">
                    <ScheduleSummary :offsets="appliedSchedule.offsets" :repeat-every-days="appliedSchedule.repeat_every_days"
                        :repeat-until="appliedSchedule.repeat_until" />
                    <span class="text-gray-500"> — {{ appliedSchedule.label }}</span>
                </p>

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input v-model="form.title" type="text" required class="input-base"
                        placeholder="e.g., Derivatives and Differentiation" />
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea v-model="form.description" class="input-base" placeholder="Describe this topic..."
                        rows="4"></textarea>
                </div>

                <!-- Difficulty -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Difficulty *</label>
                    <select v-model="form.difficulty" required class="input-base">
                        <option value="easy">Easy</option>
                        <option value="medium">Medium</option>
                        <option value="hard">Hard</option>
                    </select>
                </div>

                <!-- First Study Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Study Date *</label>
                    <DatePicker v-model="form.first_study_date" placeholder="Select study date" :required="true"
                        :min-date="today" />
                </div>

                <!-- Source Link -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Source Link</label>
                    <input v-model="form.source_link" type="url" class="input-base"
                        placeholder="https://example.com/resource" />
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea v-model="form.notes" class="input-base" placeholder="Any additional notes..."
                        rows="3"></textarea>
                </div>

                <RecallCardFields :form="form" :errors="fieldErrors" />

                <!-- Error message -->
                <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>

                <!-- Submit -->
                <div class="flex gap-4">
                    <button type="submit" :disabled="loading" class="btn-primary disabled:opacity-50">
                        <span v-if="loading">Creating...</span>
                        <span v-else>Create Topic</span>
                    </button>
                    <router-link to="/app/topics" class="btn-secondary">
                        Cancel
                    </router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTopicStore } from '@/stores/topics'
import { useCategoryStore } from '@/stores/categories'
import { format } from 'date-fns'
import DatePicker from '@/components/DatePicker.vue'
import RecallCardFields from '@/components/topics/RecallCardFields.vue'
import LoadWarnings from '@/components/review/LoadWarnings.vue'
import ScheduleSummary from '@/components/topics/ScheduleSummary.vue'
import { useRevisionTemplateStore } from '@/stores/revisionTemplates'
import { useReviewStore } from '@/stores/review'
import { todayLocal } from '@/helpers/dates'

const router = useRouter()
const authStore = useAuthStore()
const topicStore = useTopicStore()
const categoryStore = useCategoryStore()

const loading = ref(false)
const error = ref(null)
const today = todayLocal()
const reviewStore = useReviewStore()
const reviewLoad = ref(null)
const revisionTemplateStore = useRevisionTemplateStore()
const defaultOffsets = ref([])

// Which review schedule the new topic will get: its category's, or your default.
const appliedSchedule = computed(() => {
    const category = categories.value.find((c) => c.id === form.category_id)
    if (category?.review_schedule) {
        return { ...category.review_schedule, label: `${category.name} schedule` }
    }
    if (!defaultOffsets.value.length) return null
    return { offsets: defaultOffsets.value, repeat_every_days: null, repeat_until: null, label: 'your default schedule (Study Settings)' }
})

const form = reactive({
    category_id: '',
    title: '',
    description: '',
    difficulty: 'medium',
    first_study_date: format(new Date(), 'yyyy-MM-dd'),
    source_link: '',
    notes: '',
    recall_questions: [],
    summary: '',
    practice_prompt: '',
    lane: null,
})

const fieldErrors = ref({})

const categories = ref([])

const handleSubmit = async () => {
    loading.value = true
    error.value = null

    try {
        const api = authStore.getApiClient()
        fieldErrors.value = {}
        await topicStore.createTopic(api, {
            ...form,
            recall_questions: form.recall_questions.filter((q) => q.question.trim() !== ''),
        })
        router.push({ name: 'Topics' })
    } catch (err) {
        fieldErrors.value = err.response?.data?.errors || {}
        error.value = err.response?.data?.msg || err.response?.data?.message || 'Failed to create topic'
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    try {
        const api = authStore.getApiClient()
        await categoryStore.fetchCategories(api)
        categories.value = categoryStore.categories
        reviewLoad.value = await reviewStore.fetchLoad(api).catch(() => null)
        await revisionTemplateStore.fetchTemplates(api).catch(() => null)
        defaultOffsets.value = (revisionTemplateStore.templates || [])
            .filter((t) => t.is_active)
            .sort((a, b) => a.sequence_no - b.sequence_no)
            .map((t) => t.day_offset)
    } catch (err) {
        error.value = 'Failed to load categories'
    }
})
</script>
