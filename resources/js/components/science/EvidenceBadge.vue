<template>
    <span :class="['inline-block px-2 py-0.5 rounded-full text-xs font-semibold whitespace-nowrap', level === 'research' ? 'bg-success-100 text-success-800' : 'bg-amber-100 text-amber-800']"
        :title="level === 'research' ? 'The mechanism has direct experimental or meta-analytic support.' : 'A practical heuristic motivated by research, not tested directly.'">
        {{ EVIDENCE[level] }}
    </span>
</template>

<script setup>
import { EVIDENCE } from '@/content/learningScience'

defineProps({ level: { type: String, required: true } })
</script>
