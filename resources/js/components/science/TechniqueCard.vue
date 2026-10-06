<template>
    <article :id="`technique-${technique.id}`" class="scroll-mt-24 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <h4 class="text-lg font-semibold text-gray-900">{{ technique.name }}</h4>
            <EvidenceBadge :level="technique.evidence" />
        </div>
        <p class="text-gray-700">{{ technique.description }}</p>
        <p class="text-gray-700"><span class="font-medium text-gray-900">Why StudyTracker uses it: </span>{{ technique.why }}</p>
        <div>
            <p class="text-sm font-medium text-gray-900 mb-1">Benefits</p>
            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                <li v-for="(benefit, i) in technique.benefits" :key="i">{{ benefit }}</li>
            </ul>
        </div>
        <p v-if="technique.caveat" class="text-sm bg-amber-50 border border-amber-200 rounded p-3 text-amber-900">
            <span class="font-semibold">Limits: </span>{{ technique.caveat }}
        </p>
        <div v-if="showWhere && technique.where?.length" class="text-sm text-gray-600">
            Where you see it:
            <template v-for="(w, i) in technique.where" :key="w.label">
                <router-link :to="w.to" class="text-primary-700 hover:underline">{{ w.label }}</router-link><template v-if="i < technique.where.length - 1">, </template>
            </template>
        </div>
        <p class="text-sm text-gray-600">Research: <Citations :ids="technique.refs" /></p>
    </article>
</template>

<script setup>
import EvidenceBadge from './EvidenceBadge.vue'
import Citations from './Citations.vue'

defineProps({
    technique: { type: Object, required: true },
    showWhere: { type: Boolean, default: true },
})
</script>
