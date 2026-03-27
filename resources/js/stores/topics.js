import { defineStore } from 'pinia'

export const useTopicStore = defineStore('topics', {
    state: () => ({
        topics: [],
        topic: null,
        studyTasks: [],
        practiceLogs: [],
        loading: false,
        error: null,
        pagination: {
            page: 1,
            perPage: 15,
            total: 0,
            lastPage: 1,
        },
        filters: {
            search: '',
            status: 'active',
            category_id: null,
            difficulty: null,
        },
    }),

    getters: {
        getTopics: (state) => state.topics,
        getTopic: (state) => state.topic,
        isLoading: (state) => state.loading,
        getError: (state) => state.error,
        getPagination: (state) => state.pagination,
    },

    actions: {
        setLoading(loading) {
            this.loading = loading
        },

        setError(error) {
            this.error = error
        },

        setFilters(filters) {
            this.filters = { ...this.filters, ...filters }
        },

        resetPagination() {
            this.pagination.page = 1
        },

        async fetchTopics(api) {
            this.setLoading(true)
            try {
                const response = await api.get('/study/topics', {
                    params: {
                        ...this.filters,
                        page: this.pagination.page,
                        per_page: this.pagination.perPage,
                    },
                })
                this.topics = response.data.data
                this.pagination = {
                    page: response.data.data.current_page,
                    perPage: response.data.data.per_page,
                    total: response.data.data.total,
                    lastPage: response.data.data.last_page,
                }
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch topics')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async fetchTopic(api, id) {
            this.setLoading(true)
            try {
                const response = await api.get(`/study/topics/${id}`)
                const data = response.data.data
                this.topic = data.topic
                this.studyTasks = data.study_tasks || []
                this.practiceLogs = data.practice_logs || []
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to fetch topic')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async createTopic(api, topicData) {
            try {
                const response = await api.post('/study/topics', topicData)
                this.topics.unshift(response.data.data)
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to create topic')
                throw error
            }
        },

        async updateTopic(api, id, topicData) {
            try {
                const response = await api.put(`/study/topics/${id}`, topicData)
                const index = this.topics.findIndex(t => t.id === id)
                if (index !== -1) {
                    this.topics[index] = response.data.data
                }
                this.topic = response.data.data
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to update topic')
                throw error
            }
        },

        async deleteTopic(api, id) {
            try {
                await api.delete(`/study/topics/${id}`)
                this.topics = this.topics.filter(t => t.id !== id)
            } catch (error) {
                this.setError(error.response?.data?.message || 'Failed to delete topic')
                throw error
            }
        },

        clearTopic() {
            this.topic = null
            this.setError(null)
        },
    },
})
