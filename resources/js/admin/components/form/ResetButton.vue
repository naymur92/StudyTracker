<template>
    <button :type="type" :class="btnClass" :disabled="disabled" @click="handleClick">
        <i :class="icon + ' me-1'"></i>
        {{ text }}
    </button>
</template>

<script>
export default {
    name: 'ResetButton',
    props: {
        text: {
            type: String,
            default: 'Reset',
        },
        type: {
            type: String,
            default: 'button',
        },
        icon: {
            type: String,
            default: 'fas fa-undo',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        variant: {
            type: String,
            default: 'secondary',
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
