<template>
    <div class="p-4 bg-success-50 border border-success-200 rounded-lg flex items-start gap-3">
        <svg class="w-5 h-5 text-success-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd" />
        </svg>
        <div class="flex-1">
            <h3 class="font-semibold text-success-900">{{ title }}</h3>
            <p class="text-success-700 text-sm mt-1">{{ message }}</p>
        </div>
        <button v-if="dismissible" @click="$emit('dismiss')" class="text-success-600 hover:text-success-800">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: 'Success',
    },
    message: {
        type: String,
        required: true,
    },
    dismissible: {
        type: Boolean,
        default: true,
    },
})

defineEmits(['dismiss'])
</script>
