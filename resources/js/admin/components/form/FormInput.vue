<template>
    <label v-if="label" :for="id">
        <strong>{{ label }}</strong>
        <span v-if="required" class="text-danger">&nbsp;<i class="fas fa-xs fa-asterisk"></i></span>
    </label>
    <input :id="id" :name="name || id" :type="type" class="form-control" :class="{ 'is-invalid': hasError }"
        :placeholder="placeholder" :disabled="disabled" :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)" />
    <div v-if="hasError" class="invalid-feedback d-block"><strong>{{ firstError }}</strong></div>
</template>

<script>
export default {
    name: 'FormInput',
    props: {
        modelValue: {
            type: [String, Number],
            default: '',
        },
        id: {
            type: String,
            required: true,
        },
        name: {
            type: String,
            default: null,
        },
        label: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        type: {
            type: String,
            default: 'text',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        required: {
            type: Boolean,
            default: false,
        },
        error: {
            // Accepts string or array of strings
            type: [String, Array],
            default: null,
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
    },
};
</script>
