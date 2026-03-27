<template>
    <div class="space-y-6">
        <h1 class="text-3xl font-bold text-gray-900">Calendar</h1>

        <!-- Month Navigation -->
        <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
            <button @click="previousMonth" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
            <div class="text-center">
                <p class="font-semibold text-lg text-gray-900">{{ monthYear }}</p>
            </div>
            <button @click="nextMonth" class="p-2 rounded-lg hover:bg-gray-100">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                        clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-700">{{ error }}</p>
        </div>

        <!-- Calendar Grid -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="grid grid-cols-7 gap-2 mb-4">
                <div v-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day"
                    class="text-center font-semibold text-gray-700 py-2">
                    {{ day }}
                </div>
            </div>

            <div class="grid grid-cols-7 gap-2">
                <div v-for="date in calendarDays" :key="date.toString()" :class="[
                    'p-4 rounded-lg border border-gray-200 text-center min-h-20 cursor-pointer hover:bg-primary-50 transition-colors',
                    formatDate(date) === formatDate(today.value) ? 'bg-primary-100 border-primary-600' : '',
                    date.getMonth() !== currentMonth ? 'bg-gray-50 text-gray-400' : ''
                ]">
                    <p class="font-semibold">{{ date.getDate() }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ getTaskCount(date) }} tasks</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { format, startOfMonth, endOfMonth, eachDayOfInterval, addMonths, subMonths } from 'date-fns'
import { useAuthStore } from '@/stores/auth'

const today = ref(new Date())
const currentDate = ref(new Date())
const authStore = useAuthStore()
const error = ref(null)
const calendarData = ref({})

const currentMonth = computed(() => currentDate.value.getMonth())
const monthYear = computed(() => format(currentDate.value, 'MMMM yyyy'))

const calendarDays = computed(() => {
    const start = startOfMonth(currentDate.value)
    const end = endOfMonth(currentDate.value)

    // Get first day of the week for start date
    const startDate = new Date(start)
    startDate.setDate(startDate.getDate() - start.getDay())

    // Get last day of the week for end date
    const endDate = new Date(end)
    endDate.setDate(endDate.getDate() + (6 - end.getDay()))

    return eachDayOfInterval({
        start: startDate,
        end: endDate,
    })
})

const formatDate = (date) => format(date, 'yyyy-MM-dd')

const fetchCalendar = async () => {
    error.value = null

    try {
        const api = authStore.getApiClient()
        const response = await api.get('/study/calendar', {
            params: {
                year: currentDate.value.getFullYear(),
                month: currentDate.value.getMonth() + 1,
            },
        })

        calendarData.value = response.data?.data?.calendar || {}
    } catch (err) {
        calendarData.value = {}
        error.value = err.response?.data?.message || 'Failed to load calendar data'
    }
}

const getTaskCount = (date) => {
    const key = formatDate(date)
    return calendarData.value[key]?.total || 0
}

const previousMonth = () => {
    currentDate.value = subMonths(currentDate.value, 1)
    fetchCalendar()
}

const nextMonth = () => {
    currentDate.value = addMonths(currentDate.value, 1)
    fetchCalendar()
}

onMounted(() => {
    fetchCalendar()
})
</script>
