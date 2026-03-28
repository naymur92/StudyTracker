<template>
    <div class="text-center">
        <div class="mb-4">
            <svg class="w-16 h-16 text-red-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Verification Failed</h2>
        <p class="text-gray-600 mb-6">{{ message }}</p>

        <div class="space-y-3">
            <router-link :to="resendLink" class="w-full btn-primary inline-flex justify-center">
                Go to verification page
            </router-link>

            <router-link to="/auth/login" class="w-full btn-secondary inline-flex justify-center">
                Back to login
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()

const message = computed(() => route.query.message || 'Verification token is invalid or expired.')

const resendLink = computed(() => {
    const query = {}

    if (typeof route.query.email === 'string' && route.query.email) {
        query.email = route.query.email
    }

    return {
        name: 'VerifyEmail',
        query,
    }
})
</script>