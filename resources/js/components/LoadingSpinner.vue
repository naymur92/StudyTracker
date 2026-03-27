<template>
    <div class="flex items-center justify-center py-12">
        <div class="text-center">
            <div class="inline-block">
                <svg class="w-12 h-12 text-primary-600 animate-spin" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" />
                </svg>
            </div>
            <p v-if="message" class="mt-4 text-gray-600">{{ message }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    message: {
        type: String,
        default: 'Loading...',
    },
})
</script>
