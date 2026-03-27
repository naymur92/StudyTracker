<template>
    <div>
        <!-- Edit Role Modal -->
        <div class="modal fade" tabindex="-1" :class="{ show: showModal }"
            :style="{ display: showModal ? 'block' : 'none' }" aria-labelledby="editRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 40vw">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoleModalLabel">Edit Role</h5>
                        <button type="button" class="close" @click="closeModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="submitRole">
                        <div class="modal-body">
                            <!-- Loading indicator -->
                            <div v-if="loading" class="text-center mb-3">
                                <span class="spinner-border spinner-border-sm mr-2"></span>
                                Loading role data...
                            </div>
                            <!-- Role Name Input -->
                            <div class="form-group">
                                <form-input v-model="form.name" id="editRoleName" name="name" label="Role Name"
                                    placeholder="Admin, Editor, Viewer" :disabled="loading" :required="true"
                                    :error="errors.name" />
                            </div>

                            <!-- Permissions Checkboxes -->
                            <div class="form-group">
                                <checkbox-group v-model="form.permissions" :options="availablePermissions"
                                    id="editPermissions" name="permissions" label="Select Permissions" :required="true"
                                    :error="errors.permissions" :disabled="loading" cols="col-md-3 col-6" />
                            </div>

                            <!-- General Error Messages -->
                            <ul v-if="otherErrors.length > 0" class="text-danger list-unstyled">
                                <li v-for="(msg, index) in otherErrors" :key="index">
                                    {{ msg }}
                                </li>
                            </ul>
                        </div>

                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" @click="closeModal"
                                :disabled="loading">Close</button> -->
                            <reset-button variant="danger" text="Reset" @click="resetForm" :disabled="loading" />
                            <submit-button variant="success" text="Update Role" :loading="loading" />
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
    name: 'RoleEditModal',
    props: {
        permissions: {
            type: Array,
            default: () => [],
        },
        updateUrl: {
            type: String,
            default: '/admin/roles/0',
        },
    },
    emits: ['role-updated'],
    setup(props, { emit }) {
        const showModal = ref(false);
        const loading = ref(false);
        const errors = ref({});
        const form = ref({
            name: '',
            permissions: [],
        });
        const originalData = ref({
            name: '',
            permissions: [],
        });
        const currentRoleId = ref(null);

        // Filter permissions with id > 8
        const availablePermissions = computed(() => {
            return props.permissions.filter(p => p.id > 8);
        });

        // Filter other errors (excluding field-specific errors)
        const otherErrors = computed(() => {
            return Object.entries(errors.value)
                .filter(([field]) => field !== 'name' && field !== 'permissions')
                .map(([, msgs]) => msgs[0]);
        });

        const buildUpdateUrl = () => {
            // If the URL contains a {role} placeholder, use that
            if (props.updateUrl.includes('{role}')) {
                return props.updateUrl.replace('{role}', currentRoleId.value);
            }
            // Otherwise replace the trailing numeric segment (e.g. /admin/roles/0) with the current id
            return props.updateUrl.replace(/\/\d+$/, '/' + currentRoleId.value);
        };

        const openModal = async (roleId) => {
            loading.value = true;
            window.setSpinner(document.body);
            currentRoleId.value = roleId;
            resetForm();
            // Open modal immediately; fields are disabled while loading
            showModal.value = true;

            try {
                const response = await fetch(`/admin/roles/${roleId}/json`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                });
                const data = await response.json();

                if (data.success) {
                    form.value.name = data.role.name;
                    // normalize permissions to an array of names if necessary
                    form.value.permissions = Array.isArray(data.permissions)
                        ? data.permissions.map(p => (typeof p === 'object' ? p.name : p))
                        : [];

                    // Store original data for reset functionality
                    originalData.value = {
                        name: data.role.name,
                        permissions: [...form.value.permissions],
                    };
                } else {
                    errors.value = { general: ['Failed to load role data'] };
                }
            } catch (error) {
                console.error('Error fetching role:', error);
                errors.value = { general: ['Failed to load role data'] };
            } finally {
                loading.value = false;
                window.unsetSpinner();
            }
        };

        const closeModal = () => {
            showModal.value = false;
        };

        const resetForm = () => {
            // Restore original data instead of clearing
            if (originalData.value.name || originalData.value.permissions.length > 0) {
                form.value = {
                    name: originalData.value.name,
                    permissions: [...originalData.value.permissions],
                };
            } else {
                form.value = { name: '', permissions: [] };
            }
            errors.value = {};
        };

        const submitRole = async () => {
            loading.value = true;
            errors.value = {};
            window.setSpinner();

            try {
                const url = buildUpdateUrl();

                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify(form.value),
                });

                const data = await response.json();

                if (data.success) {
                    window.unsetSpinner();
                    closeModal();
                    resetForm();
                    emit('role-updated', currentRoleId.value);
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else if (data.errors) {
                    window.unsetSpinner();
                    errors.value = data.errors;
                    loading.value = false;
                } else {
                    window.unsetSpinner();
                    errors.value = { general: ['An unknown error occurred.'] };
                    loading.value = false;
                }
            } catch (error) {
                console.error('Error:', error);
                window.unsetSpinner();
                errors.value = { general: ['An error occurred. Please try again.'] };
                loading.value = false;
            }
        };

        // Expose methods to window for inline handlers
        window.openEditRoleModal = openModal;
        window.closeEditRoleModal = closeModal;

        return {
            showModal,
            loading,
            errors,
            form,
            availablePermissions,
            otherErrors,
            currentRoleId,
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
    max-height: 300px;
    overflow-y: auto;
}

.permissions label {
    margin-bottom: 0.5rem;
    cursor: pointer;
}

.modal.show {
    display: block !important;
}

.invalid-feedback {
    color: #dc3545;
}
</style>
