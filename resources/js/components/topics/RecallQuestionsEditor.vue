<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium text-gray-700">Recall questions</label>
            <span class="text-xs text-gray-500">{{ modelValue.length }}/{{ max }} · 3–5 work best</span>
        </div>
        <p class="text-xs text-gray-500">
            Write questions you will answer from memory during reviews. Answers are optional and stay hidden until you
            reveal them.
        </p>

        <div v-for="(item, idx) in modelValue" :key="idx" class="border border-gray-200 rounded-lg p-3 space-y-2">
            <div class="flex items-start gap-2">
                <span class="text-sm font-semibold text-gray-500 mt-2 w-5">{{ idx + 1 }}.</span>
                <div class="flex-1 space-y-2">
                    <input :value="item.question" @input="update(idx, 'question', $event.target.value)" type="text"
                        maxlength="500" class="input-base" placeholder="Question, e.g. What does groupby return?" />
                    <textarea :value="item.answer || ''" @input="update(idx, 'answer', $event.target.value)"
                        maxlength="2000" rows="2" class="input-base" placeholder="Answer (optional)"></textarea>
                    <p v-if="errorFor(idx)" class="text-xs text-red-600">{{ errorFor(idx) }}</p>
                </div>
                <div class="flex flex-col gap-1">
                    <button type="button" @click="move(idx, -1)" :disabled="idx === 0" title="Move up"
                        class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-40">↑</button>
                    <button type="button" @click="move(idx, 1)" :disabled="idx === modelValue.length - 1"
                        title="Move down"
                        class="px-2 py-1 text-xs rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-40">↓</button>
                    <button type="button" @click="remove(idx)" title="Remove"
                        class="px-2 py-1 text-xs rounded bg-red-50 text-red-700 hover:bg-red-100">✕</button>
                </div>
            </div>
        </div>

        <button type="button" @click="add" :disabled="modelValue.length >= max"
            class="px-3 py-1.5 text-sm rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 disabled:opacity-40">
            + Add question
        </button>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    max: { type: Number, default: 10 },
})
const emit = defineEmits(['update:modelValue'])

const clone = () => props.modelValue.map((item) => ({ question: item.question || '', answer: item.answer || '' }))

const update = (idx, key, value) => {
    const next = clone()
    next[idx][key] = value
    emit('update:modelValue', next)
}

const add = () => emit('update:modelValue', [...clone(), { question: '', answer: '' }])

const remove = (idx) => emit('update:modelValue', clone().filter((_, i) => i !== idx))

const move = (idx, dir) => {
    const next = clone()
    const target = idx + dir
    ;[next[idx], next[target]] = [next[target], next[idx]]
    emit('update:modelValue', next)
}

const errorFor = (idx) => {
    const list = props.errors[`recall_questions.${idx}.question`] || props.errors[`recall_questions.${idx}.answer`]
    return list ? list[0] : null
}
</script>
