<template>
    <div class="space-y-6 max-w-4xl">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Revision Templates</h1>
                <p class="text-gray-600">Configure your spaced repetition schedule.</p>
            </div>
            <div class="flex gap-3">
                <button @click="addTemplate" class="btn-secondary">+ Add Step</button>
                <button @click="resetTemplates" :disabled="loading" class="btn-secondary disabled:opacity-50">
                    Reset Defaults
                </button>
                <button @click="saveTemplates" :disabled="loading" class="btn-primary disabled:opacity-50">
                    Save
                </button>
            </div>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <div v-if="error" class="p-4 bg-red-50 border-b border-red-200 text-red-700">{{ error }}</div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900">Sequence</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900">Day Offset</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-900">Active</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-900">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="(template, idx) in templates" :key="template.id || idx">
                        <td class="px-4 py-3">
                            <input v-model.number="template.sequence_no" type="number" min="1" class="input-base" />
                        </td>
                        <td class="px-4 py-3">
                            <input v-model="template.name" type="text" class="input-base" />
                        </td>
                        <td class="px-4 py-3">
                            <input v-model.number="template.day_offset" type="number" min="1" class="input-base" />
                        </td>
                        <td class="px-4 py-3">
                            <input v-model="template.is_active" type="checkbox" class="w-4 h-4" />
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button @click="removeTemplate(idx)" class="text-red-600 hover:text-red-800 text-sm">
                                Remove
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRevisionTemplateStore } from '@/stores/revisionTemplates'
import { showError, showInfo, showSuccess } from '@/helpers/alerts'

const authStore = useAuthStore()
const revisionTemplateStore = useRevisionTemplateStore()

const templates = ref([])
const loading = ref(false)
const error = ref(null)

const normalizeTemplates = (rows) => {
    return [...rows]
        .map((row, index) => ({
            id: row.id || null,
            sequence_no: Number(row.sequence_no || (index + 1)),
            name: row.name || `Revision ${index + 1}`,
            day_offset: Number(row.day_offset || 1),
            is_active: Boolean(row.is_active),
        }))
        .sort((a, b) => a.sequence_no - b.sequence_no)
}

const loadTemplates = async () => {
    loading.value = true
    error.value = null
    try {
        const api = authStore.getApiClient()
        await revisionTemplateStore.fetchTemplates(api)
        templates.value = normalizeTemplates(revisionTemplateStore.templates)
    } catch (err) {
        error.value = err.response?.data?.msg || err.response?.data?.message || 'Failed to load revision templates'
        await showError(error.value)
    } finally {
        loading.value = false
    }
}

const addTemplate = () => {
    const next = templates.value.length + 1
    templates.value.push({
        id: null,
        sequence_no: next,
        name: `Revision ${next}`,
        day_offset: next,
        is_active: true,
    })
}

const removeTemplate = (index) => {
    if (templates.value.length === 1) {
        error.value = 'At least one revision step is required.'
        showInfo(error.value)
        return
    }
    templates.value.splice(index, 1)
}

const saveTemplates = async () => {
    loading.value = true
    error.value = null
    try {
        const api = authStore.getApiClient()
        await revisionTemplateStore.updateTemplates(api, normalizeTemplates(templates.value))
        templates.value = normalizeTemplates(revisionTemplateStore.templates)
        await showSuccess('Revision templates saved successfully.')
    } catch (err) {
        error.value = err.response?.data?.msg || err.response?.data?.message || 'Failed to save revision templates'
        await showError(error.value)
    } finally {
        loading.value = false
    }
}

const resetTemplates = async () => {
    loading.value = true
    error.value = null
    try {
        const api = authStore.getApiClient()
        await revisionTemplateStore.resetTemplates(api)
        templates.value = normalizeTemplates(revisionTemplateStore.templates)
        await showSuccess('Revision templates reset to defaults.')
    } catch (err) {
        error.value = err.response?.data?.msg || err.response?.data?.message || 'Failed to reset revision templates'
        await showError(error.value)
    } finally {
        loading.value = false
    }
}

onMounted(loadTemplates)
</script>
