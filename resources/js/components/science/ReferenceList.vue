<template>
    <ol class="space-y-3 text-sm text-gray-700">
        <li v-for="ref in list" :key="ref.id" :id="`ref-${ref.id}`" class="scroll-mt-24 pl-6 -indent-6">
            {{ ref.apa }}
            <a v-if="ref.doi" :href="`https://doi.org/${ref.doi}`" target="_blank" rel="noopener noreferrer"
                class="text-primary-700 hover:underline break-all">https://doi.org/{{ ref.doi }}</a>
        </li>
    </ol>
</template>

<script setup>
import { computed } from 'vue'
import { sortedReferences } from '@/content/learningScience'

const props = defineProps({
    // Reference ids to list; all references when omitted.
    ids: { type: Array, default: null },
})

const list = computed(() => sortedReferences(props.ids ?? undefined))
</script>
