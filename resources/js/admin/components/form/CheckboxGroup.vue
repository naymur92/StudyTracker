<template>
    <label v-if="label" class="form-label mb-3">
        <strong>{{ label }}</strong>
        <span v-if="required" class="text-danger">
            &nbsp;<i class="fas fa-xs fa-asterisk"></i>
        </span>
    </label>

    <div :class="[containerClass, { 'is-invalid': hasError }]">
        <div class="row">
            <div v-for="(option, index) in options" :key="index" :class="colClass + ' mb-2'">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" :name="name + '[]'"
                        :value="option.value || option.name" :id="`${id}_${index}`"
                        :checked="isChecked(option.value || option.name)" :disabled="disabled"
                        @change="handleChange($event, option.value || option.name)">
                    <label class="form-check-label" :for="`${id}_${index}`">
                        {{ option.label || option.name }}
                    </label>
                </div>
            </div>
            <p v-if="options.length === 0" class="text-muted col-12">{{ emptyMessage }}</p>
        </div>
    </div>

    <div v-if="hasError" class="invalid-feedback d-block mt-2">
        <strong>{{ firstError }}</strong>
    </div>
</template>

<script>
export default {
    name: 'CheckboxGroup',
    props: {
        modelValue: {
            type: Array,
            default: () => [],
        },
        options: {
            type: Array,
            required: true,
            // Expected format: [{ value: 'x', label: 'Label' }] or [{ name: 'permission-name' }]
        },
        id: {
            type: String,
            required: true,
        },
        name: {
            type: String,
            default: 'checkboxes',
        },
        label: {
            type: String,
            default: '',
        },
        required: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        error: {
            type: [String, Array],
            default: null,
        },
        emptyMessage: {
            type: String,
            default: 'No options available',
        },
        cols: {
            type: String,
            default: 'col-md-3 col-6',
        },
        containerClass: {
            type: String,
            default: 'p-3 border rounded',
        },
    },
    computed: {
        hasError() {
            return !!(this.error && (Array.isArray(this.error) ? this.error.length : String(this.error).length));
        },
        firstError() {
            if (!this.error) return '';
            return Array.isArray(this.error) ? this.error[0] : this.error;
        },
        colClass() {
            return this.cols;
        },
    },
    methods: {
        isChecked(value) {
            return this.modelValue.includes(value);
        },
        handleChange(event, value) {
            const checked = event.target.checked;
            let newValue = [...this.modelValue];

            if (checked) {
                if (!newValue.includes(value)) {
                    newValue.push(value);
                }
            } else {
                newValue = newValue.filter(v => v !== value);
            }

            this.$emit('update:modelValue', newValue);
        },
    },
};
</script>
