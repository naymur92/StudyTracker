<template>
    <div class="space-y-6">
        <div class="mb-8">
            <router-link :to="`/topics/${route.params.id}`" class="text-primary-600 hover:text-primary-700 font-medium">
                ← Back to Topic
            </router-link>
            <h1 class="text-3xl font-bold text-gray-900 mt-4">Edit Topic</h1>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <div v-else class="bg-white rounded-lg shadow p-8 max-w-2xl">
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select v-model="form.category_id" class="input-base">
                        <option value="">No Category</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                    <input v-model="form.title" type="text" required class="input-base" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea v-model="form.description" class="input-base" rows="4"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Difficulty</label>
                        <select v-model="form.difficulty" class="input-base">
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select v-model="form.status" class="input-base">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Source Link</label>
                    <input v-model="form.source_link" type="url" class="input-base" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea v-model="form.notes" class="input-base" rows="3"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">First Study Date</label>
                    <input :value="formatDate(topic?.first_study_date)" type="text" disabled
                        class="input-base bg-gray-50" />
                </div>

                <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>

                <div class="flex gap-4">
                    <button type="submit" :disabled="saving" class="btn-primary disabled:opacity-50">
                        <span v-if="saving">Saving...</span>
                        <span v-else>Save Changes</span>
                    </button>
                    <router-link :to="`/topics/${route.params.id}`" class="btn-secondary">Cancel</router-link>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTopicStore } from '@/stores/topics'
import { useCategoryStore } from '@/stores/categories'
import { showError, showSuccess } from '@/helpers/alerts'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const topicStore = useTopicStore()
const categoryStore = useCategoryStore()

const loading = ref(false)
const saving = ref(false)
const error = ref(null)
const topic = ref(null)
const categories = ref([])

const form = reactive({
    category_id: '',
    title: '',
    description: '',
    difficulty: 'medium',
    status: 'active',
    source_link: '',
    notes: '',
})

const formatDate = (date) => date || 'N/A'

const loadPage = async () => {
    loading.value = true
    error.value = null

    try {
        const api = authStore.getApiClient()
        await Promise.all([
            topicStore.fetchTopic(api, route.params.id),
            categoryStore.fetchCategories(api),
        ])

        topic.value = topicStore.topic
        categories.value = categoryStore.categories

        form.category_id = topic.value?.category_id || ''
        form.title = topic.value?.title || ''
        form.description = topic.value?.description || ''
        form.difficulty = topic.value?.difficulty || 'medium'
        form.status = topic.value?.status || 'active'
        form.source_link = topic.value?.source_link || ''
        form.notes = topic.value?.notes || ''
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load topic'
        await showError(error.value)
    } finally {
        loading.value = false
    }
}

const handleSubmit = async () => {
    saving.value = true
    error.value = null

    try {
        const api = authStore.getApiClient()
        await topicStore.updateTopic(api, route.params.id, form)
        await showSuccess('Topic updated successfully.')
        router.push({ name: 'TopicDetail', params: { id: route.params.id } })
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to update topic'
        await showError(error.value)
    } finally {
        saving.value = false
    }
}

onMounted(loadPage)
</script>
