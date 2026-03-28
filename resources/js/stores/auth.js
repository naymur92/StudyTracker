import { defineStore } from 'pinia'
import axios from 'axios'

let refreshRequestPromise = null

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
        tokenExpiresAt: null,
        user: null,
        clientId: null,
        clientSecret: null,
        interceptorInitialized: false,
    }),

    persist: {
        paths: ['token', 'refreshToken', 'tokenExpiresAt', 'user', 'clientId', 'clientSecret'],
    },

    getters: {
        isAuthenticated: (state) => !!state.token,
        getToken: (state) => state.token,
        getUser: (state) => state.user,
    },

    actions: {
        ensureClientCredentials() {
            if (!this.clientId) {
                this.clientId = import.meta.env.VITE_OAUTH_CLIENT_ID || null
            }

            if (!this.clientSecret) {
                this.clientSecret = import.meta.env.VITE_OAUTH_CLIENT_SECRET || null
            }
        },

        setClientCredentials(clientId, clientSecret) {
            this.clientId = clientId
            this.clientSecret = clientSecret
        },

        getTokenPayload(responseData) {
            // Supports both current flat format and nested payload fallback.
            if (responseData?.access_token) {
                return responseData
            }

            return responseData?.data || {}
        },

        isTokenExpired() {
            if (!this.tokenExpiresAt) {
                return false
            }

            return Date.now() >= Number(this.tokenExpiresAt)
        },

        setTokenState(tokenPayload) {
            this.token = tokenPayload?.access_token || null
            this.refreshToken = tokenPayload?.refresh_token || null

            const expiresIn = Number(tokenPayload?.expires_in)
            if (Number.isFinite(expiresIn) && expiresIn > 0) {
                // 15-second safety margin to avoid edge expiration during request transit.
                this.tokenExpiresAt = Date.now() + (expiresIn * 1000) - 15000
            } else {
                this.tokenExpiresAt = null
            }
        },

        initializeApiInterceptors() {
            if (this.interceptorInitialized) {
                return
            }

            this.interceptorInitialized = true

            api.interceptors.request.use(async (config) => {
                const requestUrl = config.url || ''

                const isAuthEndpoint = [
                    '/auth/token',
                    '/auth/token/refresh',
                    '/auth/register',
                    '/auth/resend-verification',
                    '/auth/forgot-password/request',
                    '/auth/forgot-password/verify',
                ].some((path) => requestUrl.includes(path))

                if (!isAuthEndpoint && this.refreshToken && this.isTokenExpired()) {
                    if (!refreshRequestPromise) {
                        refreshRequestPromise = this.refreshAccessToken().finally(() => {
                            refreshRequestPromise = null
                        })
                    }

                    await refreshRequestPromise
                }

                if (this.token && !isAuthEndpoint) {
                    config.headers = config.headers || {}
                    config.headers.Authorization = `Bearer ${this.token}`
                }

                return config
            })

            api.interceptors.response.use(
                (response) => response,
                async (error) => {
                    const originalRequest = error.config || {}
                    const status = error.response?.status
                    const requestUrl = originalRequest.url || ''

                    const isAuthEndpoint = [
                        '/auth/token',
                        '/auth/token/refresh',
                        '/auth/register',
                        '/auth/resend-verification',
                        '/auth/forgot-password/request',
                        '/auth/forgot-password/verify',
                    ].some((path) => requestUrl.includes(path))

                    if (status !== 401 || originalRequest._retry || isAuthEndpoint || !this.refreshToken) {
                        return Promise.reject(error)
                    }

                    originalRequest._retry = true

                    try {
                        if (!refreshRequestPromise) {
                            refreshRequestPromise = this.refreshAccessToken().finally(() => {
                                refreshRequestPromise = null
                            })
                        }

                        await refreshRequestPromise

                        originalRequest.headers = originalRequest.headers || {}
                        originalRequest.headers.Authorization = `Bearer ${this.token}`

                        return api(originalRequest)
                    } catch (refreshError) {
                        return Promise.reject(refreshError)
                    }
                },
            )
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
                this.ensureClientCredentials()

                const response = await api.post('/auth/token', {
                    email,
                    password,
                }, {
                    headers: {
                        'X-Client-Id': this.clientId,
                        'X-Client-Secret': this.clientSecret,
                    },
                })

                const tokenPayload = this.getTokenPayload(response.data)
                this.setTokenState(tokenPayload)
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
                this.ensureClientCredentials()

                const response = await api.post('/auth/token/refresh', {
                    refresh_token: this.refreshToken,
                }, {
                    headers: {
                        'X-Client-Id': this.clientId,
                        'X-Client-Secret': this.clientSecret,
                    },
                })

                const tokenPayload = this.getTokenPayload(response.data)
                this.setTokenState(tokenPayload)

                if (!this.token || !this.refreshToken) {
                    throw new Error('Invalid refresh token response payload.')
                }

                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`

                return response.data
            } catch (error) {
                this.logout()
                throw error
            }
        },

        async requestForgotPassword(email) {
            try {
                const response = await api.post('/auth/forgot-password/request', {
                    email,
                })
                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        async verifyForgotPassword(email, code, password, passwordConfirmation) {
            try {
                const response = await api.post('/auth/forgot-password/verify', {
                    email,
                    code,
                    password,
                    password_confirmation: passwordConfirmation,
                })
                return response.data
            } catch (error) {
                throw error.response?.data || error
            }
        },

        logout() {
            this.token = null
            this.refreshToken = null
            this.tokenExpiresAt = null
            this.user = null
            delete api.defaults.headers.common['Authorization']
        },

        getApiClient() {
            this.initializeApiInterceptors()

            if (this.token) {
                api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
            }
            return api
        },
    },
})
