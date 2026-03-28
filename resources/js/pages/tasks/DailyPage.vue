<template>
    <div class="space-y-6">
        <h1 class="text-3xl font-bold text-gray-900">Daily Tasks</h1>

        <!-- Date Navigation -->
        <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
            <button @click="previousDay" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div class="text-center">
                <p class="font-semibold text-gray-900">{{ formatDate(selectedDate) }}</p>
            </div>
            <button @click="nextDay" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Tasks -->
        <div v-else class="space-y-6">
            <!-- Group by type -->
            <div v-for="group in taskGroups" :key="group.type" class="space-y-3">
                <h3 class="text-lg font-semibold text-gray-900 capitalize">{{ group.label }}</h3>
                <div v-if="group.tasks.length === 0" class="text-center py-4 text-gray-500">No tasks</div>
                <div v-for="task in group.tasks" :key="task.id" class="card p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4 flex-1">
                        <input type="checkbox" :checked="task.status === 'completed'"
                            :disabled="task.status === 'completed' || task.status === 'skipped'"
                            @change="completeTask(task)" class="w-5 h-5 text-primary-600 rounded" />
                        <div>
                            <p class="font-medium text-gray-900">{{ task.topic?.title || task.topic_title || task.title
                                }}</p>
                            <p class="text-sm text-gray-600">{{ task.notes }}</p>
                        </div>
                    </div>
                    <span :class="['px-3 py-1 rounded text-xs font-semibold', getStatusColor(task.status)]">
                        {{ capitalizeFirst(task.status) }}
                    </span>
                    <div class="flex items-center gap-2 ml-4" v-if="task.status !== 'completed'">
                        <button v-if="task.status === 'pending' || task.status === 'missed'" @click="skipTask(task)"
                            class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200">
                            Skip
                        </button>
                        <button v-if="task.status !== 'skipped'" @click="openRescheduleModal(task)"
                            class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700 hover:bg-blue-200">
                            Reschedule
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showRescheduleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
                <h2 class="text-xl font-bold text-gray-900 mb-3">Reschedule Task</h2>
                <p class="text-sm text-gray-600 mb-4">{{ selectedTask?.title || selectedTask?.topic_title }}</p>
                <form @submit.prevent="submitReschedule" class="space-y-4">
                    <input v-model="rescheduleDate" type="date" class="input-base" required />
                    <div v-if="actionError" class="text-sm text-red-700">{{ actionError }}</div>
                    <div class="flex gap-3">
                        <button type="submit" class="btn-primary flex-1">Save</button>
                        <button type="button" @click="showRescheduleModal = false" class="btn-secondary flex-1">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTaskStore } from '@/stores/tasks'
import { formatDate as fmtDate, addDays } from 'date-fns'
import { showError, showSuccess } from '@/helpers/alerts'

const authStore = useAuthStore()
const taskStore = useTaskStore()

const loading = ref(false)
const selectedDate = ref(new Date().toISOString().split('T')[0])
const tasks = ref([])
const showRescheduleModal = ref(false)
const selectedTask = ref(null)
const rescheduleDate = ref(new Date().toISOString().split('T')[0])
const actionError = ref(null)

const formatDate = (date) => fmtDate(new Date(date), 'EEEE, MMM d, yyyy')
const capitalizeFirst = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-gray-100 text-gray-800',
        completed: 'bg-success-100 text-success-800',
        skipped: 'bg-amber-100 text-amber-800',
        missed: 'bg-red-100 text-red-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const taskGroups = computed(() => {
    // New API shape: { date, summary, groups: { learn: [...], revision_1: [...] } }
    if (tasks.value && !Array.isArray(tasks.value) && tasks.value.groups) {
        return Object.entries(tasks.value.groups).map(([type, list]) => ({
            type,
            label: list?.[0]?.type_label || type,
            tasks: Array.isArray(list) ? list : [],
        }))
    }

    // Backward-compatible fallback for old flat array shape
    const groups = {}
    const taskList = Array.isArray(tasks.value) ? tasks.value : []
    taskList.forEach(task => {
        if (!groups[task.task_type]) {
            groups[task.task_type] = {
                type: task.task_type,
                label: task.type_label || task.task_type,
                tasks: [],
            }
        }
        groups[task.task_type].tasks.push(task)
    })
    return Object.values(groups)
})

const fetchTasks = async () => {
    loading.value = true
    try {
        const api = authStore.getApiClient()
        await taskStore.fetchDailyTasks(api, selectedDate.value)
        tasks.value = taskStore.tasks
    } catch (err) {
        console.error(err)
        await showError('Failed to load tasks')
    } finally {
        loading.value = false
    }
}

const previousDay = () => {
    const date = new Date(selectedDate.value)
    selectedDate.value = addDays(date, -1).toISOString().split('T')[0]
    fetchTasks()
}

const nextDay = () => {
    const date = new Date(selectedDate.value)
    selectedDate.value = addDays(date, 1).toISOString().split('T')[0]
    fetchTasks()
}

const completeTask = async (task) => {
    try {
        const api = authStore.getApiClient()
        if (task.status === 'completed') {
            // Skip
        } else {
            await taskStore.completeTask(api, task.id, {
                notes: '',
                difficulty_feedback: 'medium',
            })
            await fetchTasks()
            await showSuccess('Task marked as completed.')
        }
    } catch (err) {
        console.error(err)
        await showError(err.response?.data?.msg || err.response?.data?.message || 'Failed to complete task')
    }
}

const skipTask = async (task) => {
    actionError.value = null
    try {
        const api = authStore.getApiClient()
        await taskStore.skipTask(api, task.id, {
            reason: '',
        })
        await fetchTasks()
        await showSuccess('Task skipped successfully.')
    } catch (err) {
        actionError.value = err.response?.data?.msg || err.response?.data?.message || 'Failed to skip task'
        await showError(actionError.value)
    }
}

const openRescheduleModal = (task) => {
    selectedTask.value = task
    rescheduleDate.value = task.scheduled_date || new Date().toISOString().split('T')[0]
    actionError.value = null
    showRescheduleModal.value = true
}

const submitReschedule = async () => {
    if (!selectedTask.value) return

    actionError.value = null
    try {
        const api = authStore.getApiClient()
        await taskStore.rescheduleTask(api, selectedTask.value.id, rescheduleDate.value)
        showRescheduleModal.value = false
        await fetchTasks()
        await showSuccess('Task rescheduled successfully.')
    } catch (err) {
        actionError.value = err.response?.data?.msg || err.response?.data?.message || 'Failed to reschedule task'
        await showError(actionError.value)
    }
}

onMounted(() => {
    fetchTasks()
})
</script>
