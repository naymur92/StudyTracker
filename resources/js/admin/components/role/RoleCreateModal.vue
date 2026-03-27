<template>
    <div>
        <!-- Create Role Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" :class="{ show: showModal }"
            :style="{ display: showModal ? 'block' : 'none' }" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 40vw">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Create New Role</h5>
                        <button type="button" class="close" @click="closeModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="submitRole">
                        <div class="modal-body">
                            <div class="form-group">
                                <form-input v-model="form.name" id="_name" name="name" label="Role Name"
                                    placeholder="Super Admin, Admin, User, etc." :disabled="loading" :required="true"
                                    :error="errors.name" />
                            </div>

                            <div class="form-group">
                                <checkbox-group v-model="form.permissions" :options="availablePermissions"
                                    id="permissions" name="permissions" label="Select Permissions" :required="true"
                                    :error="errors.permissions" empty-message="No permissions available"
                                    cols="col-md-3 col-6" />

                                <small class="text-muted d-block mt-2">Select at least one permission.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" @click="closeModal">Close</button> -->
                            <reset-button variant="danger" text="Reset Form" @click="resetForm" />
                            <submit-button variant="success" text="Add Role" :loading="loading" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Backdrop -->
        <div v-if="showModal" class="modal-backdrop fade show"></div>
    </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
    name: 'RoleCreateModal',
    props: {
        permissions: {
            type: Array,
            default: () => [],
        },
        createUrl: {
            type: String,
            default: '/admin/roles',
        },
    },
    emits: ['role-created'],
    setup(props, { emit }) {
        const showModal = ref(false);
        const loading = ref(false);
        const errors = ref({});
        const form = ref({
            name: '',
            permissions: [],
        });

        // Filter permissions with id > 8
        const availablePermissions = computed(() => {
            return props.permissions.filter(p => p.id > 8);
        });

        const openModal = () => {
            window.setSpinner();
            resetForm();
            showModal.value = true;
            // Simulate modal loading, then hide spinner
            setTimeout(() => {
                window.unsetSpinner();
            }, 300);
        };

        const closeModal = () => {
            showModal.value = false;
        };

        const resetForm = () => {
            form.value = { name: '', permissions: [] };
            errors.value = {};
        };

        const submitRole = async () => {
            loading.value = true;
            errors.value = {};
            window.setSpinner();

            try {
                const response = await fetch(props.createUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(form.value),
                });

                const data = await response.json();

                if (data.success) {
                    window.unsetSpinner();
                    closeModal();
                    resetForm();
                    // Reload page to show new role
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else if (data.errors) {
                    window.unsetSpinner();
                    errors.value = data.errors;
                    loading.value = false;
                }
            } catch (error) {
                console.error('Error:', error);
                window.unsetSpinner();
                errors.value = { general: ['An error occurred. Please try again.'] };
                loading.value = false;
            } finally {
                loading.value = false;
            }
        };

        // Expose methods to window for Blade event handlers
        window.openCreateRoleModal = openModal;
        window.closeRoleModal = closeModal;

        return {
            showModal,
            loading,
            errors,
            form,
            availablePermissions,
            openModal,
            closeModal,
            resetForm,
            submitRole,
        };
    },
};
</script>

<style scoped>
.permissions {
    padding: 10px;
    border-radius: 5px;
}

.permissions label {
    display: block;
    margin-bottom: 8px;
}

.modal.show {
    display: block !important;
}

.invalid-feedback {
    color: #dc3545;
}
</style>
