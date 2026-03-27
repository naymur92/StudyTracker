import { defineStore } from 'pinia'
import axios from 'axios'

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
})

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: null,
        refreshToken: null,
        user: null,
        clientId: null,
        clientSecret: null,
    }),

    persist: {
        paths: ['token', 'refreshToken', 'user', 'clientId', 'clientSecret'],
    },

    getters: {
        isAuthenticated: (state) => !!state.token,
        getToken: (state) => state.token,
        getUser: (state) => state.user,
    },

    actions: {
        setClientCredentials(clientId, clientSecret) {
            this.clientId = clientId
            this.clientSecret = clientSecret
        },

        async register(name, email, password, passwordConfirmation) {
            try {
                const response = await api.post('/auth/register', {
                    name,
                    email,
                    password,
                    password_confirmation: passwordConfirmation,
                })
                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async login(email, password) {
            try {
                const response = await api.post('/auth/token', {
                    email,
                    password,
                }, {
                    headers: {
                        'X-Client-Id': this.clientId,
                        'X-Client-Secret': this.clientSecret,
                    },
                })

                this.token = response.data.access_token
                this.refreshToken = response.data.refresh_token
                this.user = response.data.user

                // Set default auth header
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async resendVerification(email) {
            try {
                const response = await api.post('/auth/resend-verification', {
                    email,
                })
                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async refreshAccessToken() {
            try {
                const response = await api.post('/auth/token/refresh', {
                    refresh_token: this.refreshToken,
                }, {
                    headers: {
                        'X-Client-Id': this.clientId,
                        'X-Client-Secret': this.clientSecret,
                    },
                })

                this.token = response.data.access_token
                this.refreshToken = response.data.refresh_token

                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

                return response.data
            } catch (error) {
                this.logout()
                throw error
            }
        },

        logout() {
            this.token = null
            this.refreshToken = null
            this.user = null
            delete api.defaults.headers.common['Authorization']
        },

        getApiClient() {
            if (this.token) {
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
            }
            return api
        },
    },
})
