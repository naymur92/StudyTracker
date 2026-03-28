<template>
    <div class="relative" ref="containerRef">
        <!-- Trigger button -->
        <button type="button" @click="toggle" class="input-base text-left flex items-center justify-between w-full">
            <span :class="modelValue ? 'text-gray-900' : 'text-gray-400'">
                {{ displayValue }}
            </span>
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </button>

        <!-- Dropdown -->
        <div v-if="isOpen" class="absolute z-20 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg p-4 w-64">
            <!-- Year navigation -->
            <div class="flex items-center justify-between mb-3">
                <button type="button" @click="prevYear"
                    class="p-1 rounded hover:bg-gray-100 text-gray-600 transition-colors" aria-label="Previous year">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <span class="font-semibold text-gray-900 text-sm">{{ viewYear }}</span>
                <button type="button" @click="nextYear"
                    class="p-1 rounded hover:bg-gray-100 text-gray-600 transition-colors"
                    :disabled="viewYear >= maxYear" :class="viewYear >= maxYear ? 'opacity-30 cursor-not-allowed' : ''"
                    aria-label="Next year">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Month grid -->
            <div class="grid grid-cols-3 gap-1">
                <button v-for="(name, index) in MONTH_NAMES" :key="index" type="button" @click="selectMonth(index + 1)"
                    :class="[
            'py-2 px-1 text-sm rounded-md transition-colors text-center',
            isSelected(index + 1)
                ? 'bg-primary-600 text-white font-semibold'
                : 'text-gray-700 hover:bg-primary-50 hover:text-primary-700',
            isFuture(index + 1) ? 'opacity-30 cursor-not-allowed' : 'cursor-pointer'
        ]" :disabled="isFuture(index + 1)">
                    {{ name }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { format } from 'date-fns'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Select month',
    },
})

const emit = defineEmits(['update:modelValue'])

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

const today = new Date()
const maxYear = today.getFullYear()

const isOpen = ref(false)
const containerRef = ref(null)

// Initialize view year from modelValue or current year
const viewYear = ref(
    props.modelValue ? parseInt(props.modelValue.split('-')[0]) : today.getFullYear()
)

const displayValue = computed(() => {
    if (!props.modelValue) return props.placeholder
    const [year, month] = props.modelValue.split('-').map(Number)
    return `${MONTH_NAMES[month - 1]} ${year}`
})

const toggle = () => {
    isOpen.value = !isOpen.value
    if (isOpen.value && props.modelValue) {
        viewYear.value = parseInt(props.modelValue.split('-')[0])
    }
}

const prevYear = () => {
    viewYear.value--
}

const nextYear = () => {
    if (viewYear.value < maxYear) viewYear.value++
}

const isFuture = (month) => {
    if (viewYear.value > maxYear) return true
    if (viewYear.value === maxYear && month > today.getMonth() + 1) return true
    return false
}

const isSelected = (month) => {
    if (!props.modelValue) return false
    const [y, m] = props.modelValue.split('-').map(Number)
    return y === viewYear.value && m === month
}

const selectMonth = (month) => {
    if (isFuture(month)) return
    const value = `${viewYear.value}-${String(month).padStart(2, '0')}`
    emit('update:modelValue', value)
    isOpen.value = false
}

// Close on outside click
const handleClickOutside = (e) => {
    if (containerRef.value && !containerRef.value.contains(e.target)) {
        isOpen.value = false
    }
}

onMounted(() => document.addEventListener('mousedown', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('mousedown', handleClickOutside))
</script>
