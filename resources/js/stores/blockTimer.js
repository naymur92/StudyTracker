import { defineStore } from 'pinia'

// Other tabs re-sync when this one changes the timer.
const channel = typeof BroadcastChannel !== 'undefined' ? new BroadcastChannel('study-timer') : null

/**
 * Block timer state. The server owns the timer; this store mirrors the last
 * response (persisted, so a reload paints at once). Alert bookkeeping lives in
 * components/timer/timerAlerts.js.
 */
export const useBlockTimerStore = defineStore('blockTimer', {
    state: () => ({
        timer: null, // active timer object with its block, or null
        recent: null, // latest run that ended in the last 12 hours, or null
        serverOffsetMs: 0, // server clock − browser clock
        syncedAt: 0, // server-clock ms at which timer.used_seconds was true
        wrapup: null, // finished block waiting for "What did you learn?"
        shownSessionId: null, // active run this browser last showed
        revision: 0, // bumps whenever block outcomes may have changed
    }),

    persist: {
        paths: ['timer', 'recent', 'serverOffsetMs', 'syncedAt', 'wrapup', 'shownSessionId'],
    },

    getters: {
        isActive: (state) => !!state.timer,
        activeBlockId: (state) => state.timer?.block_id ?? null,
    },

    actions: {
        serverNow() {
            return Date.now() + this.serverOffsetMs
        },

        /** Take a `{ timer, recent, server_time }` response as the new truth. */
        apply(payload, { broadcast = true } = {}) {
            if (!payload) return
            const serverMs = Date.parse(payload.server_time)
            if (!Number.isNaN(serverMs)) {
                this.serverOffsetMs = serverMs - Date.now()
                this.syncedAt = serverMs
            }
            this.timer = payload.timer ?? null
            this.recent = payload.recent ?? null
            this.revision += 1
            if (broadcast) channel?.postMessage('changed')
        },

        async sync(api) {
            const response = await api.get('/study/timer')
            this.apply(response.data.data, { broadcast: false })
            return response.data.data
        },

        async act(api, request) {
            try {
                const response = await request(api)
                this.apply(response.data.data)
                return response.data
            } catch (err) {
                // 409 (another timer) and 422 (state changed elsewhere) carry or imply fresh state.
                const status = err.response?.status
                if (status === 409 && err.response.data?.data) this.apply(err.response.data.data, { broadcast: false })
                else if (status === 422) await this.sync(api).catch(() => {})
                throw err
            }
        },

        start(api, blockId, extras = {}) {
            return this.act(api, (a) => a.post(`/study/blocks/${blockId}/timer/start`, extras))
        },
        pause(api, blockId = this.activeBlockId) {
            return this.act(api, (a) => a.post(`/study/blocks/${blockId}/timer/pause`, {}))
        },
        resume(api, blockId = this.activeBlockId) {
            return this.act(api, (a) => a.post(`/study/blocks/${blockId}/timer/resume`, {}))
        },
        stop(api, blockId = this.activeBlockId) {
            return this.act(api, (a) => a.post(`/study/blocks/${blockId}/timer/stop`, {}))
        },
        discard(api, blockId = this.activeBlockId) {
            return this.act(api, (a) => a.delete(`/study/blocks/${blockId}/timer`))
        },

        /** Remember a finished run's block for the topic-page wrap-up. */
        setWrapup(run) {
            const block = run?.block
            this.wrapup = block?.topic
                ? {
                    session_id: run.session_id,
                    block_id: block.id,
                    topic_id: block.topic.id,
                    topic_title: block.topic.title,
                    block_date: block.block_date,
                    slot: block.slot,
                    planned_task: block.planned_task,
                    minutes: block.actual_minutes,
                }
                : null
        },

        clearWrapup() {
            this.wrapup = null
        },
    },
})

/** Subscribe to timer changes made in other tabs; returns an unsubscribe function. */
export const onOtherTabChange = (callback) => {
    if (!channel) return () => {}
    const handler = () => callback()
    channel.addEventListener('message', handler)
    return () => channel.removeEventListener('message', handler)
}
