<template>
    <div class="space-y-16 pb-8">
        <!-- About Header -->
        <div class="text-center space-y-3">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">About StudyTracker</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                A study management tool built around spaced repetition — the proven technique
                for long-term retention.
            </p>
        </div>

        <!-- App Tour -->
        <AppTour />

        <!-- Tech & Purpose -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 space-y-4">
            <h3 class="text-xl font-bold text-gray-900">Why This Exists</h3>
            <p class="text-gray-600">
                StudyTracker was built out of a real need to organize self-study routines. Most learners rely on
                willpower to remember when to review — but our memory isn't reliable. This app automates the
                scheduling, so you can focus on actually learning.
            </p>
            <p class="text-gray-600">
                Built with Laravel, Vue 3, and Tailwind CSS. The spaced repetition engine generates
                revision tasks automatically based on configurable intervals.
            </p>
        </div>

        <!-- Developer Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Developer</h3>
            <div class="flex items-start gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 font-bold text-lg flex items-center justify-center flex-shrink-0">
                    N
                </div>
                <div class="space-y-3">
                    <div>
                        <h4 class="font-semibold text-gray-900">Naymur</h4>
                        <p class="text-sm text-gray-600">Software Engineer</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a :href="links.github" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm rounded-lg hover:bg-black transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                            </svg>
                            GitHub
                        </a>
                        <a :href="links.linkedin" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-700 text-white text-sm rounded-lg hover:bg-blue-800 transition-colors">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.454C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0" />
                            </svg>
                            LinkedIn
                        </a>
                        <a :href="links.repo" target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            Source Code
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import AppTour from '@/components/AppTour.vue'

const links = {
    github: 'https://github.com/naymur92',
    linkedin: 'https://linkedin.com/in/naymur',
    repo: 'https://github.com/naymur92/StudyTracker',
}
</script>
