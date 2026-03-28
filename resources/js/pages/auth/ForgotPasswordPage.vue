<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2">Forgot Password</h2>
        <p class="text-sm text-gray-600 mb-6">Request a reset code and set your new password.</p>

        <div class="mb-6 flex gap-2 text-xs font-medium">
            <span :class="['px-3 py-1 rounded-full', step === 1 ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600']">
                1. Request code
            </span>
            <span :class="['px-3 py-1 rounded-full', step === 2 ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-600']">
                2. Verify and reset
            </span>
        </div>

        <form v-if="step === 1" @submit.prevent="handleRequestCode" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="requestForm.email" type="email" required class="input-base" placeholder="your@email.com" />
                <span v-if="errors.email" class="text-sm text-red-500">{{ errors.email[0] }}</span>
            </div>

            <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">{{ error }}</p>
            </div>

            <div v-if="success" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-700">{{ success }}</p>
            </div>

            <button type="submit" :disabled="loading" class="w-full btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="loading">Sending code...</span>
                <span v-else>Send reset code</span>
            </button>
        </form>

        <form v-else @submit.prevent="handleVerifyCode" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="verifyForm.email" type="email" required class="input-base" placeholder="your@email.com" />
                <span v-if="errors.email" class="text-sm text-red-500">{{ errors.email[0] }}</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Verification Code</label>
                <input v-model="verifyForm.code" type="text" inputmode="numeric" maxlength="6" required class="input-base" placeholder="123456" />
                <span v-if="errors.code" class="text-sm text-red-500">{{ errors.code[0] }}</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input v-model="verifyForm.password" type="password" required class="input-base" placeholder="••••••••" />
                <span v-if="errors.password" class="text-sm text-red-500">{{ errors.password[0] }}</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input v-model="verifyForm.password_confirmation" type="password" required class="input-base" placeholder="••••••••" />
                <span v-if="errors.password_confirmation" class="text-sm text-red-500">{{ errors.password_confirmation[0] }}</span>
            </div>

            <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">{{ error }}</p>
            </div>

            <button type="submit" :disabled="loading" class="w-full btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="loading">Resetting password...</span>
                <span v-else>Reset password</span>
            </button>

            <button type="button" @click="step = 1" class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Back to request code
            </button>
        </form>

        <div class="mt-6 text-center">
            <router-link to="/auth/login" class="text-primary-600 hover:text-primary-700 font-medium text-sm">
                Back to sign in
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const step = ref(1)
const loading = ref(false)
const error = ref(null)
const success = ref(null)
const errors = reactive({})

const requestForm = reactive({
    email: '',
})

const verifyForm = reactive({
    email: '',
    code: '',
    password: '',
    password_confirmation: '',
})

const resetErrors = () => {
    Object.keys(errors).forEach((key) => {
        delete errors[key]
    })
}

const handleRequestCode = async () => {
    loading.value = true
    error.value = null
    success.value = null
    resetErrors()

    try {
        const result = await authStore.requestForgotPassword(requestForm.email)
        success.value = result.msg || 'If this email exists, a password reset code has been sent.'

        verifyForm.email = requestForm.email
        step.value = 2
    } catch (err) {
        if (err.errors) {
            Object.assign(errors, err.errors)
        } else {
            error.value = err.msg || err.message || 'Failed to send reset code.'
        }
    } finally {
        loading.value = false
    }
}

const handleVerifyCode = async () => {
    loading.value = true
    error.value = null
    success.value = null
    resetErrors()

    try {
        await authStore.verifyForgotPassword(
            verifyForm.email,
            verifyForm.code,
            verifyForm.password,
            verifyForm.password_confirmation,
        )

        router.push({ name: 'Login', query: { reset: '1' } })
    } catch (err) {
        if (err.errors) {
            Object.assign(errors, err.errors)
        } else {
            error.value = err.msg || err.message || 'Failed to reset password.'
        }
    } finally {
        loading.value = false
    }
}
</script>
