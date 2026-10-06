<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$emit('cancel')">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full space-y-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">How well did you recall it?</h2>
                <p class="text-sm text-gray-600 mt-1">{{ title }}</p>
                <p class="text-xs text-gray-500 mt-2">
                    Grade honestly — your answer decides when this topic comes back.
                    <router-link to="/app/guide#review" class="text-primary-600 hover:underline">What do the grades mean?</router-link>
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <button v-for="g in grades" :key="g.value" type="button" :disabled="busy" @click="$emit('select', g.value)"
                    :class="['rounded-lg border px-3 py-3 text-left transition-colors disabled:opacity-50', g.class]">
                    <span class="block font-semibold">{{ g.label }}</span>
                    <span class="block text-xs opacity-80">{{ g.hint }}</span>
                </button>
            </div>
            <button type="button" @click="$emit('cancel')" class="btn-secondary w-full">Cancel</button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
    busy: { type: Boolean, default: false },
})
defineEmits(['select', 'cancel'])

const grades = [
    { value: 'again', label: 'Again', hint: 'Forgot it — restart, back tomorrow', class: 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100' },
    { value: 'hard', label: 'Hard', hint: 'Shaky — check again tomorrow', class: 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' },
    { value: 'good', label: 'Good', hint: 'Recalled it — normal next interval', class: 'border-success-200 bg-success-50 text-success-800 hover:bg-success-100' },
    { value: 'easy', label: 'Easy', hint: 'Effortless — skip one step', class: 'border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100' },
]
</script>
