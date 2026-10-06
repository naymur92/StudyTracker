<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Mistakes</h1>
                <p class="text-gray-600">Fix errors while they are fresh, then fold them into the topic they belong to.</p>
            </div>
            <button class="btn-primary" @click="openForm()">+ Log mistake</button>
        </div>

        <div class="flex flex-wrap gap-3 bg-white rounded-lg shadow p-4">
            <select v-model="filters.state" @change="load" class="input-base w-auto">
                <option value="open">Open (active + ready)</option>
                <option value="active">Active — reviews pending</option>
                <option value="ready">Ready to merge</option>
                <option value="closed">Closed</option>
                <option value="all">All</option>
            </select>
            <select v-model="filters.cause" @change="load" class="input-base w-auto">
                <option value="">Any cause</option>
                <option value="concept">Concept</option>
                <option value="memory">Memory</option>
                <option value="careless">Careless</option>
            </select>
        </div>

        <div v-if="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
        </div>

        <div v-else-if="mistakes.length === 0" class="bg-white rounded-lg shadow p-8 text-center text-gray-600">
            No mistakes here. Every wrong answer you log becomes a short review.
        </div>

        <div v-else class="space-y-3">
            <div v-for="m in mistakes" :key="m.id" class="bg-white rounded-lg shadow p-5 space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <p class="font-medium text-gray-900 flex-1">{{ m.question }}</p>
                    <div class="flex items-center gap-2 text-xs">
                        <span :class="['px-2 py-0.5 rounded-full font-semibold', stateStyle[m.state]]">{{ stateLabel[m.state] }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-700">{{ m.cause }}</span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                    <div class="bg-red-50 rounded p-2"><span class="text-red-700 font-semibold">Your answer: </span>{{ m.my_answer || '—' }}</div>
                    <div class="bg-success-50 rounded p-2"><span class="text-success-700 font-semibold">Correct: </span>{{ m.correct_answer }}</div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-600">
                    <span>
                        Logged {{ m.logged_on }}
                        <template v-if="m.parent_topic"> · Parent: <router-link :to="`/app/topics/${m.parent_topic.id}`" class="text-primary-700 hover:underline">{{ m.parent_topic.title }}</router-link></template>
                        <template v-if="m.source"> · {{ m.source }}</template>
                        <template v-if="m.next_review_date"> · Next review {{ m.next_review_date }}</template>
                    </span>
                    <span class="flex gap-2">
                        <button v-if="m.state === 'ready'" class="px-3 py-1 rounded bg-primary-600 text-white hover:bg-primary-700" @click="merge(m)">
                            {{ m.parent_topic ? `Merge into ${m.parent_topic.title}` : 'Close' }}
                        </button>
                        <button v-if="m.state !== 'closed'" class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200" @click="openForm(m)">Edit</button>
                        <button class="px-3 py-1 rounded bg-red-50 text-red-700 hover:bg-red-100" @click="remove(m)">Delete</button>
                    </span>
                </div>
            </div>
        </div>

        <MistakeForm :open="formOpen" :mistake="editing" :parent-topic-id="parentFromQuery" @close="closeForm" @saved="load" />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMistakeStore } from '@/stores/mistakes'
import { showConfirm, showError, showSuccess } from '@/helpers/alerts'
import MistakeForm from '@/components/mistakes/MistakeForm.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const mistakeStore = useMistakeStore()

const stateLabel = { active: 'Reviewing', ready: 'Ready to merge', closed: 'Closed' }
const stateStyle = { active: 'bg-blue-100 text-blue-800', ready: 'bg-success-100 text-success-800', closed: 'bg-gray-100 text-gray-600' }

const filters = reactive({ state: 'open', cause: '' })
const loading = ref(false)
const formOpen = ref(false)
const editing = ref(null)
const mistakes = computed(() => mistakeStore.mistakes)
const parentFromQuery = computed(() => (typeof route.query.parent === 'string' ? route.query.parent : null))

const load = async () => {
    loading.value = true
    try {
        const params = { state: filters.state }
        if (filters.cause) params.cause = filters.cause
        await mistakeStore.fetchMistakes(authStore.getApiClient(), params)
    } catch {
        await showError('Failed to load mistakes')
    } finally {
        loading.value = false
    }
}

const openForm = (mistake = null) => {
    editing.value = mistake
    formOpen.value = true
}

const closeForm = () => {
    formOpen.value = false
    editing.value = null
    if (route.query.log) router.replace({ query: {} })
}

const merge = async (m) => {
    try {
        const result = await mistakeStore.mergeMistake(authStore.getApiClient(), m.id)
        await showSuccess(result.appended_to_parent ? `Added to ${m.parent_topic.title}'s recall questions.` : 'Mistake closed.')
        await load()
    } catch (err) {
        await showError(err.response?.data?.msg || 'Failed to merge')
    }
}

const remove = async (m) => {
    if (!(await showConfirm('Delete this mistake and its reviews?', 'Confirm Delete'))) return
    try {
        await mistakeStore.deleteMistake(authStore.getApiClient(), m.id)
    } catch {
        await showError('Failed to delete')
    }
}

onMounted(() => {
    load()
    if (route.query.log) openForm()
})
</script>
