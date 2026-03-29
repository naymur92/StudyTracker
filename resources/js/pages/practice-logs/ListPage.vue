<template>
    <div class="space-y-6">
        <h1 class="text-3xl font-bold text-gray-900">Practice Logs</h1>

        <div class="bg-white rounded-lg shadow p-4 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <DatePicker v-model="filters.date_from" placeholder="From date" @change="applyFilters" />
                <DatePicker v-model="filters.date_to" placeholder="To date" :min-date="filters.date_from || null"
                    @change="applyFilters" />
                <select v-model="filters.practice_type" @change="applyFilters" class="input-base">
                    <option value="">All Types</option>
                    <option value="problem_solving">Problem Solving</option>
                    <option value="implementation">Implementation</option>
                    <option value="reading">Reading</option>
                    <option value="note_making">Note Making</option>
                    <option value="mock_interview">Mock Interview</option>
                    <option value="other">Other</option>
                </select>
                <button @click="openCreateModal" class="btn-primary">+ Log Practice</button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <div v-if="!logs || logs.length === 0" class="p-8 text-center text-gray-600">
                No practice logs yet.
            </div>
            <table v-else class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900">Topic</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900">Details</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900">Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-900">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-900">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ log.topic?.title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ log.practice_type_label ||
                    capitalizeFirst(log.practice_type) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 truncate">{{ log.details }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ log.duration_minutes || 0 }} min</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(log.practiced_on) }}</td>
                        <td class="px-6 py-4 text-right space-x-3">
                            <button @click="openEditModal(log)" class="text-blue-600 hover:text-blue-800 text-sm">
                                Edit
                            </button>
                            <button @click="deleteLog(log.id)" class="text-red-600 hover:text-red-800 text-sm">
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination.lastPage > 1" class="flex justify-center gap-2">
            <button @click="previousPage" :disabled="pagination.page === 1"
                class="px-4 py-2 rounded border border-gray-300 disabled:opacity-50">
                Previous
            </button>
            <span class="px-4 py-2">Page {{ pagination.page }} of {{ pagination.lastPage }}</span>
            <button @click="nextPage" :disabled="pagination.page === pagination.lastPage"
                class="px-4 py-2 rounded border border-gray-300 disabled:opacity-50">
                Next
            </button>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-xl w-full">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    {{ isEditMode ? 'Edit Practice Log' : 'New Practice Log' }}
                </h2>
                <form @submit.prevent="submitLog" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Topic *</label>
                        <select v-model="logForm.topic_id" class="input-base" :disabled="isEditMode" required>
                            <option value="">Select topic</option>
                            <option v-for="topic in topics" :key="topic.id" :value="topic.id">{{ topic.title }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Practice Type *</label>
                            <select v-model="logForm.practice_type" class="input-base" required>
                                <option value="problem_solving">Problem Solving</option>
                                <option value="implementation">Implementation</option>
                                <option value="reading">Reading</option>
                                <option value="note_making">Note Making</option>
                                <option value="mock_interview">Mock Interview</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date *</label>
                            <DatePicker v-model="logForm.practiced_on" placeholder="Practice date" :required="true" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                        <input v-model.number="logForm.duration_minutes" type="number" min="1" max="480"
                            class="input-base" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Details *</label>
                        <textarea v-model="logForm.details" class="input-base" rows="4" required></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Outcome</label>
                        <input v-model="logForm.outcome" type="text" class="input-base" />
                    </div>

                    <div v-if="formError" class="text-sm text-red-700">{{ formError }}</div>

                    <div class="flex gap-3">
                        <button type="submit" class="btn-primary flex-1">{{ isEditMode ? 'Update' : 'Create' }}</button>
                        <button type="button" @click="showModal = false" class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { format } from 'date-fns'
import DatePicker from '@/components/DatePicker.vue'
import { useAuthStore } from '@/stores/auth'
import { usePracticeLogStore } from '@/stores/practiceLogs'
import { showConfirm, showError, showSuccess } from '@/helpers/alerts'

const authStore = useAuthStore()
const practiceLogStore = usePracticeLogStore()

const loading = ref(false)
const showModal = ref(false)
const isEditMode = ref(false)
const editingLogId = ref(null)
const formError = ref(null)
const topics = ref([])

const filters = reactive({
    date_from: null,
    date_to: null,
    practice_type: '',
})

const logForm = reactive({
    topic_id: '',
    practice_type: 'problem_solving',
    practiced_on: format(new Date(), 'yyyy-MM-dd'),
    details: '',
    duration_minutes: null,
    outcome: '',
})

const logs = computed(() => practiceLogStore.logs)
const pagination = computed(() => practiceLogStore.pagination)

const capitalizeFirst = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
const formatDate = (date) => date ? format(new Date(date), 'MMM d, yyyy') : 'N/A'

const resetForm = () => {
    logForm.topic_id = ''
    logForm.practice_type = 'problem_solving'
    logForm.practiced_on = format(new Date(), 'yyyy-MM-dd')
    logForm.details = ''
    logForm.duration_minutes = null
    logForm.outcome = ''
    formError.value = null
}

const loadTopics = async () => {
    try {
        const api = authStore.getApiClient()
        const response = await api.get('/study/topics', {
            params: { per_page: 100 },
        })
        const payload = response.data.data
        topics.value = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : [])
    } catch (err) {
        formError.value = err.response?.data?.message || 'Failed to load topics'
        await showError(formError.value)
    }
}

const applyFilters = async () => {
    loading.value = true
    try {
        const api = authStore.getApiClient()
        practiceLogStore.setFilters(filters)
        practiceLogStore.resetPagination()
        await practiceLogStore.fetchPracticeLogs(api)
    } catch (err) {
        await showError(err.response?.data?.message || 'Failed to load practice logs')
    } finally {
        loading.value = false
    }
}

const openCreateModal = async () => {
    isEditMode.value = false
    editingLogId.value = null
    resetForm()
    if (topics.value.length === 0) {
        await loadTopics()
    }
    showModal.value = true
}

const openEditModal = async (log) => {
    isEditMode.value = true
    editingLogId.value = log.id
    if (topics.value.length === 0) {
        await loadTopics()
    }
    logForm.topic_id = log.topic_id
    logForm.practice_type = log.practice_type
    logForm.practiced_on = log.practiced_on
    logForm.details = log.details || ''
    logForm.duration_minutes = log.duration_minutes || null
    logForm.outcome = log.outcome || ''
    formError.value = null
    showModal.value = true
}

const submitLog = async () => {
    formError.value = null
    try {
        const api = authStore.getApiClient()
        if (isEditMode.value) {
            await practiceLogStore.updatePracticeLog(api, editingLogId.value, {
                practice_type: logForm.practice_type,
                practiced_on: logForm.practiced_on,
                details: logForm.details,
                duration_minutes: logForm.duration_minutes,
                outcome: logForm.outcome,
            })
        } else {
            await practiceLogStore.createPracticeLog(api, {
                topic_id: logForm.topic_id,
                practice_type: logForm.practice_type,
                practiced_on: logForm.practiced_on,
                details: logForm.details,
                duration_minutes: logForm.duration_minutes,
                outcome: logForm.outcome,
            })
        }
        showModal.value = false
        await applyFilters()
        await showSuccess(isEditMode.value ? 'Practice log updated successfully.' : 'Practice log created successfully.')
    } catch (err) {
        formError.value = err.response?.data?.message || 'Failed to save practice log'
        await showError(formError.value)
    }
}

const deleteLog = async (id) => {
    const confirmed = await showConfirm('Delete this practice log?', 'Confirm Delete')
    if (!confirmed) return

    try {
        const api = authStore.getApiClient()
        await practiceLogStore.deletePracticeLog(api, id)
        await showSuccess('Practice log deleted successfully.')
    } catch (err) {
        formError.value = err.response?.data?.message || 'Failed to delete practice log'
        await showError(formError.value)
    }
}

const previousPage = () => {
    if (pagination.value.page > 1) {
        practiceLogStore.pagination.page--
        applyFilters()
    }
}

const nextPage = () => {
    if (pagination.value.page < pagination.value.lastPage) {
        practiceLogStore.pagination.page++
        applyFilters()
    }
}

onMounted(async () => {
    await applyFilters()
})
</script>
