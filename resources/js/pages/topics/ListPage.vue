<template>
    <div class="space-y-6">
        <!-- Header with Create button -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Topics</h1>
                <p class="text-gray-600">Manage your study topics</p>
            </div>
            <router-link to="/topics/create" class="btn-primary">
                + New Topic
            </router-link>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
            <input v-model="filters.search" type="text" placeholder="Search topics..." @input="handleSearch"
                class="input-base" />
            <select v-model="filters.status" @change="applyFilters" class="input-base">
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
            </select>
            <select v-model="filters.difficulty" @change="applyFilters" class="input-base">
                <option value="">All Difficulties</option>
                <option value="easy">Easy</option>
                <option value="medium">Medium</option>
                <option value="hard">Hard</option>
            </select>
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error state -->
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700">{{ error }}</p>
        </div>

        <!-- Topics list -->
        <div v-else-if="topics && topics.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="topic in topics" :key="topic.id" class="card p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-900 flex-1">{{ topic.title }}</h3>
                    <span :class="['px-2 py-1 rounded text-xs font-semibold', getDifficultyColor(topic.difficulty)]">
                        {{ capitalizeFirst(topic.difficulty) }}
                    </span>
                </div>
                <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ topic.description }}</p>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>{{ topic.category?.name }}</span>
                    <span>{{ topic.task_count || 0 }} tasks</span>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center gap-3">
                    <router-link :to="`/topics/${topic.id}`" class="text-sm text-primary-600 hover:text-primary-700">
                        View
                    </router-link>
                    <router-link :to="`/topics/${topic.id}/edit`" class="text-sm text-blue-600 hover:text-blue-700">
                        Edit
                    </router-link>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="text-center py-12">
            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 12s4.5 5.747 10 5.747m0-13c5.5 0 10 4.745 10 5.747s-4.5 5.747-10 5.747m0-13v13m0-13C6.5 6.253 2 10.998 2 12s4.5 5.747 10 5.747m0 0c5.5 0 10-4.745 10-5.747s-4.5-5.747-10-5.747" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">No topics yet</h3>
            <p class="text-gray-600 mb-6">Create your first topic to get started</p>
            <router-link to="/topics/create" class="btn-primary">Create Topic</router-link>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="flex justify-center gap-2 mt-8">
            <button @click="previousPage" :disabled="pagination.page === 1"
                class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 hover:bg-gray-50 disabled:opacity-50">
                Previous
            </button>
            <div class="px-4 py-2">
                <p class="text-sm text-gray-600">
                    Page {{ pagination.page }} of {{ pagination.lastPage }}
                </p>
            </div>
            <button @click="nextPage" :disabled="pagination.page === pagination.lastPage"
                class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 hover:bg-gray-50 disabled:opacity-50">
                Next
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTopicStore } from '@/stores/topics'

const authStore = useAuthStore()
const topicStore = useTopicStore()

const loading = ref(false)
const error = ref(null)
const searchTimeout = ref(null)

const filters = ref({
    search: '',
    status: 'active',
    difficulty: '',
})

const topics = computed(() => topicStore.topics)
const pagination = computed(() => topicStore.pagination)

const capitalizeFirst = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1) : ''

const getDifficultyColor = (difficulty) => {
    const colors = {
        easy: 'bg-success-100 text-success-800',
        medium: 'bg-yellow-100 text-yellow-800',
        hard: 'bg-red-100 text-red-800',
    }
    return colors[difficulty] || 'bg-gray-100 text-gray-800'
}

const handleSearch = () => {
    clearTimeout(searchTimeout.value)
    searchTimeout.value = setTimeout(() => {
        topicStore.resetPagination()
        applyFilters()
    }, 500)
}

const applyFilters = async () => {
    loading.value = true
    error.value = null

    try {
        const api = authStore.getApiClient()
        topicStore.setFilters({
            search: filters.value.search,
            status: filters.value.status,
            difficulty: filters.value.difficulty || null,
        })
        await topicStore.fetchTopics(api)
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to load topics'
    } finally {
        loading.value = false
    }
}

const previousPage = async () => {
    if (pagination.value.page > 1) {
        topicStore.pagination.page--
        await applyFilters()
    }
}

const nextPage = async () => {
    if (pagination.value.page < pagination.value.lastPage) {
        topicStore.pagination.page++
        await applyFilters()
    }
}

onMounted(() => {
    applyFilters()
})
</script>
