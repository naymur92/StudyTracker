<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Settings & Configuration</h4>
                    </div>
                    <div class="card-body">
                        <!-- Settings Tabs -->
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: currentTab === 'site' }"
                                    @click="switchTab('site')" href="#">
                                    <i class="bi bi-gear"></i> Site Configuration
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: currentTab === 'backup' }"
                                    @click="switchTab('backup')" href="#">
                                    <i class="bi bi-database"></i> Backup & Restore
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" :class="{ active: currentTab === 'cache' }"
                                    @click="switchTab('cache')" href="#">
                                    <i class="bi bi-lightning"></i> Cache Management
                                </a>
                            </li>
                        </ul>

                        <!-- Site Configuration Tab -->
                        <div v-show="currentTab === 'site'">
                            <h5 class="mb-3">Site Configuration</h5>
                            <form @submit.prevent="updateSiteConfig">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="app_name" class="form-label d-block">Application Name</label>
                                            <input type="text" class="form-control" id="app_name"
                                                v-model="siteConfig.app_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <select2 v-model="siteConfig.timezone" :options="timezones" label="Timezone"
                                                id="timezone" placeholder="Select Timezone" />
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_email" class="form-label d-block">Contact Email</label>
                                            <input type="email" class="form-control" id="contact_email"
                                                v-model="siteConfig.contact_email">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="contact_phone" class="form-label d-block">Contact Phone</label>
                                            <input type="text" class="form-control" id="contact_phone"
                                                v-model="siteConfig.contact_phone">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="app_description" class="form-label d-block">Application
                                        Description</label>
                                    <textarea class="form-control" id="app_description"
                                        v-model="siteConfig.app_description" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="app_logo" class="form-label d-block">Application Logo</label>
                                    <input type="file" class="form-control" id="app_logo" @change="handleLogoUpload"
                                        accept="image/*">
                                    <small class="form-text text-muted d-block">Accepted formats: JPEG, PNG, JPG, GIF,
                                        SVG (Max: 2MB)</small>
                                    <div class="mt-2" v-if="logoPreview">
                                        <img :src="logoPreview" alt="Logo Preview" class="img-thumbnail"
                                            style="max-width: 200px;">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary" :disabled="loading">
                                        <i class="bi bi-save"></i> Save Configuration
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Backup & Restore Tab -->
                        <div v-show="currentTab === 'backup'">
                            <h5 class="mb-3">Database Backup & Restore</h5>

                            <div class="mb-4">
                                <button type="button" class="btn btn-success" @click="createBackup" :disabled="loading">
                                    <i class="bi bi-plus-circle"></i> Create New Backup
                                </button>
                                <button type="button" class="btn btn-secondary" @click="loadBackups"
                                    :disabled="loading">
                                    <i class="bi bi-arrow-clockwise"></i> Refresh List
                                </button>
                            </div>

                            <div v-if="backupsLoading">
                                <p class="text-muted">Loading backups...</p>
                            </div>
                            <div v-else-if="backups.length === 0">
                                <p class="text-muted">No backups found</p>
                            </div>
                            <div v-else class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Size</th>
                                            <th>Created At</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="backup in backups" :key="backup.name">
                                            <td>{{ backup.name }}</td>
                                            <td>{{ backup.size }}</td>
                                            <td>{{ backup.created_at }}</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary"
                                                    @click="downloadBackup(backup.name)">
                                                    <i class="bi bi-download"></i> Download
                                                </button>
                                                <button class="btn btn-sm btn-warning"
                                                    @click="restoreBackup(backup.name)">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                                <button class="btn btn-sm btn-danger"
                                                    @click="deleteBackup(backup.name)">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Cache Management Tab -->
                        <div v-show="currentTab === 'cache'">
                            <h5 class="mb-3">Cache Management</h5>

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Cache Information</h6>
                                            <table class="table table-sm">
                                                <tbody>
                                                    <tr>
                                                        <th>Cache Driver:</th>
                                                        <td>{{ cacheInfo.driver || '-' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Available Stores:</th>
                                                        <td>{{ cacheInfo.stores?.join(', ') || '-' }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Clear Specific Cache</h6>
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-outline-primary"
                                                    @click="clearCache('clear')" :disabled="loading">
                                                    <i class="bi bi-trash"></i> Clear Application Cache
                                                </button>
                                                <button type="button" class="btn btn-outline-primary"
                                                    @click="clearCache('clear-config')" :disabled="loading">
                                                    <i class="bi bi-trash"></i> Clear Configuration Cache
                                                </button>
                                                <button type="button" class="btn btn-outline-primary"
                                                    @click="clearCache('clear-route')" :disabled="loading">
                                                    <i class="bi bi-trash"></i> Clear Route Cache
                                                </button>
                                                <button type="button" class="btn btn-outline-primary"
                                                    @click="clearCache('clear-view')" :disabled="loading">
                                                    <i class="bi bi-trash"></i> Clear View Cache
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Optimization</h6>
                                            <div class="d-grid gap-2">
                                                <button type="button" class="btn btn-success"
                                                    @click="clearCache('optimize')" :disabled="loading">
                                                    <i class="bi bi-lightning"></i> Optimize Application
                                                </button>
                                                <button type="button" class="btn btn-warning"
                                                    @click="clearCache('clear-optimization')" :disabled="loading">
                                                    <i class="bi bi-x-circle"></i> Clear Optimization
                                                </button>
                                                <button type="button" class="btn btn-danger"
                                                    @click="clearCache('clear-all')" :disabled="loading">
                                                    <i class="bi bi-trash-fill"></i> Clear All Caches
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mt-4">
                                <i class="bi bi-info-circle"></i>
                                <strong>Note:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Clear Cache:</strong> Clears the application cache</li>
                                    <li><strong>Clear Config:</strong> Clears configuration cache</li>
                                    <li><strong>Clear Route:</strong> Clears route cache</li>
                                    <li><strong>Clear View:</strong> Clears compiled view files</li>
                                    <li><strong>Optimize:</strong> Caches config and routes for better performance</li>
                                    <li><strong>Clear Optimization:</strong> Removes all cached files</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { showSuccess, showError, showInfo, showConfirm } from '@/helpers/notifications';

const currentTab = ref('site');
const loading = ref(false);
const backupsLoading = ref(false);

// Timezone options
const timezones = ref([
    { value: 'UTC', label: 'UTC' },
    { value: 'America/New_York', label: 'America/New York' },
    { value: 'America/Chicago', label: 'America/Chicago' },
    { value: 'America/Denver', label: 'America/Denver' },
    { value: 'America/Los_Angeles', label: 'America/Los Angeles' },
    { value: 'Europe/London', label: 'Europe/London' },
    { value: 'Europe/Paris', label: 'Europe/Paris' },
    { value: 'Asia/Tokyo', label: 'Asia/Tokyo' },
    { value: 'Asia/Shanghai', label: 'Asia/Shanghai' },
    { value: 'Asia/Dubai', label: 'Asia/Dubai' },
    { value: 'Asia/Dhaka', label: 'Asia/Dhaka' },
    { value: 'Australia/Sydney', label: 'Australia/Sydney' }
]);

// Site Configuration
const siteConfig = ref({
    app_name: '',
    timezone: '',
    app_description: '',
    contact_email: '',
    contact_phone: '',
});
const logoFile = ref(null);
const logoPreview = ref('');

// Backups
const backups = ref([]);

// Cache
const cacheInfo = ref({
    driver: '',
    stores: []
});

const switchTab = (tab) => {
    currentTab.value = tab;

    if (tab === 'backup') {
        loadBackups();
    } else if (tab === 'cache') {
        loadCacheInfo();
    }
};

const loadSiteConfig = async () => {
    try {
        const response = await axios.get('/admin/settings/site-config');
        if (response.data.success) {
            siteConfig.value = { ...response.data.data };
            if (response.data.data.app_logo) {
                logoPreview.value = `/${response.data.data.app_logo}`;
            }
        }
    } catch (error) {
        console.error('Failed to load site config:', error);
        showError(error.response?.data?.message || 'Failed to load site configuration');
    }
};

const updateSiteConfig = async () => {
    loading.value = true;
    const formData = new FormData();

    // Only append non-empty values (exclude app_logo as it's handled separately)
    Object.keys(siteConfig.value).forEach(key => {
        if (key === 'app_logo') return; // Skip app_logo, handled separately

        const value = siteConfig.value[key];
        if (value !== null && value !== undefined && value !== '') {
            formData.append(key, value);
        }
    });

    // Only append logo file if a new file is selected
    if (logoFile.value) {
        formData.append('app_logo', logoFile.value);
    }

    try {
        const response = await axios.post('/admin/settings/site-config', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        if (response.data.success) {
            showSuccess('Site configuration updated successfully');
            await loadSiteConfig();
        }
    } catch (error) {
        // console.error('Failed to update site config:', error);
        const errorMessage = error.response?.data?.message || 'Failed to update site configuration';
        showError(errorMessage);
    } finally {
        loading.value = false;
    }
};

const handleLogoUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        logoFile.value = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

// Backup Management
const loadBackups = async () => {
    backupsLoading.value = true;
    try {
        const response = await axios.get('/admin/backups');
        if (response.data.success) {
            backups.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to load backups:', error);
        showError(error.response?.data?.message || 'Failed to load backups');
    } finally {
        backupsLoading.value = false;
    }
};

const createBackup = async () => {
    const confirmed = await showConfirm('Create a new database backup?');
    if (!confirmed) return;

    loading.value = true;
    showInfo('Creating backup...');

    try {
        const response = await axios.post('/admin/backups/create');
        if (response.data.success) {
            showSuccess('Backup created successfully');
            await loadBackups();
        }
    } catch (error) {
        console.error('Failed to create backup:', error);
        showError(error.response?.data?.message || 'Failed to create backup');
    } finally {
        loading.value = false;
    }
};

const downloadBackup = (filename) => {
    window.location.href = `/admin/backups/${encodeURIComponent(filename)}/download`;
};

const restoreBackup = async (filename) => {
    const confirmed = await showConfirm(
        `This will overwrite the current database!`,
        `Restore backup "${filename}"?`
    );
    if (!confirmed) return;

    loading.value = true;
    showInfo('Restoring backup...');

    try {
        const response = await axios.post(`/admin/backups/${encodeURIComponent(filename)}/restore`);
        if (response.data.success) {
            showSuccess('Backup restored successfully');
        }
    } catch (error) {
        console.error('Failed to restore backup:', error);
        showError(error.response?.data?.message || 'Failed to restore backup');
    } finally {
        loading.value = false;
    }
};

const deleteBackup = async (filename) => {
    const confirmed = await showConfirm(
        `This action cannot be undone.`,
        `Delete backup "${filename}"?`
    );
    if (!confirmed) return;

    loading.value = true;
    try {
        const response = await axios.delete(`/admin/backups/${encodeURIComponent(filename)}`);
        if (response.data.success) {
            showSuccess('Backup deleted successfully');
            await loadBackups();
        }
    } catch (error) {
        console.error('Failed to delete backup:', error);
        showError(error.response?.data?.message || 'Failed to delete backup');
    } finally {
        loading.value = false;
    }
};

// Cache Management
const loadCacheInfo = async () => {
    try {
        const response = await axios.get('/admin/cache/info');
        if (response.data.success) {
            cacheInfo.value = response.data.data;
        }
    } catch (error) {
        console.error('Failed to load cache info:', error);
    }
};

const clearCache = async (type) => {
    loading.value = true;
    showInfo('Processing...');

    try {
        const response = await axios.post(`/admin/cache/${type}`);
        if (response.data.success) {
            showSuccess(response.data.message || 'Cache cleared successfully');
        }
    } catch (error) {
        console.error('Failed to clear cache:', error);
        showError(error.response?.data?.message || 'Failed to clear cache');
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadSiteConfig();
});
</script>

<style scoped>
.nav-link {
    cursor: pointer;
}
</style>
