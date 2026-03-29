<template>
    <div class="space-y-16 pb-8">
        <!-- App Tour Section -->
        <AppTour />

        <!-- CTA Section -->
        <div class="text-center space-y-6 py-8 bg-white rounded-xl shadow-sm border border-gray-100 px-6">
            <h3 class="text-2xl font-bold text-gray-900">Ready to start learning smarter?</h3>
            <p class="text-gray-600 max-w-lg mx-auto">
                Choose how you'd like to get started. Try the live demo to explore all features,
                or create your own account.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <router-link to="/auth/login"
                    class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                    Login
                </router-link>
                <button @click="handleDemoLogin" :disabled="demoLoading"
                    class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <span v-if="demoLoading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        Starting tour...
                    </span>
                    <span v-else>Tour This App</span>
                </button>
                <router-link to="/auth/register"
                    class="px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-black font-medium transition-colors">
                    Join Free
                </router-link>
            </div>
            <p v-if="demoError" class="text-sm text-red-600">{{ demoError }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppTour from '@/components/AppTour.vue'

const router = useRouter()
const authStore = useAuthStore()
const demoLoading = ref(false)
const demoError = ref(null)

const handleDemoLogin = async () => {
    demoLoading.value = true
    demoError.value = null

    try {
        await authStore.demoLogin()
        router.push({ name: 'Dashboard' })
    } catch (err) {
        demoError.value = err.msg || err.message || 'Demo login failed. Please try again.'
    } finally {
        demoLoading.value = false
    }
}
</script>
