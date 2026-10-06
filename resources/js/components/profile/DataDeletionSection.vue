<template>
    <div class="border-b border-gray-200 pb-6" id="delete-my-data">
        <h2 class="text-xl font-bold text-gray-900 mb-1">Delete my data</h2>
        <p class="text-sm text-gray-600 mb-4">
            Choose what to clear from your account. An admin reviews each request; approved data is archived and then
            deleted. Your account itself is not deleted.
            <router-link to="/app/guide#profile" class="text-primary-600 hover:underline">Learn more</router-link>
        </p>

        <div v-if="store.loading" class="text-sm text-gray-500">Loading your data…</div>
        <div v-else-if="store.error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">{{ store.error }}</div>

        <template v-else>
            <!-- An open request replaces the form -->
            <div v-if="store.openRequest" class="p-4 rounded-lg border border-amber-200 bg-amber-50 text-sm text-amber-900">
                <p class="font-medium">
                    Request {{ store.openRequest.reference }} is {{ store.openRequest.status_label.toLowerCase() }}.
                </p>
                <p class="mt-1">
                    {{ store.openRequest.categories.map((c) => c.label).join(', ') }} ·
                    requested {{ formatDate(store.openRequest.created_at) }}.
                    You can make a new request once this one is finished.
                </p>
            </div>

            <form v-else @submit.prevent="openConfirm" class="space-y-4">
                <p v-if="authStore.isDemoUser" class="text-xs text-amber-600">The demo account cannot request data deletion.</p>

                <fieldset :disabled="authStore.isDemoUser" class="space-y-2">
                    <legend class="sr-only">Data to delete</legend>
                    <label v-for="category in store.categories" :key="category.key"
                        class="flex items-start gap-3 p-3 border rounded-lg cursor-pointer"
                        :class="selected.includes(category.key) ? 'border-red-400 bg-red-50' : 'border-gray-200'">
                        <input type="checkbox" class="mt-1 w-4 h-4" :value="category.key" v-model="selected" />
                        <span class="flex-1">
                            <span class="flex justify-between gap-2">
                                <span class="font-medium text-gray-900">{{ category.label }}</span>
                                <span class="text-sm text-gray-500 whitespace-nowrap">{{ category.total }} {{ category.total === 1 ? 'item' : 'items' }}</span>
                            </span>
                            <span class="block text-xs text-gray-600">{{ category.description }}</span>
                        </span>
                    </label>
                    <p v-if="fieldErrors.categories" class="text-sm text-red-600">{{ fieldErrors.categories[0] }}</p>

                    <div>
                        <label for="data-deletion-reason" class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                        <textarea id="data-deletion-reason" v-model="reason" rows="2" maxlength="1000" class="input-base"
                            placeholder="Helps the admin understand your request"></textarea>
                        <p v-if="fieldErrors.reason" class="text-sm text-red-600">{{ fieldErrors.reason[0] }}</p>
                    </div>

                    <button type="submit" :disabled="!selected.length"
                        class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Request deletion
                    </button>
                </fieldset>
            </form>

            <div v-if="message" class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">{{ message }}</div>

            <!-- History -->
            <div v-if="store.requests.length" class="mt-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Your requests</h3>
                <ul class="divide-y divide-gray-100 border border-gray-200 rounded-lg">
                    <li v-for="request in store.requests" :key="request.id" class="p-3 text-sm">
                        <div class="flex flex-wrap justify-between gap-2">
                            <span class="font-medium text-gray-900">{{ request.categories.map((c) => c.label).join(', ') }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium" :class="statusClass(request.status)">{{ request.status_label }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ request.reference }} · requested {{ formatDate(request.created_at) }}
                            <template v-if="request.completed_at"> · completed {{ formatDate(request.completed_at) }}</template>
                        </p>
                        <p v-if="request.status === 'rejected' && request.rejection_reason" class="text-xs text-gray-700 mt-1">
                            Reason: {{ request.rejection_reason }}
                        </p>
                        <p v-if="request.deleted_counts" class="text-xs text-gray-600 mt-1">
                            Deleted: {{ request.deleted_counts.map((c) => `${c.count} ${c.label.toLowerCase()}`).join(', ') }}
                        </p>
                        <p v-if="request.status === 'restored'" class="text-xs text-gray-600 mt-1">An admin restored this data from the archive.</p>
                    </li>
                </ul>
            </div>
        </template>

        <DataDeletionConfirmModal v-if="confirming" :categories="selectedCategories" :submitting="submitting"
            :error="submitError" @close="confirming = false" @confirm="submit" />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { formatDate as fmtDate } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import { useDataDeletionStore } from '@/stores/dataDeletion'
import DataDeletionConfirmModal from './DataDeletionConfirmModal.vue'

const authStore = useAuthStore()
const store = useDataDeletionStore()

const selected = ref([])
const reason = ref('')
const confirming = ref(false)
const submitting = ref(false)
const submitError = ref('')
const fieldErrors = ref({})
const message = ref('')

const selectedCategories = computed(() => store.categories.filter((c) => selected.value.includes(c.key)))

const statusClass = (status) => ({
    pending: 'bg-amber-100 text-amber-800',
    approved: 'bg-blue-100 text-blue-800',
    processing: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed: 'bg-red-100 text-red-800',
    rejected: 'bg-gray-100 text-gray-700',
    restored: 'bg-indigo-100 text-indigo-800',
}[status] || 'bg-gray-100 text-gray-700')

const formatDate = (date) => {
    if (!date) return ''
    const parsed = new Date(String(date).replace(' ', 'T'))
    return Number.isNaN(parsed.getTime()) ? '' : fmtDate(parsed, 'MMM d, yyyy')
}

const openConfirm = () => {
    if (!selected.value.length || authStore.isDemoUser) return
    submitError.value = ''
    fieldErrors.value = {}
    message.value = ''
    confirming.value = true
}

const submit = async (confirmation) => {
    submitting.value = true
    submitError.value = ''
    try {
        const response = await store.createRequest(authStore.getApiClient(), {
            categories: selected.value,
            reason: reason.value || null,
            confirmation,
        })
        message.value = response.msg || 'Request sent. An admin will review it.'
        confirming.value = false
        selected.value = []
        reason.value = ''
    } catch (err) {
        if (err.errors) {
            fieldErrors.value = err.errors
            submitError.value = Object.values(err.errors).flat()[0] || 'Please check the form.'
        } else {
            submitError.value = err.msg || err.message || 'Could not send the request.'
        }
    } finally {
        submitting.value = false
    }
}

onMounted(() => {
    store.load(authStore.getApiClient()).catch(() => {})
})
</script>
