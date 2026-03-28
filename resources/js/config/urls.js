const trimTrailingSlash = (value = '') => value.replace(/\/+$/, '')

export const resolveApiBaseUrl = () => {
    const configuredApiUrl = trimTrailingSlash(import.meta.env.VITE_API_URL || '')

    if (configuredApiUrl) {
        return configuredApiUrl
    }

    if (typeof window !== 'undefined' && window.location?.origin) {
        return `${trimTrailingSlash(window.location.origin)}/api`
    }

    return '/api'
}

export const resolveAppBaseUrl = () => {
    const configuredAppUrl = trimTrailingSlash(import.meta.env.VITE_APP_URL || '')

    if (configuredAppUrl) {
        return configuredAppUrl
    }

    if (typeof window !== 'undefined' && window.location?.origin) {
        return trimTrailingSlash(window.location.origin)
    }

    const apiBaseUrl = resolveApiBaseUrl()

    return apiBaseUrl.endsWith('/api') ? apiBaseUrl.slice(0, -4) : apiBaseUrl
}