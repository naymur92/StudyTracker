import { defineStore } from 'pinia'

export const useRevisionTemplateStore = defineStore('revisionTemplates', {
    state: () => ({
        templates: [],
        loading: false,
        error: null,
    }),

    actions: {
        setLoading(loading) {
            this.loading = loading
        },

        setError(error) {
            this.error = error
        },

        async fetchTemplates(api) {
            this.setLoading(true)
            try {
                const response = await api.get('/study/revision-templates')
                this.templates = response.data.data || []
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch revision templates')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async updateTemplates(api, templates) {
            try {
                const payload = templates.map((template, index) => ({
                    sequence_no: Number(template.sequence_no || (index + 1)),
                    day_offset: Number(template.day_offset),
                    name: template.name || `Revision ${index + 1}`,
                    is_active: Boolean(template.is_active),
                }))

                const response = await api.put('/study/revision-templates', {
                    templates: payload,
                })

                this.templates = response.data.data || []
                this.setError(null)
                return this.templates
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to update revision templates')
                throw error
            }
        },

        async resetTemplates(api) {
            try {
                const response = await api.post('/study/revision-templates/reset')
                this.templates = response.data.data || []
                this.setError(null)
                return this.templates
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to reset revision templates')
                throw error
            }
        },
    },
})
