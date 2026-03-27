<template>
    <div class="space-y-6 max-w-2xl">
        <h1 class="text-3xl font-bold text-gray-900">Profile</h1>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Profile Card -->
        <div v-else-if="profile" class="bg-white rounded-lg shadow p-8 space-y-6">
            <!-- User Info -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Account Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <p class="py-2 text-gray-900">{{ profile.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <p class="py-2 text-gray-900">{{ profile.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                        <p class="py-2 text-gray-900">{{ formatDate(profile.created_at || profile.email_verified_at) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Statistics</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="card p-4 text-center">
                        <p class="text-3xl font-bold text-primary-600">{{ profile.topics_count || 0 }}</p>
                        <p class="text-sm text-gray-600 mt-2">Topics</p>
                    </div>
                    <div class="card p-4 text-center">
                        <p class="text-3xl font-bold text-success-600">{{ profile.tasks_count || 0 }}</p>
                        <p class="text-sm text-gray-600 mt-2">Tasks</p>
                    </div>
                    <div class="card p-4 text-center">
                        <p class="text-3xl font-bold text-blue-600">{{ profile.practice_logs_count || 0 }}</p>
                        <p class="text-sm text-gray-600 mt-2">Practice Logs</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div>
                <button @click="logout"
                    class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Logout
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useUserStore } from '@/stores/user'
import { formatDate as fmtDate } from 'date-fns'

const router = useRouter()
const authStore = useAuthStore()
const userStore = useUserStore()

const loading = ref(false)
const profile = ref(null)

const formatDate = (date) => {
    if (!date) return 'N/A'

    const normalizedDate = typeof date === 'string' ? date.replace(' ', 'T') : date
    const parsedDate = new Date(normalizedDate)

    if (Number.isNaN(parsedDate.getTime())) return 'N/A'
    return fmtDate(parsedDate, 'MMM d, yyyy')
}

const logout = () => {
    authStore.logout()
    router.push({ name: 'Login' })
}

onMounted(async () => {
    loading.value = true
    try {
        const api = authStore.getApiClient()
        await userStore.fetchProfile(api)
        profile.value = userStore.profile
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
})
</script>
