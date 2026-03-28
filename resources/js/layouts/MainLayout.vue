<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile header -->
        <header class="sticky top-0 z-40 bg-white border-b border-gray-200 md:hidden">
            <div class="flex items-center justify-between px-4 py-4">
                <div class="flex items-center gap-2">
                    <img src="/favicon.svg" alt="StudyTracker" class="w-6 h-6" />
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
                        <img src="/favicon.svg" alt="StudyTracker" class="w-8 h-8" />
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
                        <svg :class="`w-5 h-5 ${item.icon}`" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="item.name === 'Dashboard'" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 16l4-4m0 0a9 9 0 11-12.99-12.99 9 9 0 0112.99 12.99z" />
                            <path v-else-if="item.name === 'DailyTasks'" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            <path v-else-if="item.name === 'Topics'" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6.253v13m0-13C6.5 6.253 2 10.998 2 12s4.5 5.747 10 5.747m0-13c5.5 0 10 4.745 10 5.747s-4.5 5.747-10 5.747m0-13v13m0-13C6.5 6.253 2 10.998 2 12s4.5 5.747 10 5.747m0 0c5.5 0 10-4.745 10-5.747s-4.5-5.747-10-5.747" />
                            <path v-else-if="item.name === 'Categories'" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            <path v-else-if="item.name === 'PracticeLogs'" stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            <path v-else-if="item.name === 'Calendar'" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            <path v-else-if="item.name === 'RevisionTemplates'" stroke-linecap="round"
                                stroke-linejoin="round" stroke-width="2"
                                d="M9.75 3a3 3 0 00-2.995 2.824L6.75 6v.086a2.25 2.25 0 01-1.062 1.914l-.074.044-.074.043a2.25 2.25 0 00-1.058 2.66l.026.08.026.08a2.25 2.25 0 010 1.506l-.026.08-.026.08a2.25 2.25 0 001.058 2.66l.074.043.074.044a2.25 2.25 0 011.062 1.914V18l.005.176A3 3 0 009.75 21h.5a3 3 0 002.995-2.824L13.25 18v-.086a2.25 2.25 0 011.062-1.914l.074-.044.074-.043a2.25 2.25 0 001.058-2.66l-.026-.08-.026-.08a2.25 2.25 0 010-1.506l.026-.08.026-.08a2.25 2.25 0 00-1.058-2.66l-.074-.043-.074-.044a2.25 2.25 0 01-1.062-1.914V6l-.005-.176A3 3 0 0010.25 3h-.5zM10 9.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z" />
                            <path v-else-if="item.name === 'Profile'" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2"
                                d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <span>{{ item.label }}</span>
                    </router-link>
                </nav>

                <!-- User section -->
                <div class="p-4 border-t border-gray-200 bg-white mt-auto">
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
                    <router-view />
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const sidebarOpen = ref(false)

const navigationItems = [
    { name: 'Dashboard', label: 'Dashboard', to: '/', icon: 'IconDashboard' },
    { name: 'DailyTasks', label: 'Daily Tasks', to: '/tasks', icon: 'IconTasks' },
    { name: 'Topics', label: 'Topics', to: '/topics', icon: 'IconTopics' },
    { name: 'Categories', label: 'Categories', to: '/categories', icon: 'IconCategories' },
    { name: 'PracticeLogs', label: 'Practice Logs', to: '/practice-logs', icon: 'IconLogs' },
    { name: 'Calendar', label: 'Calendar', to: '/calendar', icon: 'IconCalendar' },
    { name: 'RevisionTemplates', label: 'Revision Templates', to: '/revision-templates', icon: 'IconSettings' },
    { name: 'Profile', label: 'Profile', to: '/profile', icon: 'IconProfile' },
]

const isActive = (item) => {
    return route.path === item.to || route.name === item.name
}

const handleLogout = () => {
    authStore.logout()
    router.push({ name: 'Login' })
}
</script>

<style scoped></style>
