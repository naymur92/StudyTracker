<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
                <p class="text-gray-600">Organize your study materials</p>
            </div>
            <button @click="showCreateModal = true" class="btn-primary">
                + New Category
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-700">{{ error }}</p>
        </div>

        <!-- Categories Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="category in categories" :key="category.id" :style="{ borderLeftColor: category.color }"
                class="card p-6 border-l-4">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <span :class="getCategoryIconClass(category.icon)" :style="{ color: category.color }"></span>
                        <h3 class="text-lg font-semibold text-gray-900">{{ category.name }}</h3>
                    </div>
                    <div class="flex items-center gap-2" v-if="!category.is_system">
                        <button @click="openEditModal(category)"
                            class="px-3 py-1 text-sm text-primary-600 hover:bg-primary-50 rounded-lg transition-colors">
                            Edit
                        </button>
                        <button @click="deleteCategory(category.id)"
                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <p class="text-gray-600 text-sm">{{ category.topics_count || 0 }} topics</p>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
                <h2 class="text-xl font-bold text-gray-900 mb-4">New Category</h2>
                <form @submit.prevent="createCategory" class="space-y-4">
                    <input v-model="newCategory.name" type="text" placeholder="Category name" class="input-base"
                        required />
                    <div class="flex gap-4">
                        <input v-model="newCategory.color" type="color"
                            class="w-12 h-10 border border-gray-300 rounded cursor-pointer" />
                        <input v-model="newCategory.icon" type="text" placeholder="Icon class"
                            class="input-base flex-1" />
                    </div>
                    <div class="flex gap-4">
                        <button type="submit" class="btn-primary flex-1">Create</button>
                        <button type="button" @click="showCreateModal = false"
                            class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
            <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Edit Category</h2>
                <form @submit.prevent="updateCategory" class="space-y-4">
                    <input v-model="editCategory.name" type="text" placeholder="Category name" class="input-base"
                        required />
                    <div class="flex gap-4">
                        <input v-model="editCategory.color" type="color"
                            class="w-12 h-10 border border-gray-300 rounded cursor-pointer" />
                        <input v-model="editCategory.icon" type="text" placeholder="Icon class"
                            class="input-base flex-1" />
                    </div>
                    <div class="flex gap-4">
                        <button type="submit" class="btn-primary flex-1">Update</button>
                        <button type="button" @click="showEditModal = false"
                            class="btn-secondary flex-1">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useCategoryStore } from '@/stores/categories'
import { showConfirm, showError, showSuccess } from '@/helpers/alerts'

const authStore = useAuthStore()
const categoryStore = useCategoryStore()

const loading = ref(false)
const error = ref(null)
const showCreateModal = ref(false)
const showEditModal = ref(false)

const newCategory = reactive({
    name: '',
    color: '#0ea5e9',
    icon: 'fa-folder',
})

const editCategory = reactive({
    id: null,
    name: '',
    color: '#0ea5e9',
    icon: 'fa-folder',
})

const categories = computed(() => categoryStore.categories)

const getCategoryIconClass = (icon) => {
    if (!icon) return 'fas fa-folder text-2xl'

    const hasStylePrefix = ['fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands']
        .some((prefix) => icon.includes(prefix))

    if (hasStylePrefix) return `${icon} text-2xl`
    if (icon.startsWith('fa-')) return `fas ${icon} text-2xl`
    return `fas fa-${icon} text-2xl`
}

const fetchCategories = async () => {
    loading.value = true
    error.value = null
    try {
        const api = authStore.getApiClient()
        await categoryStore.fetchCategories(api)
    } catch (err) {
        error.value = 'Failed to load categories'
        await showError(error.value)
    } finally {
        loading.value = false
    }
}

const createCategory = async () => {
    try {
        const api = authStore.getApiClient()
        await categoryStore.createCategory(api, newCategory)
        newCategory.name = ''
        newCategory.color = '#0ea5e9'
        newCategory.icon = 'fa-folder'
        showCreateModal.value = false
        await showSuccess('Category created successfully.')
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to create category'
        await showError(error.value)
    }
}

const openEditModal = (category) => {
    editCategory.id = category.id
    editCategory.name = category.name
    editCategory.color = category.color || '#0ea5e9'
    editCategory.icon = category.icon || 'fa-folder'
    showEditModal.value = true
}

const updateCategory = async () => {
    try {
        const api = authStore.getApiClient()
        await categoryStore.updateCategory(api, editCategory.id, {
            name: editCategory.name,
            color: editCategory.color,
            icon: editCategory.icon,
        })
        showEditModal.value = false
        await showSuccess('Category updated successfully.')
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to update category'
        await showError(error.value)
    }
}

const deleteCategory = async (id) => {
    const confirmed = await showConfirm('Delete this category?', 'Confirm Delete')
    if (!confirmed) return

    try {
        const api = authStore.getApiClient()
        await categoryStore.deleteCategory(api, id)
        await showSuccess('Category deleted successfully.')
    } catch (err) {
        error.value = 'Failed to delete category'
        await showError(error.value)
    }
}

onMounted(() => {
    fetchCategories()
})
</script>
