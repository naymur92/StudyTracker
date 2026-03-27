<template>
    <div
        class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-success-50 flex items-center justify-center p-4">
        <div class="text-center">
            <div class="mb-8">
                <svg class="w-24 h-24 text-primary-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-5xl font-bold text-gray-900 mb-4">404</h1>
            <p class="text-xl text-gray-600 mb-8">Page not found</p>
            <router-link to="/" class="btn-primary">
                Back to Dashboard
            </router-link>
        </div>
    </div>
</template>

<script setup>
</script>
