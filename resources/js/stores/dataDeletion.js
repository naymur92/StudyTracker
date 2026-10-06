import { defineStore } from 'pinia'

/**
 * Data deletion requests: the user's deletable data per category and their
 * requests. Errors are rethrown as the API body ({ msg, errors }) so forms can
 * show field errors (422) and the demo-account message (403).
 */
export const useDataDeletionStore = defineStore('dataDeletion', {
    state: () => ({
        categories: [],
        requests: [],
        loading: false,
        error: null,
    }),

    getters: {
        openRequest: (state) => state.requests.find((r) => r.is_open) || null,
    },

    actions: {
        async fetchSummary(api) {
            try {
                const response = await api.get('/study/data-deletion/summary')
                this.categories = response.data.data?.categories || []
                return this.categories
            } catch (error) {
                this.error = error.response?.data?.msg || 'Failed to load your data summary'
                throw error.response?.data || error
            }
        },

        async fetchRequests(api) {
            try {
                const response = await api.get('/study/data-deletion/requests')
                this.requests = response.data.data || []
                return this.requests
            } catch (error) {
                this.error = error.response?.data?.msg || 'Failed to load your deletion requests'
                throw error.response?.data || error
            }
        },

        async load(api) {
            this.loading = true
            this.error = null
            try {
                await Promise.all([this.fetchSummary(api), this.fetchRequests(api)])
            } finally {
                this.loading = false
            }
        },

        async createRequest(api, payload) {
            try {
                const response = await api.post('/study/data-deletion/requests', payload)
                const request = response.data.data
                this.requests = [request, ...this.requests]
                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },
    },
})
