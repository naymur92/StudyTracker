<template>
    <div class="min-h-screen bg-gradient-to-br from-primary-50 via-white to-success-50">
        <!-- Header -->
        <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-sm border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 py-3 md:py-4 flex items-center justify-between">
                <router-link to="/" class="flex items-center gap-2">
                    <img src="/favicon.svg" alt="StudyTracker" class="w-6 h-6 md:w-7 md:h-7" />
                    <h1 class="text-lg md:text-xl font-bold text-primary-600">StudyTracker</h1>
                </router-link>

                <!-- Desktop nav -->
                <nav class="hidden sm:flex items-center gap-3">
                    <router-link to="/about"
                        class="text-sm text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                        About
                    </router-link>
                    <template v-if="isLoggedIn">
                        <router-link to="/app"
                            class="text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 px-4 py-2 rounded-lg transition-colors">
                            Go To App
                        </router-link>
                    </template>
                    <template v-else>
                        <router-link to="/auth/login"
                            class="text-sm font-medium text-primary-600 hover:text-primary-700 px-3 py-2 rounded-lg hover:bg-primary-50 transition-colors">
                            Login
                        </router-link>
                        <router-link to="/auth/register"
                            class="text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 px-4 py-2 rounded-lg transition-colors">
                            Join Free
                        </router-link>
                    </template>
                </nav>

                <!-- Mobile hamburger -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="sm:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Mobile menu -->
            <nav v-if="mobileMenuOpen" class="sm:hidden border-t border-gray-100 bg-white px-4 py-3 space-y-1">
                <router-link to="/about" @click="mobileMenuOpen = false"
                    class="block text-sm text-gray-600 hover:text-gray-900 px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                    About
                </router-link>
                <template v-if="isLoggedIn">
                    <router-link to="/app/dashboard" @click="mobileMenuOpen = false"
                        class="block text-sm font-medium text-center text-white bg-primary-600 hover:bg-primary-700 px-4 py-2 rounded-lg transition-colors">
                        Go To App
                    </router-link>
                </template>
                <template v-else>
                    <router-link to="/auth/login" @click="mobileMenuOpen = false"
                        class="block text-sm font-medium text-primary-600 hover:text-primary-700 px-3 py-2 rounded-lg hover:bg-primary-50 transition-colors">
                        Login
                    </router-link>
                    <router-link to="/auth/register" @click="mobileMenuOpen = false"
                        class="block text-sm font-medium text-center text-white bg-primary-600 hover:bg-primary-700 px-4 py-2 rounded-lg transition-colors">
                        Join Free
                    </router-link>
                </template>
            </nav>
        </header>

        <!-- Content -->
        <main class="max-w-6xl mx-auto px-4 py-6 md:py-8">
            <router-view />
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 bg-white/60">
            <div class="max-w-6xl mx-auto px-4 py-4 md:py-6 text-center text-xs md:text-sm text-gray-500">
                <p>&copy; {{ currentYear }} StudyTracker. Built for learners who want to remember what they study.</p>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isLoggedIn = computed(() => !!authStore.token)
const currentYear = computed(() => new Date().getFullYear())
const mobileMenuOpen = ref(false)
</script>
