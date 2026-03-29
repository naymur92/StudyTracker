<template>
    <div v-if="authStore.isDemoUser"
        class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-sm text-amber-800">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>You're using a <strong>demo account</strong>. Data resets daily.</span>
        </div>
        <router-link to="/auth/register"
            class="text-xs font-semibold text-amber-900 bg-amber-200 hover:bg-amber-300 px-3 py-1 rounded-md transition-colors whitespace-nowrap">
            Create Account
        </router-link>
    </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>
