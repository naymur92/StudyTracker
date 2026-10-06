<template>
    <a v-if="reference" :href="`#ref-${id}`"
        class="text-primary-700 hover:underline focus:outline-none focus:ring-2 focus:ring-primary-300 rounded"
        :title="reference.apa">{{ reference.short }}</a>
    <span v-else class="text-red-600">[unknown reference {{ id }}]</span>
</template>

<script setup>
import { computed } from 'vue'
import { references } from '@/content/learningScience'

const props = defineProps({ id: { type: String, required: true } })
const reference = computed(() => references[props.id])
</script>
