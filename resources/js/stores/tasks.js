import { defineStore } from 'pinia'

export const useTaskStore = defineStore('tasks', {
    state: () => ({
        tasks: [],
        task: null,
        loading: false,
        error: null,
        selectedDate: new Date().toISOString().split('T')[0],
    }),

    getters: {
        getTasks: (state) => state.tasks,
        getTask: (state) => state.task,
        isLoading: (state) => state.loading,
        getError: (state) => state.error,
        getSelectedDate: (state) => state.selectedDate,
        getTasksByType: (state) => (type) => state.tasks.filter(t => t.type === type),
    },

    actions: {
        replaceTaskInCollection(updatedTask) {
            if (Array.isArray(this.tasks)) {
                const index = this.tasks.findIndex((task) => task.id === updatedTask.id)
                if (index !== -1) {
                    this.tasks[index] = updatedTask
                }
                return
            }

            if (this.tasks && typeof this.tasks === 'object' && this.tasks.groups) {
                Object.keys(this.tasks.groups).forEach((groupKey) => {
                    const groupTasks = this.tasks.groups[groupKey]

                    if (!Array.isArray(groupTasks)) {
                        return
                    }

                    const index = groupTasks.findIndex((task) => task.id === updatedTask.id)
                    if (index !== -1) {
                        groupTasks[index] = updatedTask
                    }
                })
            }
        },

        setLoading(loading) {
            this.loading = loading
        },

        setError(error) {
            this.error = error
        },

        setSelectedDate(date) {
            this.selectedDate = date
        },

        async fetchDailyTasks(api, date) {
            this.setLoading(true)
            try {
                const response = await api.get('/study/daily-tasks', {
                    params: { date },
                })
                this.tasks = response.data.data
                this.setError(null)
            } catch (error) {
                this.setError(error.response?.data?.msg || error.response?.data?.message || 'Failed to fetch tasks')
                throw error
            } finally {
                this.setLoading(false)
            }
        },

        async completeTask(api, taskId, data) {
            try {
                const response = await api.post(`/study/tasks/${taskId}/complete`, data)
                this.replaceTaskInCollection(response.data.data)
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.msg || error.response?.data?.message || 'Failed to complete task')
                throw error
            }
        },

        async skipTask(api, taskId, data) {
            try {
                const response = await api.post(`/study/tasks/${taskId}/skip`, data)
                this.replaceTaskInCollection(response.data.data)
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.msg || error.response?.data?.message || 'Failed to skip task')
                throw error
            }
        },

        async rescheduleTask(api, taskId, scheduledDate) {
            try {
                const response = await api.post(`/study/tasks/${taskId}/reschedule`, {
                    scheduled_date: scheduledDate,
                })
                this.replaceTaskInCollection(response.data.data)
                return response.data.data
            } catch (error) {
                this.setError(error.response?.data?.msg || error.response?.data?.message || 'Failed to reschedule task')
                throw error
            }
        },
    },
})
