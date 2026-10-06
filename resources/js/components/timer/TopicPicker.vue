<template>
    <div class="space-y-1.5">
        <input v-model="search" type="search" class="input-base text-sm" placeholder="Search your topics…" aria-label="Search topics" />
        <select :value="modelValue ?? ''" @change="$emit('update:modelValue', $event.target.value || null)" class="input-base text-sm" :size="Math.min(7, Math.max(3, options.length + 1))" aria-label="Topic">
            <option value="">— No topic —</option>
            <optgroup v-if="matching.length" :label="`${laneLabel} lane`">
                <option v-for="t in matching" :key="t.id" :value="t.id">{{ t.title }}</option>
            </optgroup>
            <optgroup v-if="others.length" :label="matching.length ? 'Other topics' : 'Topics'">
                <option v-for="t in others" :key="t.id" :value="t.id">{{ t.title }}</option>
            </optgroup>
        </select>
        <p v-if="loading" class="text-xs text-gray-500">Loading topics…</p>
        <p v-else-if="!options.length" class="text-xs text-gray-500">No topics found. <router-link to="/app/topics/create" class="underline">Create one</router-link>.</p>
    </div>
</template>

<script setup>
// Picks one of the user's topics, listing those in the block's lane first.
import { computed, onMounted, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
    modelValue: { type: String, default: null },
    lane: { type: String, default: null }, // the block's lane
    current: { type: Object, default: null }, // { id, title } already on the block
})
defineEmits(['update:modelValue'])

const authStore = useAuthStore()
const search = ref('')
const loading = ref(false)
const topics = ref([])

const options = computed(() => {
    const list = topics.value.filter((t) => t.status !== 'archived')
    if (props.current && !list.some((t) => t.id === props.current.id)) list.unshift(props.current)
    return list
})
const matching = computed(() => options.value.filter((t) => props.lane && t.lane === props.lane))
const others = computed(() => options.value.filter((t) => !(props.lane && t.lane === props.lane)))
const laneLabel = computed(() => (props.lane ? props.lane.charAt(0).toUpperCase() + props.lane.slice(1) : ''))

let debounce = null
const load = async () => {
    loading.value = true
    try {
        const response = await authStore.getApiClient().get('/study/topics', { params: { per_page: 100, search: search.value || undefined } })
        const data = response.data.data
        topics.value = Array.isArray(data) ? data : data?.data || []
    } catch {
        topics.value = []
    } finally {
        loading.value = false
    }
}

watch(search, () => {
    clearTimeout(debounce)
    debounce = setTimeout(load, 300)
})
onMounted(load)
</script>
