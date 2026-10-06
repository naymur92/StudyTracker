<template>
    <div class="lg:grid lg:grid-cols-[220px_1fr] lg:gap-8">
        <!-- Table of contents -->
        <aside class="mb-6 lg:mb-0">
            <nav class="lg:sticky lg:top-6 bg-white rounded-lg shadow p-4" aria-label="User Guide contents">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Contents</p>
                <ol class="space-y-1 text-sm">
                    <li v-for="section in guideSections" :key="section.id">
                        <a :href="`#${section.id}`" class="block px-2 py-1 rounded hover:bg-primary-50 hover:text-primary-700 text-gray-700">{{ section.title }}</a>
                    </li>
                    <li><a href="#references" class="block px-2 py-1 rounded hover:bg-primary-50 hover:text-primary-700 text-gray-700">References</a></li>
                </ol>
            </nav>
        </aside>

        <div class="space-y-10 min-w-0">
            <header>
                <h1 class="text-3xl font-bold text-gray-900">User Guide</h1>
                <p class="text-gray-600 mt-1">
                    How to use each menu, and the learning science behind it. Labels show whether a choice is
                    research-backed or a rule of thumb.
                    <router-link to="/features" class="text-primary-700 hover:underline">Public features page</router-link>
                </p>
            </header>

            <section v-for="section in guideSections" :key="section.id" :id="section.id" class="scroll-mt-24 bg-white rounded-lg shadow p-6 md:p-8 space-y-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <h2 class="text-2xl font-bold text-gray-900">{{ section.title }}</h2>
                    <router-link v-if="section.route" :to="section.route" class="text-sm text-primary-700 hover:underline">Open {{ section.title }} →</router-link>
                </div>
                <p class="text-gray-700">{{ section.purpose }}</p>

                <div v-if="section.steps?.length">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">How to use it</h3>
                    <ol class="list-decimal pl-5 space-y-1 text-gray-700">
                        <li v-for="(step, i) in section.steps" :key="i">{{ step }}</li>
                    </ol>
                </div>

                <div v-if="section.tips?.length">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">Tips</h3>
                    <ul class="list-disc pl-5 space-y-1 text-gray-700">
                        <li v-for="(tip, i) in section.tips" :key="i">{{ tip.text }} <Citations v-if="tip.refs" :ids="tip.refs" /></li>
                    </ul>
                </div>

                <details v-if="section.algorithms?.length || section.techniques?.length" class="group" :open="openByDefault(section)">
                    <summary class="cursor-pointer text-sm font-semibold text-primary-700 select-none">
                        The science behind it ({{ (section.algorithms?.length || 0) + (section.techniques?.length || 0) }})
                    </summary>
                    <div class="mt-4 space-y-4">
                        <AlgorithmCard v-for="id in section.algorithms" :key="`a-${id}`" :algorithm="algorithmById(id)" />
                        <TechniqueCard v-for="id in section.techniques" :key="`t-${id}`" :technique="techniqueById(id)" />
                    </div>
                </details>
            </section>

            <section id="references" class="scroll-mt-24 bg-white rounded-lg shadow p-6 md:p-8 space-y-4">
                <h2 class="text-2xl font-bold text-gray-900">References</h2>
                <ReferenceList :ids="guideReferenceIds" />
            </section>
        </div>
    </div>
</template>

<script setup>
import { onMounted, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { guideSections } from '@/content/userGuide'
import { techniqueById, algorithmById, citedIds } from '@/content/learningScience'
import AlgorithmCard from '@/components/science/AlgorithmCard.vue'
import TechniqueCard from '@/components/science/TechniqueCard.vue'
import Citations from '@/components/science/Citations.vue'
import ReferenceList from '@/components/science/ReferenceList.vue'

const route = useRoute()

// The section you deep-linked to opens its science panel.
const openByDefault = (section) => route.hash === `#${section.id}`

const guideReferenceIds = [
    ...new Set([
        ...citedIds([
            ...guideSections.flatMap((s) => (s.techniques || []).map(techniqueById)),
            ...guideSections.flatMap((s) => (s.algorithms || []).map(algorithmById)),
        ]),
        ...guideSections.flatMap((s) => (s.tips || []).flatMap((t) => t.refs || [])),
    ]),
]

onMounted(async () => {
    document.title = 'User Guide — StudyTracker'
    if (route.hash) {
        await nextTick()
        document.querySelector(route.hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }
})
</script>
