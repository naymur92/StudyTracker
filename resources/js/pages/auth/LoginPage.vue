<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Sign In</h2>

        <div v-if="showResetSuccess" class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700">Password reset successful. Please sign in with your new password.</p>
        </div>

        <form @submit.prevent="handleLogin" class="space-y-4">
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="form.email" type="email" required class="input-base" placeholder="your@email.com" />
                <span v-if="errors.email" class="text-sm text-red-500">{{ errors.email[0] }}</span>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input v-model="form.password" type="password" required class="input-base" placeholder="••••••••" />
                <span v-if="errors.password" class="text-sm text-red-500">{{ errors.password[0] }}</span>
                <div class="mt-2 text-right">
                    <router-link to="/auth/forgot-password" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        Forgot password?
                    </router-link>
                </div>
            </div>

            <!-- Error message -->
            <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">{{ error }}</p>
                <p class="text-xs text-red-600 mt-1">Check that your OAuth credentials are configured in the auth store,
                    or set VITE_OAUTH_CLIENT_ID and VITE_OAUTH_CLIENT_SECRET environment variables.</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                class="w-full btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="loading" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" />
                    </svg>
                    Signing in...
                </span>
                <span v-else>Sign In</span>
            </button>
        </form>

        <!-- Demo credentials -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs text-blue-700 font-semibold">Demo Credentials</p>
            <p class="text-xs text-blue-600 mt-1">Email: user@example.com</p>
            <p class="text-xs text-blue-600">Password: Password@123</p>
        </div>

        <!-- Link to register -->
        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Don't have an account?
                <router-link to="/auth/register" class="text-primary-600 hover:text-primary-700 font-medium">
                    Register here
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref(null)
const showResetSuccess = ref(false)
const errors = reactive({})

const form = reactive({
    email: 'user@example.com',
    password: 'Password@123',
})

const handleLogin = async () => {
    loading.value = true
    error.value = null
    errors.email = null
    errors.password = null

    try {
        await authStore.login(form.email, form.password)
        router.push({ name: 'Dashboard' })
    } catch (err) {
        if (err.errors) {
            Object.assign(errors, err.errors)
        } else {
            error.value = err.message || 'Login failed. Please try again.'
        }
    } finally {
        loading.value = false
    }
}

// Initialize OAuth credentials from environment or set defaults
onMounted(() => {
    showResetSuccess.value = route.query.reset === '1'

    const clientId = import.meta.env.VITE_OAUTH_CLIENT_ID
    const clientSecret = import.meta.env.VITE_OAUTH_CLIENT_SECRET

    if (clientId && clientSecret) {
        authStore.setClientCredentials(clientId, clientSecret)
    }
    // If not set via env, credentials must be set manually in the app
})
</script>
