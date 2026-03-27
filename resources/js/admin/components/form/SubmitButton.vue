<template>
    <button :type="type" :class="btnClass" :disabled="disabled || loading" @click="handleClick">
        <i v-if="loading" class="fas fa-spinner fa-spin me-1"></i>
        <i v-else :class="icon + ' me-1'"></i>
        {{ text }}
    </button>
</template>

<script>
export default {
    name: 'SubmitButton',
    props: {
        text: {
            type: String,
            default: 'Save',
        },
        type: {
            type: String,
            default: 'submit',
        },
        icon: {
            type: String,
            default: 'fas fa-save',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        variant: {
            type: String,
            default: 'primary',
            validator: (value) => ['primary', 'success', 'danger', 'warning', 'info', 'secondary'].includes(value),
        },
    },
    computed: {
        btnClass() {
            return `btn btn-${this.variant} waves-effect waves-light br-5`;
        },
    },
    methods: {
        handleClick(event) {
            this.$emit('click', event);
        },
    },
};
</script>
