<template>
    <div>
        <!-- Create Permission Modal -->
        <div class="modal fade" tabindex="-1" :class="{ show: showModal }"
            :style="{ display: showModal ? 'block' : 'none' }" aria-labelledby="permissionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 30vw">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="permissionModalLabel">Permission Add Form</h5>
                        <button type="button" class="close" @click="closeModal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form @submit.prevent="submitPermissions">
                        <div class="modal-body">
                            <label class="mb-2"><strong>Permission Name</strong> <span class="text-danger"><i
                                        class="fas fa-xs fa-asterisk"></i></span></label>

                            <div class="form-body px-3">
                                <div v-for="(field, index) in permissionFields" :key="index" class="position-relative"
                                    :class="{ 'mt-2': index > 0 }">
                                    <input v-model="field.value" type="text" class="form-control"
                                        :class="{ 'is-invalid': field.error }"
                                        placeholder="item-list, item-create, item-edit, item-delete, etc"
                                        :disabled="loading">
                                    <i v-if="index > 0" @click="removeField(index)"
                                        class="fa fa-times text-danger position-absolute"
                                        style="cursor: pointer; top: 10px; right: -20px;"></i>
                                    <div v-if="field.error" class="invalid-feedback d-block">{{ field.error }}</div>
                                </div>
                            </div>

                            <!-- Add More Button -->
                            <div class="d-flex justify-content-end my-3">
                                <button type="button" class="btn btn-primary" @click="addField" :disabled="loading">
                                    <i class="fa fa-plus mr-2"></i> Add More Field
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" @click="closeModal"
                                :disabled="loading">Close</button> -->
                            <reset-button variant="danger" text="Reset Form" @click="resetForm" :disabled="loading" />
                            <submit-button variant="success" text="Add Permissions" :loading="loading" />
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
import { ref } from 'vue';

export default {
    name: 'PermissionCreateModal',
    props: {
        createUrl: {
            type: String,
            default: '/admin/permissions',
        },
    },
    setup(props) {
        const showModal = ref(false);
        const loading = ref(false);
        const permissionFields = ref([{ value: '', error: null }]);

        const openModal = () => {
            window.setSpinner();
            resetForm();
            showModal.value = true;
            setTimeout(() => {
                window.unsetSpinner();
            }, 100);
        };

        const closeModal = () => {
            showModal.value = false;
        };

        const resetForm = () => {
            permissionFields.value = [{ value: '', error: null }];
        };

        const addField = () => {
            permissionFields.value.push({ value: '', error: null });
        };

        const removeField = (index) => {
            permissionFields.value.splice(index, 1);
        };

        const submitPermissions = async () => {
            loading.value = true;
            window.setSpinner();

            // Clear previous errors
            permissionFields.value.forEach(field => field.error = null);

            // Prepare data
            const names = permissionFields.value.map(f => f.value);

            try {
                const response = await fetch(props.createUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name: names }),
                });

                const data = await response.json();

                if (data.success || response.ok) {
                    window.unsetSpinner();
                    closeModal();
                    resetForm();
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else if (data.errors) {
                    window.unsetSpinner();
                    // Map errors back to fields
                    Object.keys(data.errors).forEach(key => {
                        const match = key.match(/name\.(\d+)/);
                        if (match) {
                            const index = parseInt(match[1]);
                            if (permissionFields.value[index]) {
                                permissionFields.value[index].error = data.errors[key][0];
                            }
                        }
                    });
                    loading.value = false;
                }
            } catch (error) {
                console.error('Error:', error);
                window.unsetSpinner();
                loading.value = false;
            }
        };

        // Expose to window
        window.openPermissionModal = openModal;
        window.closePermissionModal = closeModal;

        return {
            showModal,
            loading,
            permissionFields,
            openModal,
            closeModal,
            resetForm,
            addField,
            removeField,
            submitPermissions,
        };
    },
};
</script>

<style scoped>
.modal.show {
    display: block !important;
}
</style>
