<template>
    <VueDatePicker v-model="internalValue" :enable-time-picker="false" :time-config="{ enableTimePicker: false }"
        :formats="{ input: 'yyyy-MM-dd' }" model-type="yyyy-MM-dd" auto-apply no-today hide-input-icon
        :placeholder="placeholder" :required="required" :disabled="disabled" :min-date="minDate" :max-date="maxDate"
        :teleport="true" input-class-name="dp-input" :dark="false"
        :action-row="{ showNow: false, showPreview: false, showSelect: false, showCancel: false }" />
</template>

<script setup>
import { computed } from 'vue'
import { VueDatePicker } from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Select date' },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    minDate: { type: [String, Date], default: null },
    maxDate: { type: [String, Date], default: null },
})

const emit = defineEmits(['update:modelValue', 'change'])

const internalValue = computed({
    get: () => props.modelValue || null,
    set: (val) => {
        const str = val ?? ''
        emit('update:modelValue', str)
        emit('change', str)
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

.dp-input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: #f9fafb;
}

:root {
    --dp-primary-color: #0284c7;
    --dp-primary-text-color: #fff;
    --dp-border-color: #e5e7eb;
    --dp-border-radius: 0.5rem;
    --dp-cell-border-radius: 0.375rem;
    --dp-font-family: 'Inter', system-ui, sans-serif;
    --dp-font-size: 0.875rem;
}
</style>
