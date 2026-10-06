<template>
    <div class="space-y-6 max-w-3xl">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Review</h1>
            <p class="text-gray-600">Answer from memory first, then reveal and grade yourself honestly.</p>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <div v-else-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">{{ error }}</div>

        <!-- Session summary -->
        <div v-else-if="finished" class="bg-white rounded-lg shadow p-8 space-y-4">
            <h2 class="text-2xl font-bold text-gray-900">Session summary</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div><p class="text-sm text-gray-600">Reviewed</p><p class="text-2xl font-bold">{{ results.length }}</p></div>
                <div><p class="text-sm text-gray-600">Time</p><p class="text-2xl font-bold">{{ totalMinutes }} min</p></div>
                <div><p class="text-sm text-gray-600">Still due</p><p class="text-2xl font-bold">{{ remaining }}</p></div>
                <div><p class="text-sm text-gray-600">Relearn tomorrow</p><p class="text-2xl font-bold">{{ gradeCounts.again + gradeCounts.hard }}</p></div>
            </div>
            <div class="flex flex-wrap gap-2">
                <span v-for="g in gradeOrder" :key="g" :class="['px-3 py-1 rounded-full text-sm font-semibold', gradeStyle[g].chip]">
                    {{ gradeStyle[g].label }}: {{ gradeCounts[g] }}
                </span>
            </div>
            <p v-if="remaining > 0" class="text-sm text-gray-600">
                {{ remaining }} {{ remaining === 1 ? 'review stays' : 'reviews stay' }} due and will appear in your next session.
            </p>
            <div class="flex gap-3">
                <router-link to="/app" class="btn-primary">Back to dashboard</router-link>
                <button v-if="remaining > 0" type="button" class="btn-secondary" @click="resume">Continue reviewing</button>
            </div>
        </div>

        <!-- Empty queue -->
        <div v-else-if="items.length === 0" class="bg-white rounded-lg shadow p-8 text-center space-y-2">
            <p class="text-xl font-semibold text-gray-900">Nothing due right now</p>
            <p class="text-gray-600">Your reviews are up to date. Come back tomorrow.</p>
            <router-link to="/app" class="btn-primary inline-block mt-2">Back to dashboard</router-link>
        </div>

        <!-- Budget notice -->
        <div v-else-if="budgetNotice" class="bg-amber-50 border border-amber-300 rounded-lg p-6 space-y-3 text-amber-900">
            <p class="text-lg font-semibold">You have reached your {{ summary.budget_minutes }}-minute review budget</p>
            <p>The remaining {{ items.length - index }} reviews stay due and carry over. Stopping here keeps reviews short and sustainable.</p>
            <div class="flex gap-3">
                <button type="button" class="btn-primary" @click="stop">Stop here</button>
                <button type="button" class="btn-secondary" @click="continuePastBudget">Continue</button>
            </div>
        </div>

        <!-- Relearn note after Again / Hard -->
        <div v-else-if="relearn" class="bg-white rounded-lg shadow p-6 space-y-4 border-l-4 border-amber-400">
            <p class="text-lg font-semibold text-gray-900">Relearn it now — about 5 minutes</p>
            <p class="text-gray-700">
                Re-study the answer key below until you can say it without looking.
                <strong>{{ relearn.topicTitle }}</strong> comes back {{ relearn.nextLabel }} for a quick check.
            </p>
            <div v-if="relearn.summary" class="bg-gray-50 rounded p-3 text-gray-800 whitespace-pre-wrap">{{ relearn.summary }}</div>
            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-primary" @click="nextCard">Continue</button>
                <router-link :to="{ path: '/app/mistakes', query: relearn.topicId ? { log: 1, parent: relearn.topicId } : { log: 1 } }" target="_blank"
                    class="btn-secondary">Log a mistake</router-link>
            </div>
        </div>

        <!-- Current card -->
        <div v-else-if="current" class="bg-white rounded-lg shadow p-6 md:p-8 space-y-6">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-gray-500 font-medium">{{ index + 1 }} / {{ items.length }}</span>
                <span v-if="current.topic.category" class="px-2 py-0.5 rounded-full font-semibold text-white"
                    :style="{ backgroundColor: current.topic.category.color || '#6b7280' }">{{ current.topic.category.name }}</span>
                <span v-if="current.topic.lane" class="px-2 py-0.5 rounded-full font-semibold bg-gray-100 text-gray-700">{{ current.topic.lane }}</span>
                <span v-if="current.topic.kind === 'mistake'" class="px-2 py-0.5 rounded-full font-semibold bg-red-100 text-red-800">Mistake</span>
                <span v-if="current.task.review_kind === 'relearn'" class="px-2 py-0.5 rounded-full font-semibold bg-amber-100 text-amber-800">Relearn check</span>
                <span v-if="current.task.is_overdue" class="px-2 py-0.5 rounded-full font-semibold bg-red-50 text-red-700">Overdue since {{ current.task.scheduled_date }}</span>
                <span v-if="!current.within_budget" class="px-2 py-0.5 rounded-full font-semibold bg-amber-50 text-amber-800">Beyond budget</span>
            </div>

            <h2 class="text-2xl font-bold text-gray-900">{{ current.topic.title }}</h2>

            <div v-if="current.topic.recall_questions.length" class="space-y-3">
                <p class="text-sm text-gray-600">Answer each question from memory — out loud, on paper or in code — before revealing.</p>
                <ol class="space-y-3">
                    <li v-for="(q, i) in current.topic.recall_questions" :key="i" class="border border-gray-200 rounded-lg p-3">
                        <p class="font-medium text-gray-900">{{ i + 1 }}. {{ q.question }}</p>
                        <p v-if="revealed && q.answer" class="mt-2 text-sm text-gray-700 bg-success-50 rounded p-2 whitespace-pre-wrap">{{ q.answer }}</p>
                    </li>
                </ol>
            </div>
            <div v-else class="rounded-lg border border-dashed border-gray-300 p-4 space-y-2">
                <p class="font-medium text-gray-900">Blank-page recall</p>
                <p class="text-sm text-gray-600">
                    This topic has no recall questions yet. Write or say everything you remember about it, then reveal
                    and compare.
                </p>
                <router-link :to="`/app/topics/${current.topic.id}/edit`" class="text-sm text-primary-600 hover:underline">Add questions</router-link>
            </div>

            <div v-if="revealed" class="space-y-4 pt-4 border-t border-gray-200">
                <div v-if="current.topic.mistake_details" class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="bg-red-50 rounded p-3"><p class="text-red-700 font-semibold">Your earlier answer</p><p>{{ current.topic.mistake_details.my_answer || '—' }}</p></div>
                    <div class="bg-success-50 rounded p-3"><p class="text-success-700 font-semibold">Correct answer</p><p>{{ current.topic.mistake_details.correct_answer }}</p></div>
                    <p class="text-gray-600 md:col-span-2">Cause: {{ current.topic.mistake_details.cause }}<span v-if="current.topic.mistake_details.source"> · Source: {{ current.topic.mistake_details.source }}</span></p>
                </div>
                <div v-if="current.topic.summary">
                    <p class="text-sm text-gray-600 mb-1">Summary (answer key)</p>
                    <p class="text-gray-900 whitespace-pre-wrap">{{ current.topic.summary }}</p>
                </div>
                <p v-if="current.topic.practice_prompt" class="text-sm text-gray-700"><span class="text-gray-500">Practice:</span> {{ current.topic.practice_prompt }}</p>
                <a v-if="current.topic.source_link" :href="current.topic.source_link" target="_blank" rel="noopener noreferrer" class="text-sm text-primary-600 hover:underline break-all">{{ current.topic.source_link }}</a>
            </div>

            <div class="space-y-3 pt-2">
                <button v-if="!revealed" type="button" class="btn-primary w-full" @click="reveal">Reveal answers <span class="opacity-70 text-xs">(Space)</span></button>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <button v-for="(g, i) in gradeOrder" :key="g" type="button" :disabled="!revealed || submitting" @click="grade(g)"
                        :class="['rounded-lg border px-3 py-3 text-left disabled:opacity-40 disabled:cursor-not-allowed', gradeStyle[g].button]">
                        <span class="block font-semibold">{{ gradeStyle[g].label }} <span class="text-xs opacity-60">({{ i + 1 }})</span></span>
                        <span class="block text-xs opacity-80">{{ previewLabel(current.grade_preview[g]) }}</span>
                    </button>
                </div>
                <p v-if="!revealed" class="text-xs text-gray-500 text-center">Grades unlock after you reveal the answers.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { differenceInCalendarDays, format } from 'date-fns'
import { useAuthStore } from '@/stores/auth'
import { useReviewStore } from '@/stores/review'
import { parseLocalDate } from '@/helpers/dates'
import { showError } from '@/helpers/alerts'

const authStore = useAuthStore()
const reviewStore = useReviewStore()

const MAX_SECONDS = 3600
const gradeOrder = ['again', 'hard', 'good', 'easy']
const gradeStyle = {
    again: { label: 'Again', button: 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100', chip: 'bg-red-100 text-red-800' },
    hard: { label: 'Hard', button: 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100', chip: 'bg-amber-100 text-amber-800' },
    good: { label: 'Good', button: 'border-success-200 bg-success-50 text-success-800 hover:bg-success-100', chip: 'bg-success-100 text-success-800' },
    easy: { label: 'Easy', button: 'border-blue-200 bg-blue-50 text-blue-800 hover:bg-blue-100', chip: 'bg-blue-100 text-blue-800' },
}

const loading = ref(false)
const error = ref(null)
const items = ref([])
const summary = ref({})
const queueDate = ref(null)
const index = ref(0)
const revealed = ref(false)
const submitting = ref(false)
const results = ref([])
const relearn = ref(null)
const budgetNotice = ref(false)
const budgetAcknowledged = ref(false)
const finished = ref(false)
let shownAt = performance.now()

const current = computed(() => items.value[index.value] || null)
const remaining = computed(() => Math.max(items.value.length - results.value.length, 0))
const totalMinutes = computed(() => Math.round(results.value.reduce((sum, r) => sum + r.seconds, 0) / 60))
const gradeCounts = computed(() => gradeOrder.reduce((acc, g) => ({ ...acc, [g]: results.value.filter((r) => r.grade === g).length }), {}))

const previewLabel = (date) => {
    if (!date) return 'No more reviews'
    const days = differenceInCalendarDays(parseLocalDate(date), parseLocalDate(queueDate.value))
    if (days <= 1) return 'Back tomorrow'
    if (days < 60) return `In ${days} days`
    return `On ${format(parseLocalDate(date), 'd MMM yyyy')}`
}

const startCard = () => {
    revealed.value = false
    shownAt = performance.now()
}

const reveal = () => {
    revealed.value = true
}

const grade = async (value) => {
    if (!revealed.value || submitting.value || !current.value) return
    submitting.value = true
    const item = current.value
    const seconds = Math.min(Math.max(Math.round((performance.now() - shownAt) / 1000), 1), MAX_SECONDS)
    try {
        const api = authStore.getApiClient()
        const task = await reviewStore.gradeTask(api, item.task.id, value, seconds)
        results.value.push({ grade: value, seconds })
        if (value === 'again' || value === 'hard') {
            relearn.value = {
                // A mistake's own mistakes belong to its parent topic.
                topicId: item.topic.kind === 'mistake' ? (item.topic.parent_topic?.id || null) : item.topic.id,
                topicTitle: item.topic.title,
                summary: item.topic.summary,
                nextLabel: previewLabel(task.schedule_outcome?.next_review_date || item.grade_preview[value]).toLowerCase(),
            }
        } else {
            nextCard()
        }
    } catch (err) {
        await showError(err.response?.data?.msg || 'Failed to save the grade')
    } finally {
        submitting.value = false
    }
}

const nextCard = () => {
    relearn.value = null
    index.value += 1
    if (index.value >= items.value.length) {
        finished.value = true
        return
    }
    if (!budgetAcknowledged.value && !current.value.within_budget) {
        budgetNotice.value = true
        return
    }
    startCard()
}

const stop = () => {
    budgetNotice.value = false
    finished.value = true
}

const continuePastBudget = () => {
    budgetNotice.value = false
    budgetAcknowledged.value = true
    startCard()
}

const resume = () => {
    finished.value = false
    budgetAcknowledged.value = true
    if (index.value >= items.value.length) {
        load()
        return
    }
    startCard()
}

const onKey = (event) => {
    const tag = event.target?.tagName
    if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || !current.value || finished.value || budgetNotice.value || relearn.value) return
    if (event.code === 'Space') {
        event.preventDefault()
        reveal()
    } else if (['1', '2', '3', '4'].includes(event.key) && revealed.value) {
        grade(gradeOrder[Number(event.key) - 1])
    }
}

const load = async () => {
    loading.value = true
    error.value = null
    try {
        const api = authStore.getApiClient()
        const queue = await reviewStore.fetchQueue(api)
        items.value = queue.items || []
        summary.value = queue.summary || {}
        queueDate.value = queue.date
        index.value = 0
        results.value = []
        finished.value = false
        startCard()
    } catch (err) {
        error.value = err.response?.data?.msg || 'Failed to load the review queue'
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    window.addEventListener('keydown', onKey)
    load()
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>
