import { reactive, ref } from 'vue'

// Timer alerts: an in-app toast, a short chime and (when allowed) a browser
// notification. Each alert key fires once across tabs and reloads: the check
// and the mark happen under a Web Lock against a shared localStorage list.

const FIRED_KEY = 'studyTimer.firedAlerts'
const MAX_FIRED = 40
// Each alert plays in one tab. Tabs that can play sound claim it first, then
// visible tabs: a tab waits NO_AUDIO_DELAY_MS without sound and
// HIDDEN_TAB_DELAY_MS while hidden.
const NO_AUDIO_DELAY_MS = 1000
const HIDDEN_TAB_DELAY_MS = 500

/** Toasts rendered by BlockTimerHost: { id, title, body, tone, actions, countdown, countdownLabel }. */
export const toasts = reactive([])

let audioContext = null
let toastSeq = 0

/** Whether alert sounds can play now; MiniTimer shows 🔇 when false. */
export const audioOn = ref(false)

function getAudioContext() {
    if (audioContext) return audioContext
    const Ctx = window.AudioContext || window.webkitAudioContext
    if (!Ctx) return null
    try {
        audioContext = new Ctx()
        audioContext.onstatechange = () => (audioOn.value = audioContext.state === 'running')
        audioOn.value = audioContext.state === 'running'
    } catch {
        audioContext = null
    }
    return audioContext
}

/** Create or resume the AudioContext; call inside a user gesture. */
export function unlockAudio() {
    const ctx = getAudioContext()
    if (ctx && ctx.state !== 'running') ctx.resume().catch(() => {})
}

/**
 * Browsers start sound only from a user gesture, and may suspend it again
 * later (reloads, Safari interruptions, audio device changes). Re-unlock on
 * every tap or key press in `target`; returns a cleanup function.
 */
export function keepAudioUnlocked(target = window) {
    const events = ['pointerdown', 'keydown', 'touchend']
    events.forEach((e) => target.addEventListener(e, unlockAudio, { capture: true, passive: true }))
    return () => events.forEach((e) => target.removeEventListener(e, unlockAudio, { capture: true }))
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

const CHIMES = {
    break: { notes: [880, 660], repeat: 3 },
    finish: { notes: [660, 880, 1320], repeat: 3 },
    info: { notes: [660, 880], repeat: 1 },
}
const NOTE_STEP_S = 0.22
const NOTE_LENGTH_S = 0.4
const GROUP_GAP_S = 0.45

/**
 * Play the alert chime (no audio asset needed). Tries to resume a suspended
 * context first — allowed once the page has had a user gesture. Resolves
 * false when sound could not play.
 */
export async function chime(tone = 'info') {
    const ctx = getAudioContext()
    if (!ctx) return false
    if (ctx.state !== 'running') {
        // resume() never settles without a past gesture, so don't wait on it.
        await Promise.race([ctx.resume().catch(() => {}), new Promise((r) => setTimeout(r, 300))])
    }
    if (ctx.state !== 'running') return false

    const { notes, repeat } = CHIMES[tone] || CHIMES.info
    const groupLength = notes.length * NOTE_STEP_S + GROUP_GAP_S
    const start = ctx.currentTime + 0.05
    for (let g = 0; g < repeat; g++) {
        notes.forEach((freq, i) => {
            const osc = ctx.createOscillator()
            const gain = ctx.createGain()
            const t = start + g * groupLength + i * NOTE_STEP_S
            osc.type = 'triangle'
            osc.frequency.value = freq
            gain.gain.setValueAtTime(0.0001, t)
            gain.gain.exponentialRampToValueAtTime(0.5, t + 0.02)
            gain.gain.exponentialRampToValueAtTime(0.0001, t + NOTE_LENGTH_S)
            osc.connect(gain).connect(ctx.destination)
            osc.start(t)
            osc.stop(t + NOTE_LENGTH_S + 0.05)
        })
    }
    return true
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
    const delay = (audioOn.value ? 0 : NO_AUDIO_DELAY_MS) + (typeof document !== 'undefined' && document.hidden ? HIDDEN_TAB_DELAY_MS : 0)
    if (delay) await new Promise((resolve) => setTimeout(resolve, delay))

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
