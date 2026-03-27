import { defineStore } from 'pinia'

export const usePracticeLogStore = defineStore('practiceLogs', {
    state: () => ({
        logs: [],
        loading: false,
        error: null,
        pagination: {
            page: 1,
            perPage: 20,
            total: 0,
            lastPage: 1,
        },
        filters: {
            topic_id: null,
            practice_type: null,
            date_from: null,
            date_to: null,
        },
    }),

    getters: {
        getLogs: (state) => state.logs,
        isLoading: (state) => state.loading,
        getError: (state) => state.error,
        getPagination: (state) => state.pagination,
    },

    actions: {
        setLoading(loading) {
            this.loading = loading
        },

        setError(error) {
            this.error = error
        },

        setFilters(filters) {
            this.filters = { ...this.filters, ...filters }
        },

        resetPagination() {
            this.pagination.page = 1
        },

        async fetchPracticeLogs(api) {
            this.setLoading(true)
            try {
                const response = await api.get('/study/practice-logs', {
                    params: {
                        ...this.filters,
                        page: this.pagination.page,
                        per_page: this.pagination.perPage,
                    },
                })
                this.logs = response.data.data
                this.pagination = {
                    page: response.data.data.current_page,
                    perPage: response.data.data.per_page,
                    total: response.data.data.total,
                    lastPage: response.data.data.last_page,
                }
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch practice logs')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async createPracticeLog(api, logData) {
            try {
                const response = await api.post('/study/practice-logs', logData)
                this.logs.unshift(response.data.data)
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to create practice log')
                throw error
            }
        },

        async updatePracticeLog(api, id, logData) {
            try {
                const response = await api.put(`/study/practice-logs/${id}`, logData)
                const index = this.logs.findIndex(l => l.id === id)
                if (index !== -1) {
                    this.logs[index] = response.data.data
                }
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to update practice log')
                throw error
            }
        },

        async deletePracticeLog(api, id) {
            try {
                await api.delete(`/study/practice-logs/${id}`)
                this.logs = this.logs.filter(l => l.id !== id)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to delete practice log')
                throw error
            }
        },
    },
})
