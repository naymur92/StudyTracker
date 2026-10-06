<template>
    <div v-if="block" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$emit('close')">
        <form class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4" @submit.prevent="submit">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Start {{ slotLabels[block.slot] || 'block' }}</h2>
                <p class="text-sm text-gray-600">{{ block.planned_task || 'Pick what you will learn, then the timer starts.' }}</p>
            </div>

            <div v-if="needsTopic">
                <label class="block text-sm font-medium text-gray-700 mb-1">What are you learning?</label>
                <TopicPicker v-model="topicId" :lane="block.lane" :current="block.topic" />
            </div>

            <div v-if="needsMinutes">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="start-minutes">Minutes</label>
                <input id="start-minutes" v-model.number="minutes" type="number" min="5" max="480" class="input-base" required />
            </div>

            <p v-if="error" class="text-sm text-red-600">{{ error }}</p>

            <div class="flex gap-3">
                <button type="submit" class="btn-primary flex-1" :disabled="saving || (needsTopic && !topicId)">Start timer</button>
                <button type="button" class="btn-secondary flex-1" @click="$emit('close')">Cancel</button>
            </div>
        </form>
    </div>
</template>

<script setup>
// Asks for the topic (non-review blocks) and minutes a block needs before
// its timer starts.
import { computed, ref, watch } from 'vue'
import { slotLabels } from '@/components/weekly/weeklyMeta'
import TopicPicker from './TopicPicker.vue'
import { useTimerActions } from './useTimerActions'

const props = defineProps({
    block: { type: Object, default: null },
})
const emit = defineEmits(['close', 'started'])

const { start } = useTimerActions()
const topicId = ref(null)
const minutes = ref(null)
const saving = ref(false)
const error = ref('')

const needsTopic = computed(() => props.block && props.block.lane !== 'review' && !props.block.topic)
const needsMinutes = computed(() => props.block && !props.block.planned_minutes)

watch(() => props.block, (block) => {
    topicId.value = block?.topic?.id ?? null
    minutes.value = block?.planned_minutes || 30
    error.value = ''
})

const submit = async () => {
    saving.value = true
    error.value = ''
    try {
        await start(props.block, {
            ...(needsTopic.value ? { topic_id: topicId.value } : {}),
            ...(needsMinutes.value ? { planned_minutes: minutes.value } : {}),
        })
        emit('started')
        emit('close')
    } catch (err) {
        const errors = err.response?.data?.errors
        error.value = (errors && Object.values(errors).flat()[0]) || err.response?.data?.msg || err.response?.data?.message || 'The timer could not start.'
    } finally {
        saving.value = false
    }
}
</script>
