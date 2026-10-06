import { defineStore } from 'pinia'

export const usePreferencesStore = defineStore('preferences', {
    state: () => ({
        preferences: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchPreferences(api) {
            this.loading = true
            try {
                const response = await api.get('/study/preferences')
                this.preferences = response.data.data
                this.error = null
                return this.preferences
            } catch (error) {
                this.error = error.response?.data?.msg || error.response?.data?.message || 'Failed to fetch preferences'
                throw error
            } finally {
                this.loading = false
            }
        },

        async updatePreferences(api, values) {
            const response = await api.put('/study/preferences', values)
            this.preferences = response.data.data
            return this.preferences
        },
    },
})
