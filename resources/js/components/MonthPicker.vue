<template>
    <VueDatePicker v-model="internalValue" month-picker :teleport="true" hide-input-icon auto-apply
        input-class-name="dp-input" :dark="false" :max-date="maxDate || new Date()" :min-date="minDate || null"
        :placeholder="placeholder" :formats="{ input: 'yyyy-MM' }" />
</template>

<script setup>
import { computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Select month' },
    minDate: { type: [String, Date], default: null },
    maxDate: { type: [String, Date], default: null },
})

const emit = defineEmits(['update:modelValue'])

// VueDatePicker month-picker uses { month: 0-11, year: YYYY } internally
const internalValue = computed({
    get: () => {
        if (!props.modelValue) return null
        const [year, month] = props.modelValue.split('-').map(Number)
        return { month: month - 1, year }
    },
    set: (val) => {
        if (!val) {
            emit('update:modelValue', '')
            return
        }
        const month = String(val.month + 1).padStart(2, '0')
        emit('update:modelValue', `${val.year}-${month}`)
    },
})
</script>

<style>
.dp-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #111827;
    background-color: #fff;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.dp-input:focus {
    outline: none;
    border-color: transparent;
    box-shadow: 0 0 0 2px #0ea5e9;
}

.dp-input::placeholder {
    color: #9ca3af;
}
</style>
