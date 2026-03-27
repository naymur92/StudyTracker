<template>
    <div
        class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-success-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-primary-600 mb-2">StudyTracker</h1>
                <p class="text-gray-600">Learn smart, revise systematically</p>
            </div>

            <!-- Content -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <router-view />
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-sm text-gray-600">
                <p>{{ currentYear }} StudyTracker. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

<style scoped></style>
