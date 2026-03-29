import { defineStore } from 'pinia'
import { useAuthStore } from './auth'

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

                const authStore = useAuthStore()
                authStore.isDemo = !!this.profile?.is_demo

                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch profile')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async updateProfile(api, payload) {
            this.setLoading(true)
            try {
                const response = await api.patch('/user', payload)
                this.profile = {
                    ...(this.profile || {}),
                    ...(response.data.data || {}),
                }
                this.setError(null)
                return response.data
            } catch (error) {
                this.setError(error.response?.data?.msg || 'Failed to update profile')
                throw error.response?.data || error
            } finally {
                this.setLoading(false)
            }
        },

        async changePassword(api, payload) {
            this.setLoading(true)
            try {
                const response = await api.post('/user/change-password', payload)
                this.setError(null)
                return response.data
            } catch (error) {
                this.setError(error.response?.data?.msg || 'Failed to change password')
                throw error.response?.data || error
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
