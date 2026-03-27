<template>
    <div>
        <label v-if="label" :for="id" class="form-label d-block">
            <strong>{{ label }}</strong>
            <span v-if="required" class="text-danger">&nbsp;<i class="fas fa-xs fa-asterisk"></i></span>
        </label>
        <select ref="selectElement" :id="id" :name="name || id" :multiple="multiple" :disabled="disabled"
            class="form-control" :class="{ 'is-invalid': hasError }">
            <option value="" v-if="!multiple">{{ placeholder }}</option>
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <div v-if="hasError" class="invalid-feedback d-block"><strong>{{ firstError }}</strong></div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: [String, Number, Array],
        default: ''
    },
    options: {
        type: Array,
        default: () => []
    },
    placeholder: {
        type: String,
        default: 'Select an option'
    },
    label: {
        type: String,
        default: ''
    },
    id: {
        type: String,
        default: () => `select2-${Math.random().toString(36).substr(2, 9)}`
    },
    name: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    disabled: {
        type: Boolean,
        default: false
    },
    multiple: {
        type: Boolean,
        default: false
    },
    errors: {
        type: [Array, Object, String],
        default: () => []
    },
    settings: {
        type: Object,
        default: () => ({})
    }
});

const hasError = computed(() => {
    if (Array.isArray(props.errors)) {
        return props.errors.length > 0;
    }
    if (typeof props.errors === 'object' && props.errors !== null) {
        return Object.keys(props.errors).length > 0;
    }
    return !!props.errors;
});

const firstError = computed(() => {
    if (Array.isArray(props.errors)) {
        return props.errors[0] || '';
    }
    if (typeof props.errors === 'object' && props.errors !== null) {
        const keys = Object.keys(props.errors);
        return keys.length > 0 ? props.errors[keys[0]][0] || props.errors[keys[0]] : '';
    }
    return props.errors || '';
});

const emit = defineEmits(['update:modelValue']);

const selectElement = ref(null);

const initSelect2 = () => {
    // Use global jQuery from window
    const $ = window.$;
    if (!$ || !$.fn.select2) {
        console.error('jQuery or Select2 not loaded');
        return;
    }

    const $select = $(selectElement.value);

    // Initialize Select2
    $select.select2({
        placeholder: props.placeholder,
        allowClear: true,
        width: '100%',
        ...props.settings
    });

    // Set initial value
    if (props.modelValue) {
        $select.val(props.modelValue).trigger('change.select2');
    }

    // Listen for changes
    $select.on('change', (e) => {
        emit('update:modelValue', e.target.value);
    });
};

const updateValue = (newValue) => {
    if (selectElement.value && window.$) {
        const $select = window.$(selectElement.value);
        $select.val(newValue).trigger('change.select2');
    }
};

watch(() => props.modelValue, (newValue) => {
    nextTick(() => {
        updateValue(newValue);
    });
});

watch(() => props.options, () => {
    nextTick(() => {
        if (selectElement.value && window.$) {
            const $select = window.$(selectElement.value);
            $select.trigger('change.select2');
            if (props.modelValue) {
                updateValue(props.modelValue);
            }
        }
    });
}, { deep: true });

onMounted(() => {
    nextTick(() => {
        initSelect2();
    });
});
</script>

<style>
/* Select2 adjustments for better integration */
.select2-container {
    width: 100% !important;
}
</style>
