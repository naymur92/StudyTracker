<template>
    <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Create Account</h2>

        <form @submit.prevent="handleRegister" class="space-y-4">
            <!-- Name -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input v-model="form.name" type="text" required class="input-base" placeholder="John Doe" />
                <span v-if="errors.name" class="text-sm text-red-500">{{ errors.name[0] }}</span>
            </div>

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
                <p class="text-xs text-gray-500 mt-1">Minimum 8 characters with uppercase, lowercase, number and symbol
                </p>
                <span v-if="errors.password" class="text-sm text-red-500">{{ errors.password[0] }}</span>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <input v-model="form.password_confirmation" type="password" required class="input-base"
                    placeholder="••••••••" />
                <span v-if="errors.password_confirmation" class="text-sm text-red-500">{{
            errors.password_confirmation[0] }}</span>
            </div>

            <!-- Error message -->
            <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-700">{{ error }}</p>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="loading"
                class="w-full btn-primary disabled:opacity-50 disabled:cursor-not-allowed">
                <span v-if="loading">Creating account...</span>
                <span v-else>Create Account</span>
            </button>
        </form>

        <!-- Link to login -->
        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Already have an account?
                <router-link to="/auth/login" class="text-primary-600 hover:text-primary-700 font-medium">
                    Sign in here
                </router-link>
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const error = ref(null)
const errors = reactive({})

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
})

const handleRegister = async () => {
    loading.value = true
    error.value = null
    Object.keys(errors).forEach(key => delete errors[key])

    try {
        const result = await authStore.register(
            form.name,
            form.email,
            form.password,
            form.password_confirmation
        )
        router.push({
            name: 'VerifyEmail',
            query: {
                email: form.email,
                message: result.msg || result.message || 'Registration successful. Please verify your email to activate your account.',
            },
        })
    } catch (err) {
        if (err.errors) {
            Object.assign(errors, err.errors)
        } else {
            error.value = err.msg || err.message || 'Registration failed. Please try again.'
        }
    } finally {
        loading.value = false
    }
}
</script>
