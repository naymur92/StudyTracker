<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-600">Welcome back! Here's your learning overview.</p>
        </div>

        <!-- Date Navigation -->
        <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
            <button @click="previousDay" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <div class="text-center">
                <p class="text-sm text-gray-600">{{ formatDate(selectedDate) }}</p>
                <p class="text-lg font-semibold text-gray-900">{{ dayName }}</p>
            </div>

            <button @click="nextDay" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <button @click="goToToday"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors text-sm font-medium">
                Today
            </button>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
            <p class="mt-4 text-gray-600">Loading your dashboard...</p>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700">{{ error }}</p>
        </div>

        <!-- Dashboard content -->
        <div v-else class="space-y-6">
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Tasks Today</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.today_total || 0 }}</p>
                        </div>
                        <div class="bg-primary-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                <path fill-rule="evenodd"
                                    d="M4 5a2 2 0 012-2 1 1 0 000 2H6a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 1h8v8H7V6z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Completed</p>
                            <p class="text-3xl font-bold text-success-600 mt-1">{{ stats.completed_today || 0 }}</p>
                        </div>
                        <div class="bg-success-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Topics</p>
                            <p class="text-3xl font-bold text-gray-900 mt-1">{{ stats.active_topics || 0 }}</p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.669 0-3.218-.51-4.5-1.385A7.968 7.968 0 009 4.804z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Streak</p>
                            <p class="text-3xl font-bold text-orange-600 mt-1">{{ stats.streak || 0 }}</p>
                        </div>
                        <div class="bg-orange-100 p-3 rounded-lg">
                            <svg class="w-6 h-6 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                </path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Agenda -->
            <div class="card p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Today's Agenda</h2>

                <div v-if="!flattenedTasks || flattenedTasks.length === 0" class="text-center py-8">
                    <p class="text-gray-600">No tasks for today. Great job staying on top!</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="task in flattenedTasks" :key="task.id"
                        class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow cursor-pointer flex items-center justify-between">
                        <div class="flex items-center gap-4 flex-1">
                            <input type="checkbox" :checked="task.status === 'completed'"
                                :disabled="task.status === 'completed'" @change="() => toggleTask(task)"
                                class="w-5 h-5 text-primary-600 rounded focus:ring-2 focus:ring-primary-500" />
                            <div>
                                <p class="font-medium text-gray-900">{{ task.topic_title || task.title }}</p>
                                <p class="text-sm text-gray-600">{{ task.task_type }} • {{ task.notes }}</p>
                            </div>
                        </div>
                        <span :class="['px-3 py-1 rounded-full text-xs font-semibold', getStatusColor(task.status)]">
                            {{ capitalizeFirst(task.status) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTaskStore } from '@/stores/tasks'
import { formatDate as fmtDate, addDays, format } from 'date-fns'

const authStore = useAuthStore()
const taskStore = useTaskStore()

const loading = ref(false)
const error = ref(null)
const selectedDate = ref(new Date().toISOString().split('T')[0])
const stats = ref({})
const tasks = ref(null)

const flattenedTasks = computed(() => {
    if (!tasks.value || !tasks.value.groups) {
        return []
    }
    // Flatten all tasks from all groups
    return Object.values(tasks.value.groups).flat()
})

const dayName = computed(() => {
    const date = new Date(selectedDate.value)
    return format(date, 'EEEE')
})

const formatDate = (date) => {
    return fmtDate(new Date(date), 'MMM d, yyyy')
}

const capitalizeFirst = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-gray-100 text-gray-800',
        completed: 'bg-success-100 text-success-800',
        skipped: 'bg-gray-100 text-gray-800',
        overdue: 'bg-red-100 text-red-800',
    }
    return colors[status] || colors.pending
}

const previousDay = () => {
    const date = new Date(selectedDate.value)
    const prev = addDays(date, -1)
    selectedDate.value = prev.toISOString().split('T')[0]
    fetchDashboard()
}

const nextDay = () => {
    const date = new Date(selectedDate.value)
    const next = addDays(date, 1)
    selectedDate.value = next.toISOString().split('T')[0]
    fetchDashboard()
}

const goToToday = () => {
    selectedDate.value = new Date().toISOString().split('T')[0]
    fetchDashboard()
}

const fetchDashboard = async () => {
    loading.value = true
    error.value = null

    try {
        const api = authStore.getApiClient()
        const response = await api.get('/study/dashboard', {
            params: { date: selectedDate.value },
        })
        stats.value = response.data.data.stats || {}
        // Calculate today's total from summary
        if (response.data.data.agenda && response.data.data.agenda.summary) {
            stats.value.today_total = response.data.data.agenda.summary.total || 0
        }
        tasks.value = response.data.data.agenda || null
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load dashboard'
    } finally {
        loading.value = false
    }
}

const toggleTask = async (task) => {
    if (task.status === 'completed') {
        // Skip task
    } else {
        // Complete task
    }
}

onMounted(() => {
    fetchDashboard()
})
</script>
