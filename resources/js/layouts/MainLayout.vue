<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile header -->
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 md:hidden">
            <div class="flex items-center justify-between px-4 py-4">
                <div class="flex items-center gap-2">
                    <img src="/icon_512x512.png" alt="StudyTracker" class="w-6 h-6" />
                    <h1 class="text-lg font-bold text-primary-600">StudyTracker</h1>
                </div>
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </header>

        <div class="flex min-h-screen">
            <!-- Sidebar -->
            <nav :class="[
                    'fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform md:translate-x-0 md:static md:min-h-screen overflow-y-auto flex flex-col',
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                ]">
                <!-- Logo -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <img src="/icon_512x512.png" alt="StudyTracker" class="w-8 h-8" />
                        <h1 class="text-xl font-bold text-primary-600">StudyTracker</h1>
                    </div>
                    <button @click="sidebarOpen = false" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="px-3 py-4 pb-28">
                    <router-link v-for="item in navigationItems" :key="item.name" :to="item.to"
                        @click="sidebarOpen = false" :class="[
                    'flex items-center gap-3 px-4 py-3 rounded-lg transition-colors mb-2',
                    isActive(item)
                        ? 'bg-primary-50 text-primary-600 font-medium'
                        : 'text-gray-700 hover:bg-gray-100'
                ]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                        </svg>
                        <span>{{ item.label }}</span>
                    </router-link>
                </nav>

                <!-- User section -->
                <div class="p-4 border-t border-gray-200 bg-white mt-auto">
                    <router-link to="/about" @click="sidebarOpen = false"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors mb-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>About</span>
                    </router-link>
                    <button @click="handleLogout"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </div>
            </nav>

            <!-- Overlay for mobile -->
            <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50 md:hidden" />

            <!-- Main content -->
            <main class="flex-1 overflow-y-auto">
                <div class="px-4 md:px-8 py-4 md:py-8">
                    <DemoBanner class="mb-4" />
                    <div class="flex justify-end mb-2">
                        <HelpLink />
                    </div>
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import HelpLink from '@/components/HelpLink.vue'
import { mainNavigation } from '@/config/navigation'
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import DemoBanner from '@/components/DemoBanner.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const sidebarOpen = ref(false)

const navigationItems = mainNavigation

const isActive = (item) => {
    return route.path === item.to || route.name === item.name
}

const handleLogout = () => {
    authStore.logout()
    router.push({ name: 'Home' })
}
</script>

<style scoped></style>
