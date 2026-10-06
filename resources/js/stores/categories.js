import { defineStore } from 'pinia'

export const useCategoryStore = defineStore('categories', {
    state: () => ({
        categories: [],
        loading: false,
        error: null,
    }),

    getters: {
        getCategories: (state) => state.categories,
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

        async fetchCategories(api) {
            this.setLoading(true)
            try {
                const response = await api.get('/study/categories')
                this.categories = response.data.data
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch categories')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async createCategory(api, categoryData) {
            try {
                const response = await api.post('/study/categories', categoryData)
                this.categories.push(response.data.data)
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to create category')
                throw error
            }
        },

        async updateCategory(api, id, categoryData) {
            try {
                const response = await api.put(`/study/categories/${id}`, categoryData)
                const index = this.categories.findIndex(c => c.id === id)
                if (index !== -1) {
                    // keep fields the update response omits (e.g. review_schedule)
                    this.categories[index] = { ...this.categories[index], ...response.data.data }
                }
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to update category')
                throw error
            }
        },

        async deleteCategory(api, id) {
            try {
                await api.delete(`/study/categories/${id}`)
                this.categories = this.categories.filter(c => c.id !== id)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to delete category')
                throw error
            }
        },

        async fetchPresets(api) {
            const response = await api.get('/study/schedule-presets')
            return response.data.data
        },

        async saveSchedule(api, categoryId, schedule) {
            const response = await api.put(`/study/categories/${categoryId}/schedule`, schedule)
            const saved = response.data.data
            this.setCategorySchedule(categoryId, {
                preset_key: saved.preset_key,
                offsets: saved.offsets,
                repeat_every_days: saved.repeat_every_days,
                repeat_until: saved.repeat_until,
            })
            return saved
        },

        async deleteSchedule(api, categoryId) {
            await api.delete(`/study/categories/${categoryId}/schedule`)
            this.setCategorySchedule(categoryId, null)
        },

        setCategorySchedule(categoryId, schedule) {
            const index = this.categories.findIndex(c => c.id === categoryId)
            if (index !== -1) {
                this.categories[index] = { ...this.categories[index], review_schedule: schedule }
            }
        },
    },
})
