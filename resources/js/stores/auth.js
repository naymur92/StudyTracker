import { defineStore } from 'pinia'
import axios from 'axios'
import { resolveApiBaseUrl } from '@/config/urls'

let refreshRequestPromise = null

const AUTH_ENDPOINTS = [
    '/auth/token',
    '/auth/token/refresh',
    '/auth/register',
    '/auth/resend-verification',
    '/auth/forgot-password/request',
    '/auth/forgot-password/verify',
]

const isAuthEndpoint = (requestUrl = '') => AUTH_ENDPOINTS.some((path) => requestUrl.includes(path))

const setDefaultAuthorizationHeader = (token) => {
    if (token) {
        api.defaults.headers.common.Authorization = `Bearer ${token}`
        return
    }

    delete api.defaults.headers.common.Authorization
}

const api = axios.create({
    baseURL: resolveApiBaseUrl(),
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
            if (responseData?.access_token) {
                return responseData
            }

            if (responseData?.extra?.access_token) {
                return responseData.extra
            }

            if (responseData?.data?.access_token) {
                return responseData.data
            }

            return {}
        },

        getUserPayload(responseData) {
            return responseData?.user || responseData?.data?.user || null
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

            setDefaultAuthorizationHeader(this.token)
        },

        async queueRefreshRequest() {
            if (!refreshRequestPromise) {
                refreshRequestPromise = this.refreshAccessToken().finally(() => {
                    refreshRequestPromise = null
                })
            }

            return refreshRequestPromise
        },

        async ensureValidAccessToken() {
            if (this.token && !this.isTokenExpired()) {
                setDefaultAuthorizationHeader(this.token)
                return this.token
            }

            if (!this.refreshToken) {
                if (this.token && this.isTokenExpired()) {
                    this.logout()
                }

                return this.token
            }

            await this.queueRefreshRequest()
            return this.token
        },

        async restoreSession() {
            this.initializeApiInterceptors()
            this.ensureClientCredentials()

            if (!this.token && !this.refreshToken) {
                return false
            }

            if (this.token && !this.isTokenExpired()) {
                setDefaultAuthorizationHeader(this.token)
                return true
            }

            if (!this.refreshToken) {
                this.logout()
                return false
            }

            try {
                await this.queueRefreshRequest()
                return !!this.token
            } catch (_) {
                return false
            }
        },

        initializeApiInterceptors() {
            if (this.interceptorInitialized) {
                return
            }

            this.interceptorInitialized = true

            api.interceptors.request.use(async (config) => {
                const requestUrl = config.url || ''

                if (!isAuthEndpoint(requestUrl)) {
                    await this.ensureValidAccessToken()
                }

                if (this.token && !isAuthEndpoint(requestUrl)) {
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

                    if (status !== 401 || originalRequest._retry || isAuthEndpoint(requestUrl) || !this.refreshToken) {
                        return Promise.reject(error)
                    }

                    originalRequest._retry = true

                    try {
                        await this.queueRefreshRequest()

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
                this.user = this.getUserPayload(response.data)

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

                if (!this.refreshToken) {
                    throw new Error('Refresh token is not available.')
                }

                if (!this.clientId || !this.clientSecret) {
                    throw new Error('OAuth client credentials are not configured.')
                }

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
            setDefaultAuthorizationHeader(null)
        },

        getApiClient() {
            this.initializeApiInterceptors()

            if (this.token) {
                setDefaultAuthorizationHeader(this.token)
            }

            return api
        },
    },
})
