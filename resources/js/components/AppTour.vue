<template>
    <section class="space-y-16">
        <!-- Hero -->
        <div class="text-center space-y-4">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
                Master Any Subject with <span class="text-primary-600">Spaced Repetition</span>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                StudyTracker helps you learn smarter by automatically scheduling revision sessions
                at scientifically-proven intervals — so you remember more and study less.
            </p>
        </div>

        <!-- How it works -->
        <div>
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">How It Works</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div v-for="step in howItWorks" :key="step.number"
                    class="text-center p-6 bg-white rounded-xl shadow-sm border border-gray-100">
                    <div
                        class="w-10 h-10 rounded-full bg-primary-100 text-primary-700 font-bold text-lg flex items-center justify-center mx-auto mb-4">
                        {{ step.number }}
                    </div>
                    <h4 class="font-semibold text-gray-900 mb-2">{{ step.title }}</h4>
                    <p class="text-sm text-gray-600">{{ step.desc }}</p>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div>
            <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">Key Features</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div v-for="feature in features" :key="feature.title"
                    class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 space-y-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="feature.bgColor">
                        <svg class="w-5 h-5" :class="feature.iconColor" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path :d="feature.icon" />
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900">{{ feature.title }}</h4>
                    <p class="text-sm text-gray-600">{{ feature.desc }}</p>
                </div>
            </div>
        </div>

        <!-- Spaced Repetition explainer -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">The Science Behind It</h3>
            <p class="text-gray-600 text-center max-w-2xl mx-auto mb-8">
                Spaced repetition is a learning technique where revision intervals gradually increase
                over time. Instead of cramming, you review at optimal moments — right before you'd forget.
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <div v-for="interval in revisionIntervals" :key="interval.label"
                    class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium" :class="interval.class">
                    <span>{{ interval.label }}</span>
                </div>
            </div>
            <p class="text-xs text-gray-500 text-center mt-4">Default revision schedule (fully customizable)</p>
        </div>
    </section>
</template>

<script setup>
const howItWorks = [
    {
        number: 1,
        title: 'Add a Topic',
        desc: 'Create a topic you want to learn, assign it to a category and set a study date.',
    },
    {
        number: 2,
        title: 'Learn It',
        desc: 'Study the topic on the scheduled date. Mark it complete when done.',
    },
    {
        number: 3,
        title: 'Auto Revisions',
        desc: 'Revision tasks are generated automatically at increasing intervals (1, 7, 30, 90 days).',
    },
    {
        number: 4,
        title: 'Track Progress',
        desc: 'Monitor your dashboard, calendar, and practice logs to see your growth over time.',
    },
]

const features = [
    {
        title: 'Smart Daily Agenda',
        desc: 'See exactly what to study today — new topics, revisions, and overdue items in one view.',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        bgColor: 'bg-primary-100',
        iconColor: 'text-primary-600',
    },
    {
        title: 'Calendar View',
        desc: 'Visualize your study schedule on a monthly calendar with task counts and completion status.',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        bgColor: 'bg-blue-100',
        iconColor: 'text-blue-600',
    },
    {
        title: 'Categories & Organization',
        desc: 'Group your topics into color-coded categories for easy navigation and filtering.',
        icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
        bgColor: 'bg-amber-100',
        iconColor: 'text-amber-600',
    },
    {
        title: 'Practice Logging',
        desc: 'Record practice sessions with type, duration, and outcome to measure real effort.',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        bgColor: 'bg-green-100',
        iconColor: 'text-green-600',
    },
    {
        title: 'Custom Revision Schedule',
        desc: 'Adjust the spaced repetition intervals to match your learning style and speed.',
        icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        bgColor: 'bg-purple-100',
        iconColor: 'text-purple-600',
    },
    {
        title: 'Study Reports',
        desc: 'Download or email PDF reports summarizing your study progress and achievements.',
        icon: 'M7 20h10a2 2 0 002-2V6.828a2 2 0 00-.586-1.414l-2.828-2.828A2 2 0 0014.172 2H7a2 2 0 00-2 2v14a2 2 0 002 2zM9 13h6M9 17h6M9 9h2',
        bgColor: 'bg-rose-100',
        iconColor: 'text-rose-600',
    },
]

const revisionIntervals = [
    { label: 'Day 0: Learn', class: 'bg-primary-100 text-primary-700' },
    { label: 'Day 1: First Revision', class: 'bg-blue-100 text-blue-700' },
    { label: 'Day 7: Second Revision', class: 'bg-green-100 text-green-700' },
    { label: 'Day 30: Third Revision', class: 'bg-amber-100 text-amber-700' },
    { label: 'Day 90: Final Revision', class: 'bg-purple-100 text-purple-700' },
]
</script>
