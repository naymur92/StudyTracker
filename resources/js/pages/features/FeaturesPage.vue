<template>
    <div class="space-y-16 pb-8">
        <header class="text-center space-y-4">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-900">Features and the science behind them</h1>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                StudyTracker is built on well-studied learning techniques: testing yourself, spacing reviews, and
                relearning what you forget. Here is what it does, how it decides, and the research behind each choice.
            </p>
            <nav class="flex flex-wrap justify-center gap-2 pt-2" aria-label="Sections">
                <a v-for="s in sections" :key="s.id" :href="`#${s.id}`"
                    class="px-4 py-2 rounded-full bg-white border border-gray-200 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-700">
                    {{ s.label }}
                </a>
            </nav>
        </header>

        <section id="features" class="scroll-mt-24 space-y-6">
            <h2 class="text-2xl font-bold text-gray-900 text-center">Features</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <article v-for="feature in features" :key="feature.id" class="p-6 bg-white rounded-xl shadow-sm border border-gray-100 space-y-3">
                    <h3 class="font-semibold text-gray-900">{{ feature.title }}</h3>
                    <p class="text-sm text-gray-600">{{ feature.desc }}</p>
                    <p class="text-xs text-gray-500">
                        Applies:
                        <template v-for="(id, i) in feature.techniques" :key="id">
                            <a :href="`#technique-${id}`" class="text-primary-700 hover:underline">{{ techniqueName(id) }}</a><template v-if="i < feature.techniques.length - 1">, </template>
                        </template>
                    </p>
                </article>
            </div>
        </section>

        <section id="techniques" class="scroll-mt-24 space-y-6">
            <div class="text-center space-y-2">
                <h2 class="text-2xl font-bold text-gray-900">Techniques</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Each technique is labelled <strong>Research-backed</strong> when its mechanism has direct experimental
                    or meta-analytic support, or <strong>Rule of thumb</strong> when it is a practical heuristic that
                    research motivates.
                </p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <TechniqueCard v-for="t in techniques" :key="t.id" :technique="t" :show-where="false" />
            </div>
        </section>

        <section id="how-it-decides" class="scroll-mt-24 space-y-6">
            <div class="text-center space-y-2">
                <h2 class="text-2xl font-bold text-gray-900">How StudyTracker decides</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">The rules the app follows, with worked examples.</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <AlgorithmCard v-for="a in algorithms" :key="a.id" :algorithm="a" />
            </div>
        </section>

        <section id="references" class="scroll-mt-24 bg-white rounded-xl shadow-sm border border-gray-100 p-8 space-y-4">
            <h2 class="text-2xl font-bold text-gray-900">References</h2>
            <p class="text-sm text-gray-600">
                Each reference was checked against its publication record. Most come from the two learning-science
                guides this system is based on; a few were added to support specific features.
            </p>
            <ReferenceList :ids="citedReferenceIds" />
        </section>

        <div class="text-center space-y-4">
            <router-link to="/auth/register" class="inline-block px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium">
                Start learning smarter
            </router-link>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { techniques, algorithms, citedIds, techniqueById } from '@/content/learningScience'
import { features } from '@/content/features'
import TechniqueCard from '@/components/science/TechniqueCard.vue'
import AlgorithmCard from '@/components/science/AlgorithmCard.vue'
import ReferenceList from '@/components/science/ReferenceList.vue'

const sections = [
    { id: 'features', label: 'Features' },
    { id: 'techniques', label: 'Techniques' },
    { id: 'how-it-decides', label: 'How StudyTracker decides' },
    { id: 'references', label: 'References' },
]

const citedReferenceIds = citedIds([...techniques, ...algorithms])
const techniqueName = (id) => techniqueById(id)?.name ?? id

onMounted(() => {
    document.title = 'Features & the science — StudyTracker'
})
</script>
