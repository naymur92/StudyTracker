<template>
    <article :id="`algorithm-${algorithm.id}`" class="scroll-mt-24 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <h4 class="text-lg font-semibold text-gray-900">{{ algorithm.name }}</h4>
            <EvidenceBadge :level="algorithm.evidence" />
        </div>
        <p class="text-gray-700"><span class="font-medium text-gray-900">Decides: </span>{{ algorithm.decides }}</p>
        <div>
            <p class="text-sm font-medium text-gray-900 mb-1">The rule</p>
            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                <li v-for="(line, i) in algorithm.rule" :key="i">{{ line }}</li>
            </ul>
        </div>
        <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-800">
            <span class="font-semibold">Example: </span>{{ algorithm.example }}
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900 mb-1">Benefits</p>
            <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                <li v-for="(benefit, i) in algorithm.benefits" :key="i">{{ benefit }}</li>
            </ul>
        </div>
        <p class="text-sm text-gray-600">Research: <Citations :ids="algorithm.refs" /></p>
    </article>
</template>

<script setup>
import EvidenceBadge from './EvidenceBadge.vue'
import Citations from './Citations.vue'

defineProps({ algorithm: { type: Object, required: true } })
</script>
