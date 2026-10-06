import { reactive } from 'vue'

// Timer alerts: an in-app toast, a short chime and (when allowed) a browser
// notification. Each alert key fires once across tabs and reloads: the check
// and the mark happen under a Web Lock against a shared localStorage list.

const FIRED_KEY = 'studyTimer.firedAlerts'
const MAX_FIRED = 40
const HIDDEN_TAB_DELAY_MS = 1500 // let a visible tab claim the alert first

/** Toasts rendered by BlockTimerHost: { id, title, body, tone, actions, countdown, countdownLabel }. */
export const toasts = reactive([])

let audioContext = null
let toastSeq = 0

/** Create or resume the AudioContext; call inside a user gesture (Start click). */
export function unlockAudio() {
    try {
        const Ctx = window.AudioContext || window.webkitAudioContext
        if (!Ctx) return
        audioContext ??= new Ctx()
        if (audioContext.state === 'suspended') audioContext.resume()
    } catch {
        audioContext = null
    }
}

/** Ask for notification permission once; call inside a user gesture. */
export function requestNotificationPermission() {
    if (typeof Notification === 'undefined' || Notification.permission !== 'default') return
    try {
        Notification.requestPermission()
    } catch {
        // Older Safari: callback form only; alerts still work in-app.
    }
}

/** A short two- or three-note chime; no audio asset needed. */
export function chime(tone = 'info') {
    if (!audioContext || audioContext.state !== 'running') return
    const notes = tone === 'finish' ? [660, 880, 1320] : tone === 'break' ? [880, 660] : [660, 880]
    const start = audioContext.currentTime
    notes.forEach((freq, i) => {
        const osc = audioContext.createOscillator()
        const gain = audioContext.createGain()
        const t = start + i * 0.22
        osc.type = 'sine'
        osc.frequency.value = freq
        gain.gain.setValueAtTime(0.0001, t)
        gain.gain.exponentialRampToValueAtTime(0.25, t + 0.02)
        gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.4)
        osc.connect(gain).connect(audioContext.destination)
        osc.start(t)
        osc.stop(t + 0.45)
    })
}

function readFired() {
    try {
        return JSON.parse(localStorage.getItem(FIRED_KEY) || '[]')
    } catch {
        return []
    }
}

function markFired(key) {
    try {
        localStorage.setItem(FIRED_KEY, JSON.stringify([...readFired().filter((k) => k !== key), key].slice(-MAX_FIRED)))
    } catch {
        // Storage blocked: the alert may repeat after a reload, nothing worse.
    }
}

export const hasFired = (key) => readFired().includes(key)

/** Run `claim` once for `key` across tabs; resolves true in the tab that ran it. */
async function once(key, claim) {
    if (typeof document !== 'undefined' && document.hidden) {
        await new Promise((resolve) => setTimeout(resolve, HIDDEN_TAB_DELAY_MS))
    }

    const run = () => {
        if (hasFired(key)) return false
        markFired(key)
        claim()
        return true
    }

    if (navigator.locks?.request) {
        return navigator.locks.request('study-timer-alert', { ifAvailable: true }, (lock) => (lock ? run() : false))
    }
    return run()
}

const countdowns = new Map()

/**
 * Show a toast. `timeout` (ms) auto-dismisses it; `countdown` (seconds) with
 * `onExpire` runs onExpire when it reaches zero unless the toast is dismissed
 * first (any action button dismisses it).
 */
export function pushToast(toast) {
    const id = ++toastSeq
    toasts.push({ id, tone: 'info', actions: [], ...toast })
    if (toast.timeout) setTimeout(() => dismissToast(id), toast.timeout)

    if (toast.countdown) {
        countdowns.set(id, setInterval(() => {
            const live = toasts.find((t) => t.id === id)
            if (!live) return dismissToast(id)
            live.countdown -= 1
            if (live.countdown <= 0) {
                dismissToast(id)
                toast.onExpire?.()
            }
        }, 1000))
    }
    return id
}

export function dismissToast(id) {
    clearInterval(countdowns.get(id))
    countdowns.delete(id)
    const i = toasts.findIndex((t) => t.id === id)
    if (i !== -1) toasts.splice(i, 1)
}

/** Run a toast action, then dismiss the toast (cancelling any countdown). */
export function runToastAction(toast, action) {
    dismissToast(toast.id)
    action.onClick?.()
}

/**
 * Fire an alert once across tabs. Resolves true in the tab that showed it.
 * `toast` is shown in that tab; `onNotificationClick` focuses the app.
 */
export function fireAlert({ key, title, body = '', tone = 'info', toast = {}, onNotificationClick = null }) {
    return once(key, () => {
        chime(tone)
        pushToast({ title, body, tone, timeout: toast.actions?.length ? null : 8000, ...toast })

        if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            try {
                const n = new Notification(title, { body, tag: key, icon: '/icon_192x192.png' })
                n.onclick = () => {
                    window.focus()
                    onNotificationClick?.()
                    n.close()
                }
            } catch {
                // Some mobile browsers only allow notifications from a service worker.
            }
        }
    })
}
