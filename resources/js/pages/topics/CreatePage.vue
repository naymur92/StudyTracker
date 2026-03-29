<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <router-link to="/app/topics" class="text-primary-600 hover:text-primary-700 font-medium">
                ← Back to Topics
            </router-link>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Create New Topic</h1>
        </div>

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
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTopicStore } from '@/stores/topics'
import { useCategoryStore } from '@/stores/categories'
import { format } from 'date-fns'
import DatePicker from '@/components/DatePicker.vue'

const router = useRouter()
const authStore = useAuthStore()
const topicStore = useTopicStore()
const categoryStore = useCategoryStore()

const loading = ref(false)
const error = ref(null)
const today = format(new Date(), 'yyyy-MM-dd')

const form = reactive({
    category_id: '',
    title: '',
    description: '',
    difficulty: 'medium',
    first_study_date: format(new Date(), 'yyyy-MM-dd'),
    source_link: '',
    notes: '',
})

const categories = ref([])

const handleSubmit = async () => {
    loading.value = true
    error.value = null

    try {
        const api = authStore.getApiClient()
        await topicStore.createTopic(api, form)
        router.push({ name: 'Topics' })
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create topic'
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    try {
        const api = authStore.getApiClient()
        await categoryStore.fetchCategories(api)
        categories.value = categoryStore.categories
    } catch (err) {
        error.value = 'Failed to load categories'
    }
})
</script>
