<template>
    <div class="space-y-6">
        <h1 class="text-3xl font-bold text-gray-900">Reports</h1>

        <div v-if="successMessage" class="p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-700">{{ successMessage }}</p>
        </div>

        <div v-if="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700">{{ errorMessage }}</p>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <section class="bg-white rounded-lg shadow p-6 space-y-4">
                <h2 class="text-xl font-semibold text-gray-900">Download Report</h2>
                <p class="text-sm text-gray-600">
                    Select a start and end month. Maximum download range is 2 months.
                </p>

                <form class="space-y-4" @submit.prevent="downloadReport">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Month</label>
                        <MonthPicker v-model="downloadForm.startMonth" placeholder="Select start month" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Month</label>
                        <MonthPicker v-model="downloadForm.endMonth" placeholder="Select end month" />
                    </div>

                    <button type="submit" :disabled="downloading" class="btn-primary">
                        <span v-if="downloading">Preparing...</span>
                        <span v-else>Download CSV</span>
                    </button>
                </form>
            </section>

            <section class="bg-white rounded-lg shadow p-6 space-y-4">
                <h2 class="text-xl font-semibold text-gray-900">Email Report</h2>
                <p class="text-sm text-gray-600">
                    Select one or more months. You can request emailed reports up to 2 times per month.
                </p>

                <form class="space-y-4" @submit.prevent="requestEmailReport">
                    <div>
                        <p class="block text-sm font-medium text-gray-700 mb-2">Months</p>
                        <div class="max-h-64 overflow-auto border border-gray-200 rounded-lg p-3 space-y-2">
                            <label v-for="month in monthOptions" :key="month.value"
                                class="flex items-center gap-3 text-sm text-gray-700">
                                <input v-model="emailForm.months" type="checkbox" :value="month.value"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                <span>{{ month.label }}</span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" :disabled="sendingEmail" class="btn-primary">
                        <span v-if="sendingEmail">Queueing...</span>
                        <span v-else>Send Report To My Email</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { format, subMonths } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import MonthPicker from '@/components/MonthPicker.vue'

const authStore = useAuthStore()
const downloading = ref(false)
const sendingEmail = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const currentMonth = format(new Date(), 'yyyy-MM')

const downloadForm = ref({
    startMonth: currentMonth,
    endMonth: currentMonth,
})

const emailForm = ref({
    months: [currentMonth],
})

const monthOptions = computed(() => {
    return Array.from({ length: 12 }).map((_, index) => {
        const date = subMonths(new Date(), index)
        return {
            value: format(date, 'yyyy-MM'),
            label: format(date, 'MMMM yyyy'),
        }
    })
})

const resetAlerts = () => {
    successMessage.value = ''
    errorMessage.value = ''
}

const extractErrorMessage = async (error) => {
    const fallback = 'Failed to process the request.'

    if (error?.response?.data instanceof Blob) {
        try {
            const text = await error.response.data.text()
            const parsed = JSON.parse(text)
            return parsed?.msg || parsed?.message || fallback
        } catch (_) {
            return fallback
        }
    }

    return error?.response?.data?.msg || error?.response?.data?.message || error?.msg || error?.message || fallback
}

const downloadReport = async () => {
    if (!downloadForm.value.startMonth || !downloadForm.value.endMonth) {
        errorMessage.value = 'Please select both a start and end month.'
        return
    }
    resetAlerts()
    downloading.value = true

    try {
        const api = authStore.getApiClient()
        const response = await api.get('/study/reports/download', {
            params: {
                start_month: downloadForm.value.startMonth,
                end_month: downloadForm.value.endMonth,
            },
            responseType: 'blob',
        })

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        const disposition = response.headers['content-disposition'] || ''
        const filenameMatch = disposition.match(/filename="?([^";]+)"?/i)
        const filename = filenameMatch?.[1] || 'study-report.csv'

        link.href = url
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        successMessage.value = 'Report downloaded successfully.'
    } catch (error) {
        errorMessage.value = await extractErrorMessage(error)
    } finally {
        downloading.value = false
    }
}

const requestEmailReport = async () => {
    resetAlerts()
    sendingEmail.value = true

    try {
        const api = authStore.getApiClient()
        const payload = {
            months: [...emailForm.value.months].sort(),
        }

        const response = await api.post('/study/reports/email', payload)
        successMessage.value = response?.data?.msg || 'Report email request queued successfully.'
    } catch (error) {
        errorMessage.value = await extractErrorMessage(error)
    } finally {
        sendingEmail.value = false
    }
}
</script>
