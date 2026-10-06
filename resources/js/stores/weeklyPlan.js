import { defineStore } from 'pinia'

export const useWeeklyPlanStore = defineStore('weeklyPlan', {
    state: () => ({
        week: null,
    }),

    actions: {
        async fetchWeek(api, date = null) {
            const response = await api.get('/study/weekly-plan', { params: date ? { date } : {} })
            this.week = response.data.data
            return this.week
        },

        async createWeek(api, data) {
            const response = await api.post('/study/weekly-plan', data)
            this.week = response.data.data
            return this.week
        },

        async updateWeek(api, weekId, data) {
            const response = await api.patch(`/study/weekly-plan/${weekId}`, data)
            this.week = response.data.data
            return this.week
        },

        async regenerate(api, weekId, gear) {
            const response = await api.post(`/study/weekly-plan/${weekId}/regenerate`, { gear })
            this.week = response.data.data
            return this.week
        },

        async addBlock(api, weekId, data) {
            const response = await api.post(`/study/weekly-plan/${weekId}/blocks`, data)
            return response.data.data
        },

        async updateBlock(api, blockId, data) {
            const response = await api.patch(`/study/blocks/${blockId}`, data)
            return response.data.data
        },

        async deleteBlock(api, blockId) {
            await api.delete(`/study/blocks/${blockId}`)
        },

        async fetchHistory(api, weeks = 8) {
            const response = await api.get('/study/weekly-plan/history', { params: { weeks } })
            return response.data.data
        },
    },
})
