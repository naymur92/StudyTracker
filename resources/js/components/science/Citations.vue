<template>
    <span v-if="ids.length" class="text-sm text-gray-600">
        (<template v-for="(id, i) in ids" :key="id"><CitationLink :id="id" /><template v-if="i < ids.length - 1">; </template></template>)
    </span>
</template>

<script setup>
import CitationLink from './CitationLink.vue'

defineProps({ ids: { type: Array, default: () => [] } })
</script>
