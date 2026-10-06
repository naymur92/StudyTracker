<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="$emit('close')">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-lg w-full space-y-4 max-h-[90vh] overflow-y-auto"
            role="dialog" aria-modal="true" aria-labelledby="data-deletion-confirm-title">
            <div>
                <h2 id="data-deletion-confirm-title" class="text-xl font-bold text-gray-900">Delete this data?</h2>
                <p class="text-sm text-gray-600">
                    This sends a request to an admin. Nothing is deleted until an admin approves it. Your data is archived
                    first, and then permanently removed from your account.
                </p>
            </div>

            <ul class="space-y-3">
                <li v-for="category in categories" :key="category.key" class="border border-red-100 bg-red-50 rounded-lg p-3">
                    <p class="font-medium text-gray-900">
                        {{ category.label }}
                        <span class="text-sm font-normal text-gray-600">— {{ category.total }} {{ category.total === 1 ? 'item' : 'items' }}</span>
                    </p>
                    <p v-if="category.records.length" class="text-xs text-gray-600 mt-1">
                        {{ category.records.map((r) => `${r.count} ${r.label.toLowerCase()}`).join(', ') }}
                    </p>
                    <ul class="mt-2 list-disc pl-5 text-xs text-gray-700 space-y-0.5">
                        <li v-for="note in category.notes" :key="note">{{ note }}</li>
                    </ul>
                </li>
            </ul>

            <p class="text-sm text-gray-700">Your account, login and other data stay as they are.</p>

            <div>
                <label for="data-deletion-confirm-input" class="block text-sm font-medium text-gray-700 mb-1">
                    Type <span class="font-mono font-bold">DELETE</span> to confirm
                </label>
                <input id="data-deletion-confirm-input" v-model="typed" type="text" class="input-base" autocomplete="off"
                    autocapitalize="characters" spellcheck="false" />
                <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="button"
                    class="flex-1 px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="!confirmed || submitting" @click="$emit('confirm', typed)">
                    {{ submitting ? 'Sending…' : 'Send deletion request' }}
                </button>
                <button type="button" class="btn-secondary" :disabled="submitting" @click="$emit('close')">Cancel</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'

defineProps({
    categories: { type: Array, required: true },
    submitting: { type: Boolean, default: false },
    error: { type: String, default: '' },
})

defineEmits(['close', 'confirm'])

const typed = ref('')
const confirmed = computed(() => typed.value === 'DELETE')
</script>
