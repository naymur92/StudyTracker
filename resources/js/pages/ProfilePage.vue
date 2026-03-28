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
                <form @submit.prevent="handleUpdateProfile" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                        <input v-model="profileForm.name" type="text" class="input-base" required />
                        <span v-if="profileErrors.name" class="text-sm text-red-500">{{ profileErrors.name[0] }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input :value="profile.email" type="email" class="input-base bg-gray-50" disabled />
                        <p class="text-xs text-gray-500 mt-1">Email cannot be changed.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                        <p class="py-2 text-gray-900">{{ formatDate(profile.created_at || profile.email_verified_at) }}
                        </p>
                    </div>

                    <div class="pt-2">
                        <button type="submit" :disabled="updatingProfile"
                            class="px-5 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span v-if="updatingProfile">Saving...</span>
                            <span v-else>Update Profile</span>
                        </button>
                    </div>
                </form>

                <div v-if="profileMessage" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ profileMessage }}
                </div>
                <div v-if="profileError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ profileError }}
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

            <!-- Change Password -->
            <div class="border-b border-gray-200 pb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Change Password</h2>
                <form @submit.prevent="handleChangePassword" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <input v-model="passwordForm.current_password" type="password" class="input-base" required />
                        <span v-if="passwordErrors.current_password" class="text-sm text-red-500">{{
                            passwordErrors.current_password[0] }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <input v-model="passwordForm.new_password" type="password" class="input-base" required />
                        <span v-if="passwordErrors.new_password" class="text-sm text-red-500">{{
                            passwordErrors.new_password[0] }}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <input v-model="passwordForm.new_password_confirmation" type="password" class="input-base" required />
                        <span v-if="passwordErrors.new_password_confirmation" class="text-sm text-red-500">{{
                            passwordErrors.new_password_confirmation[0] }}</span>
                    </div>

                    <button type="submit" :disabled="changingPassword"
                        class="px-5 py-2 bg-gray-900 text-white rounded-lg hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="changingPassword">Updating...</span>
                        <span v-else>Change Password</span>
                    </button>
                </form>

                <div v-if="passwordMessage" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                    {{ passwordMessage }}
                </div>
                <div v-if="passwordError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
                    {{ passwordError }}
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
const profileForm = ref({ name: '' })
const passwordForm = ref({
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
})

const updatingProfile = ref(false)
const changingPassword = ref(false)

const profileMessage = ref('')
const profileError = ref('')
const passwordMessage = ref('')
const passwordError = ref('')
const profileErrors = ref({})
const passwordErrors = ref({})

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

const handleUpdateProfile = async () => {
    updatingProfile.value = true
    profileMessage.value = ''
    profileError.value = ''
    profileErrors.value = {}

    try {
        const api = authStore.getApiClient()
        const response = await userStore.updateProfile(api, {
            name: profileForm.value.name,
        })

        profile.value = {
            ...profile.value,
            ...(response.data || {}),
        }

        profileMessage.value = response.msg || 'Profile updated successfully.'
    } catch (err) {
        if (err.errors) {
            profileErrors.value = err.errors
        } else {
            profileError.value = err.msg || err.message || 'Failed to update profile.'
        }
    } finally {
        updatingProfile.value = false
    }
}

const handleChangePassword = async () => {
    changingPassword.value = true
    passwordMessage.value = ''
    passwordError.value = ''
    passwordErrors.value = {}

    try {
        const api = authStore.getApiClient()
        const response = await userStore.changePassword(api, {
            current_password: passwordForm.value.current_password,
            new_password: passwordForm.value.new_password,
            new_password_confirmation: passwordForm.value.new_password_confirmation,
        })

        passwordMessage.value = response.msg || 'Password changed successfully.'
        passwordForm.value = {
            current_password: '',
            new_password: '',
            new_password_confirmation: '',
        }
    } catch (err) {
        if (err.errors) {
            passwordErrors.value = err.errors
        } else {
            passwordError.value = err.msg || err.message || 'Failed to change password.'
        }
    } finally {
        changingPassword.value = false
    }
}

onMounted(async () => {
    loading.value = true
    try {
        const api = authStore.getApiClient()
        await userStore.fetchProfile(api)
        profile.value = userStore.profile
        profileForm.value.name = userStore.profile?.name || ''
    } catch (err) {
        console.error(err)
    } finally {
        loading.value = false
    }
})
</script>
