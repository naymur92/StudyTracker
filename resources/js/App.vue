<template>
    <div id="app" class="min-h-screen bg-gray-50">
        <router-view />
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

onMounted(() => {
    // Initialize API client with stored token if available
    if (authStore.token) {
        const api = authStore.getApiClient()
        api.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`
    }

    // Set OAuth credentials from environment if available
    const clientId = import.meta.env.VITE_OAUTH_CLIENT_ID
    const clientSecret = import.meta.env.VITE_OAUTH_CLIENT_SECRET

    if (clientId && clientSecret) {
        authStore.setClientCredentials(clientId, clientSecret)
    }
})
</script>

<style scoped></style>
