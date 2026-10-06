import { defineStore } from 'pinia'

export const useReviewStore = defineStore('review', {
    state: () => ({
        load: null,
        queue: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchQueue(api, date = null) {
            this.loading = true
            try {
                const response = await api.get('/study/review-queue', { params: date ? { date } : {} })
                this.queue = response.data.data
                this.error = null
                return this.queue
            } catch (error) {
                this.error = error.response?.data?.msg || 'Failed to load the review queue'
                throw error
            } finally {
                this.loading = false
            }
        },

        async gradeTask(api, taskId, grade, reviewSeconds = null) {
            const body = { recall_grade: grade }
            if (reviewSeconds) body.review_seconds = reviewSeconds
            const response = await api.post(`/study/tasks/${taskId}/complete`, body)
            return response.data.data
        },

        async fetchLoad(api, date = null) {
            const response = await api.get('/study/review-load', { params: date ? { date } : {} })
            this.load = response.data.data
            return this.load
        },
    },
})
