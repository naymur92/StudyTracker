<template>
    <router-link v-if="section" :to="`/app/guide#${section}`"
        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-700" :title="`User Guide: ${label}`">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Guide
    </router-link>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { mainNavigation } from '@/config/navigation'

const props = defineProps({
    // Explicit guide section; derived from the current route when omitted.
    section: { type: String, default: null },
})

const route = useRoute()

// The navigation item whose path is the longest prefix of the current path
// (so /app/topics/abc/edit maps to Topics). The guide page itself has no link.
const match = computed(() => {
    if (route.name === 'UserGuide') return null
    const path = route.path.replace(/\/+$/, '') || '/'
    return mainNavigation
        .filter((item) => item.name !== 'UserGuide')
        .filter((item) => path === item.to || (item.to !== '/app' && path.startsWith(`${item.to}/`)))
        .sort((a, b) => b.to.length - a.to.length)[0] || null
})

const section = computed(() => props.section || match.value?.guideSection || null)
const label = computed(() => match.value?.label || 'Getting started')
</script>
