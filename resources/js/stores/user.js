import { defineStore } from 'pinia'

export const useUserStore = defineStore('user', {
    state: () => ({
        profile: null,
        loading: false,
        error: null,
    }),

    getters: {
        getProfile: (state) => state.profile,
        isLoading: (state) => state.loading,
        getError: (state) => state.error,
    },

    actions: {
        setLoading(loading) {
            this.loading = loading
        },

        setError(error) {
            this.error = error
        },

        async fetchProfile(api) {
            this.setLoading(true)
            try {
                const response = await api.get('/user')
                this.profile = response.data.data
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch profile')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        setProfile(profile) {
            this.profile = profile
        },

        clearProfile() {
            this.profile = null
            this.setError(null)
        },
    },
})
