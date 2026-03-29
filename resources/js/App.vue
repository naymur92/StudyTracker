<template>
    <div id="app" class="min-h-screen bg-gray-50">
        <div v-if="sessionLoading" class="min-h-screen flex items-center justify-center">
            <div class="inline-block h-8 w-8 animate-spin rounded-full border-b-2 border-primary-600"></div>
        </div>
        <router-view v-else />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const sessionLoading = ref(true)
const route = useRoute()
const router = useRouter()

authStore.initializeApiInterceptors()

// Set OAuth credentials from environment if available
const clientId = import.meta.env.VITE_OAUTH_CLIENT_ID
const clientSecret = import.meta.env.VITE_OAUTH_CLIENT_SECRET

if (clientId && clientSecret) {
    authStore.setClientCredentials(clientId, clientSecret)
}

onMounted(async () => {
    try {
        const restored = await authStore.restoreSession()

        if (!restored && route.meta.requiresAuth) {
            await router.replace({
                name: 'Home',
            })
        }
    } finally {
        sessionLoading.value = false
    }
})
</script>

<style scoped></style>
