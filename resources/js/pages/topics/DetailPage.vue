<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <router-link to="/topics" class="text-primary-600 hover:text-primary-700 font-medium">
                ← Back to Topics
            </router-link>
            <div class="flex items-center gap-3">
                <router-link :to="`/topics/${route.params.id}/edit`"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Edit
                </router-link>
                <button @click="deleteTopic"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Delete
                </button>
            </div>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700">{{ error }}</p>
        </div>

        <!-- Topic detail -->
        <div v-else-if="topic" class="space-y-6">
            <!-- Main info -->
            <div class="bg-white rounded-lg shadow p-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ topic.title }}</h1>
                        <p class="text-gray-600 mt-2">{{ topic.description }}</p>
                    </div>
                    <span
                        :class="['px-3 py-1 rounded-full text-xs font-semibold', getDifficultyColor(topic.difficulty)]">
                        {{ capitalizeFirst(topic.difficulty) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-6 border-t border-gray-200">
                    <div>
                        <p class="text-gray-600 text-sm">Category</p>
                        <p class="font-semibold text-gray-900">{{ topic.category?.name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Status</p>
                        <p class="font-semibold text-gray-900">{{ capitalizeFirst(topic.status) }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">Tasks</p>
                        <p class="font-semibold text-gray-900">{{ studyTasks.length || 0 }}</p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm">First Study</p>
                        <p class="font-semibold text-gray-900">{{ formatDate(topic.first_study_date) }}</p>
                    </div>
                </div>
            </div>

            <!-- Tasks -->
            <div class="bg-white rounded-lg shadow p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Study Tasks</h2>
                <div v-if="!studyTasks || studyTasks.length === 0" class="text-center py-8">
                    <p class="text-gray-600">No tasks for this topic yet.</p>
                </div>
                <div v-else class="space-y-3">
                    <div v-for="task in studyTasks" :key="task.id" class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ task.task_type_label ||
                                    capitalizeFirst(task.task_type) }}</p>
                                <p class="text-sm text-gray-600">Scheduled: {{ formatDate(task.scheduled_date) }}</p>
                            </div>
                            <span
                                :class="['px-3 py-1 rounded-full text-xs font-semibold', getTaskStatusColor(task.status)]">
                                {{ capitalizeFirst(task.status) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Practice Logs -->
            <div class="bg-white rounded-lg shadow p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Practice History</h2>
                <div v-if="!practiceLogs || practiceLogs.length === 0" class="text-center py-8">
                    <p class="text-gray-600">No practice logs yet.</p>
                </div>
                <div v-else class="space-y-3">
                    <div v-for="log in practiceLogs" :key="log.id" class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="font-medium text-gray-900">{{ capitalizeFirst(log.practice_type) }}</p>
                                <p class="text-sm text-gray-600">{{ log.details }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ formatDate(log.practiced_on) }} • {{
                                    log.duration_minutes }} min</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useTopicStore } from '@/stores/topics'
import { formatDate as fmtDate } from 'date-fns'
import { showConfirm, showError, showSuccess } from '@/helpers/alerts'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const topicStore = useTopicStore()

const loading = ref(false)
const error = ref(null)
const topic = ref(null)
const studyTasks = ref([])
const practiceLogs = ref([])

const capitalizeFirst = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''
const formatDate = (date) => date ? fmtDate(new Date(date), 'MMM d, yyyy') : 'N/A'

const getDifficultyColor = (difficulty) => {
    const colors = {
        easy: 'bg-success-100 text-success-800',
        medium: 'bg-yellow-100 text-yellow-800',
        hard: 'bg-red-100 text-red-800',
    }
    return colors[difficulty] || 'bg-gray-100 text-gray-800'
}

const getTaskStatusColor = (status) => {
    const colors = {
        pending: 'bg-gray-100 text-gray-800',
        completed: 'bg-success-100 text-success-800',
        skipped: 'bg-gray-100 text-gray-800',
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const deleteTopic = async () => {
    const confirmed = await showConfirm('Are you sure you want to delete this topic?', 'Confirm Delete')
    if (!confirmed) return

    try {
        const api = authStore.getApiClient()
        await topicStore.deleteTopic(api, route.params.id)
        await showSuccess('Topic deleted successfully.')
        router.push({ name: 'Topics' })
    } catch (err) {
        error.value = 'Failed to delete topic'
        await showError(error.value)
    }
}

onMounted(async () => {
    loading.value = true
    try {
        const api = authStore.getApiClient()
        await topicStore.fetchTopic(api, route.params.id)
        topic.value = topicStore.topic
        studyTasks.value = topicStore.studyTasks || []
        practiceLogs.value = topicStore.practiceLogs || []
    } catch (err) {
        error.value = 'Failed to load topic'
        await showError(error.value)
    } finally {
        loading.value = false
    }
})
</script>
