<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Verify Email</h2>

        <div v-if="!verified" class="space-y-4">
            <p class="text-gray-600">
                We've sent a verification link to <strong>{{ email }}</strong>. Please check your email and click the
                verification link.
            </p>

            <div v-if="initialMessage" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700">{{ initialMessage }}</p>
            </div>

            <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-sm text-blue-700">Didn't receive the email? Check your spam folder or try again.</p>
            </div>

            <form @submit.prevent="handleResendVerification" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input v-model="email" type="email" required class="input-base" placeholder="your@email.com" />
                </div>

                <button type="submit" :disabled="loading" class="w-full btn-secondary disabled:opacity-50">
                    <span v-if="loading">Sending...</span>
                    <span v-else>Resend Verification Email</span>
                </button>
            </form>

            <div v-if="success" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700">{{ success }}</p>
            </div>

            <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">{{ error }}</p>
            </div>

            <!-- Back to login -->
            <div class="text-center">
                <router-link to="/auth/login" class="text-primary-600 hover:text-primary-700 font-medium">
                    Back to login
                </router-link>
            </div>
        </div>

        <div v-else class="text-center">
            <div class="mb-4">
                <svg class="w-16 h-16 text-success-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Email Verified!</h3>
            <p class="text-gray-600 mb-4">Your email has been successfully verified.</p>
            <router-link to="/auth/login" class="btn-primary">
                Go to Login
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref(null)
const success = ref(null)
const verified = ref(false)
const email = ref(router.currentRoute.value.query.email || '')
const initialMessage = computed(() => router.currentRoute.value.query.message || null)

const handleResendVerification = async () => {
    loading.value = true
    error.value = null
    success.value = null

    try {
        await authStore.resendVerification(email.value)
        success.value = 'Verification email sent! Check your inbox.'
    } catch (err) {
        error.value = err.msg || err.message || 'Failed to resend verification email'
    } finally {
        loading.value = false
    }
}
</script>
