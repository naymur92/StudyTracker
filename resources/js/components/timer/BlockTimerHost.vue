<template>
    <div class="fixed bottom-4 right-4 z-40 flex flex-col items-end gap-2 pointer-events-none print:hidden">
        <!-- Toasts: break / finish alerts and timer outcomes -->
        <div v-for="toast in toasts" :key="toast.id" role="status"
            class="pointer-events-auto w-[min(22rem,calc(100vw-2rem))] rounded-xl shadow-lg border p-3 bg-white"
            :class="toastBorder[toast.tone] || toastBorder.info">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900">{{ toast.title }}</p>
                    <p v-if="toast.body" class="text-xs text-gray-600 mt-0.5">{{ toast.body }}</p>
                    <p v-if="toast.countdown" class="text-xs text-primary-700 mt-1">{{ toast.countdownLabel }} in {{ toast.countdown }}s…</p>
                </div>
                <button type="button" @click="dismissToast(toast.id)" class="text-gray-400 hover:text-gray-600 text-sm" aria-label="Dismiss">✕</button>
            </div>
            <div v-if="toast.actions.length" class="flex gap-2 mt-2">
                <button v-for="action in toast.actions" :key="action.label" type="button" @click="runToastAction(toast, action)"
                    class="px-3 py-1 rounded-lg text-xs font-semibold bg-primary-50 text-primary-700 hover:bg-primary-100">{{ action.label }}</button>
            </div>
        </div>

        <template v-if="store.timer && live">
            <TimerPanel v-if="panelOpen" class="pointer-events-auto" :timer="store.timer" :live="live" :busy="busy"
                :can-pop-out="canPopOut" :today="today" @action="handleAction" @close="panelOpen = false" />
            <MiniTimer class="pointer-events-auto" :timer="store.timer" :live="live" :open="panelOpen" @toggle="panelOpen = !panelOpen" />
        </template>
    </div>
</template>

<script setup>
import { computed, createApp, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { getActivePinia } from 'pinia'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { onOtherTabChange } from '@/stores/blockTimer'
import { todayLocal } from '@/helpers/dates'
import { showConfirm, showError } from '@/helpers/alerts'
import { slotLabels } from '@/components/weekly/weeklyMeta'
import MiniTimer from './MiniTimer.vue'
import TimerPanel from './TimerPanel.vue'
import PopOutTimer from './PopOutTimer.vue'
import { formatClock, liveTimer, upcomingBoundaries } from './timerMath'
import { dismissToast, fireAlert, runToastAction, toasts, unlockAudio } from './timerAlerts'
import { finishFlow, useTimerActions } from './useTimerActions'

const SYNC_EVERY_MS = 120_000
const LATE_ALERT_LIMIT_S = 120 // skip a break-end alert this much later than it was due

const router = useRouter()
const authStore = useAuthStore()
const { store, pause, resume, stop, discard } = useTimerActions()
const api = () => authStore.getApiClient()
const today = todayLocal()

const panelOpen = ref(false)
const busy = ref(false)
const now = ref(store.serverNow())
const live = computed(() => liveTimer(store.timer, store.syncedAt, now.value))
const toastBorder = { info: 'border-gray-200', break: 'border-amber-300', finish: 'border-success-300' }

// ── Sync and reconcile ─────────────────────────────────────────────

/**
 * After a sync: remember the run this browser shows; when it has ended,
 * alert. `navigate` only when the page was open while it ran out — after a
 * fresh load (finished while away) the notice offers the link instead.
 */
const reconcile = (navigate) => {
    if (store.timer) {
        store.shownSessionId = store.timer.session_id
        return
    }
    const shown = store.shownSessionId
    if (!shown) return
    store.shownSessionId = null
    if (store.recent?.session_id === shown && store.recent.end_reason === 'finished') {
        finishFlow(store, router, store.recent, { navigate })
    }
}

const sync = async (navigate = true) => {
    if (!authStore.isAuthenticated) return
    try {
        await store.sync(api())
        reconcile(navigate)
    } catch {
        // Offline or a transient error: keep the mirror, retry on the next trigger.
    }
}

// ── Clock and boundaries ───────────────────────────────────────────

let ticker = null
let syncTimer = null
let boundaryTimers = []

const startTicker = () => {
    now.value = store.serverNow()
    if (ticker || !store.timer) return
    ticker = setInterval(() => (now.value = store.serverNow()), 1000)
    syncTimer = setInterval(() => sync(), SYNC_EVERY_MS)
}

const stopTicker = () => {
    clearInterval(ticker)
    clearInterval(syncTimer)
    ticker = syncTimer = null
}

const onBoundary = (boundary, timer) => {
    const current = liveTimer(store.timer, store.syncedAt, store.serverNow())
    if (!current || store.timer?.session_id !== timer.session_id) return
    const task = timer.block?.planned_task || timer.block?.topic?.title || slotLabels[timer.block?.slot] || 'your block'
    const key = `${timer.session_id}:${boundary.event}`

    if (boundary.event.startsWith('break-start')) {
        if (current.phase !== 'break') return // the break is already over
        fireAlert({ key, title: `Break — ${boundary.minutes} minutes`, body: 'Stand up and look away from the screen.', tone: 'break' })
    } else if (boundary.event.startsWith('break-end')) {
        if (current.used - boundary.at > LATE_ALERT_LIMIT_S) return
        fireAlert({ key, title: 'Break over', body: `Back to: ${task}`, tone: 'break' })
    } else {
        sync() // the server settles the run and returns it as `recent`
    }
}

/** One timeout per upcoming boundary (not a chained ticker: less throttled in hidden tabs). */
const scheduleBoundaries = () => {
    boundaryTimers.forEach(clearTimeout)
    boundaryTimers = []
    const timer = store.timer
    if (!timer || timer.state !== 'running') return

    const sinceSync = Math.max(0, (store.serverNow() - store.syncedAt) / 1000)
    for (const boundary of upcomingBoundaries(timer.used_seconds, timer.planned_seconds, timer.break_every_minutes, timer.break_minutes)) {
        const delayMs = (boundary.inSeconds - sinceSync) * 1000
        if (delayMs < -2000 && boundary.event !== 'finished') continue
        boundaryTimers.push(setTimeout(() => onBoundary(boundary, timer), Math.max(0, delayMs) + 250))
    }
}

watch(() => [store.timer, store.syncedAt], () => {
    if (store.timer) startTicker()
    else stopTicker()
    scheduleBoundaries()
}, { immediate: true })

// ── Tab title ──────────────────────────────────────────────────────

let baseTitle = null
watch(() => (store.timer && live.value ? [store.timer.state, live.value.left, store.timer.block?.slot] : null), (value) => {
    if (!value) {
        if (baseTitle !== null) document.title = baseTitle
        baseTitle = null
        return
    }
    baseTitle ??= document.title
    const [state, left, slot] = value
    document.title = `${state === 'running' ? '▶' : '⏸'} ${formatClock(left)} · ${slotLabels[slot] || 'Block'}`
}, { immediate: true })

// ── Panel actions ──────────────────────────────────────────────────

const handleAction = async (name, arg) => {
    if (name === 'popout') return popOut()
    if (name === 'open-topic') {
        panelOpen.value = false
        return router.push({ name: 'TopicDetail', params: { id: arg.id } })
    }
    if (name === 'stop' && !(await showConfirm('Stop this block now? It is marked partial unless its time is almost up.', 'Stop the timer'))) return
    if (name === 'discard' && !(await showConfirm('Discard this run? The block keeps its status and recorded time.', 'Discard the run'))) return

    busy.value = true
    try {
        if (name === 'pause') await pause()
        else if (name === 'resume') await resume()
        else if (name === 'stop' || name === 'stop-confirmed') await stop()
        else if (name === 'discard') await discard()
    } catch (err) {
        await showError(err.response?.data?.message || err.response?.data?.msg || 'The timer could not be updated.')
    } finally {
        busy.value = false
    }
}

// ── Pop-out (Document Picture-in-Picture) ──────────────────────────

const canPopOut = typeof window !== 'undefined' && 'documentPictureInPicture' in window
let pipWindow = null

const copyStyles = (target) => {
    for (const sheet of document.styleSheets) {
        try {
            const style = target.createElement('style')
            style.textContent = [...sheet.cssRules].map((rule) => rule.cssText).join('\n')
            target.head.appendChild(style)
        } catch {
            if (!sheet.href) continue
            const link = target.createElement('link')
            link.rel = 'stylesheet'
            link.href = sheet.href
            target.head.appendChild(link)
        }
    }
}

const popOut = async () => {
    if (pipWindow) return pipWindow.focus()
    try {
        const win = await window.documentPictureInPicture.requestWindow({ width: 320, height: 240 })
        copyStyles(win.document)
        win.document.title = 'StudyTracker timer'
        const el = win.document.createElement('div')
        win.document.body.appendChild(el)

        const app = createApp(PopOutTimer, { win, today, onAction: (name) => handleAction(name) })
        app.use(getActivePinia())
        app.mount(el)
        pipWindow = win
        panelOpen.value = false

        win.addEventListener('pagehide', () => {
            app.unmount()
            pipWindow = null
        })
    } catch {
        await showError('The timer window could not be opened.')
    }
}

watch(() => store.timer, (timer) => {
    if (!timer) pipWindow?.close()
})

// ── Lifecycle ──────────────────────────────────────────────────────

const onVisible = () => {
    if (document.visibilityState === 'visible') sync()
}
const unlockOnce = () => unlockAudio()
let offOtherTab = () => {}

onMounted(() => {
    // After a reload the Start click's audio unlock is gone; the next tap restores it.
    window.addEventListener('pointerdown', unlockOnce, { once: true })
    document.addEventListener('visibilitychange', onVisible)
    offOtherTab = onOtherTabChange(() => sync())
    sync(false)
})

onBeforeUnmount(() => {
    stopTicker()
    boundaryTimers.forEach(clearTimeout)
    window.removeEventListener('pointerdown', unlockOnce)
    document.removeEventListener('visibilitychange', onVisible)
    offOtherTab()
    pipWindow?.close()
})
</script>
