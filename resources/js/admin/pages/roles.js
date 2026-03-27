import { createApp } from 'vue';
import RoleCreateModal from '../components/role/RoleCreateModal.vue';

// Get data from the DOM element's data attributes or window object
const roleModalEl = document.getElementById('role-modal-app');
const permissions = window.rolesData?.permissions || [];
const createUrl = window.rolesData?.createUrl || '/admin/roles';

const app = createApp(RoleCreateModal, {
    permissions,
    createUrl,
});
app.mount('#role-modal-app');
