import { defineStore } from 'pinia'

export const useMistakeStore = defineStore('mistakes', {
    state: () => ({
        mistakes: [],
        meta: null,
        loading: false,
    }),

    actions: {
        async fetchMistakes(api, params = {}) {
            this.loading = true
            try {
                const response = await api.get('/study/mistakes', { params })
                this.mistakes = response.data.data || []
                this.meta = response.data.meta || null
                return this.mistakes
            } finally {
                this.loading = false
            }
        },

        async createMistake(api, data) {
            const response = await api.post('/study/mistakes', data)
            return response.data.data
        },

        async updateMistake(api, id, data) {
            const response = await api.patch(`/study/mistakes/${id}`, data)
            return response.data.data
        },

        async deleteMistake(api, id) {
            await api.delete(`/study/mistakes/${id}`)
            this.mistakes = this.mistakes.filter((m) => m.id !== id)
        },

        async mergeMistake(api, id) {
            const response = await api.post(`/study/mistakes/${id}/merge`)
            return response.data.data
        },
    },
})
