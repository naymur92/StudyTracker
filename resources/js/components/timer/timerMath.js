// Pure timer arithmetic for weekly-plan blocks, in seconds of block time used
// (pauses excluded). Mirrors app/Services/StudyTracker/Scheduling/BlockTimerMath.php
// — keep both in step; scripts/check-timer-math.mjs runs the shared vectors.
//
// Break pattern: `every` minutes of work, then `brk` minutes of break, repeated.
// Breaks are inside the block's planned time, and a break that would end at or
// after the block's end is not taken.

/** The block's phases in order: [{ phase: 'work' | 'break', start, end }]. */
export function phases(plannedSeconds, everyMinutes, breakMinutes) {
    if (plannedSeconds <= 0) return []
    if (!everyMinutes || !breakMinutes) return [{ phase: 'work', start: 0, end: plannedSeconds }]

    const every = everyMinutes * 60
    const brk = breakMinutes * 60
    const list = []
    let t = 0

    while (t < plannedSeconds) {
        const workEnd = Math.min(t + every, plannedSeconds)
        const breakEnd = workEnd + brk

        if (workEnd >= plannedSeconds || breakEnd >= plannedSeconds) {
            // No room for a break that ends before the block does: work to the end.
            list.push({ phase: 'work', start: t, end: plannedSeconds })
            break
        }

        list.push({ phase: 'work', start: t, end: workEnd })
        list.push({ phase: 'break', start: workEnd, end: breakEnd })
        t = breakEnd
    }

    return list
}

/** The phase at `usedSeconds` and the seconds left in it ({ phase: 'work', left: 0 } past the end). */
export function phaseAt(usedSeconds, plannedSeconds, everyMinutes, breakMinutes) {
    for (const p of phases(plannedSeconds, everyMinutes, breakMinutes)) {
        if (usedSeconds >= p.start && usedSeconds < p.end) return { phase: p.phase, left: p.end - usedSeconds }
    }
    return { phase: 'work', left: 0 }
}

/** Start (in seconds used) of the first break after `usedSeconds`, or null. */
export function nextBreakAt(usedSeconds, plannedSeconds, everyMinutes, breakMinutes) {
    const next = phases(plannedSeconds, everyMinutes, breakMinutes).find((p) => p.phase === 'break' && p.start > usedSeconds)
    return next ? next.start : null
}

/**
 * Live view of a timer object from the API: the server's `used_seconds` as of
 * `syncedAtMs` (server clock), advanced to `nowMs` (server clock) while running.
 */
export function liveTimer(timer, syncedAtMs, nowMs) {
    if (!timer) return null
    const planned = timer.planned_seconds
    const elapsed = timer.state === 'running' ? Math.max(0, Math.floor((nowMs - syncedAtMs) / 1000)) : 0
    const used = Math.min(planned, timer.used_seconds + elapsed)
    const phase = phaseAt(used, planned, timer.break_every_minutes, timer.break_minutes)

    return {
        used,
        left: Math.max(0, planned - used),
        phase: phase.phase,
        phaseLeft: phase.left,
        nextBreakAt: nextBreakAt(used, planned, timer.break_every_minutes, timer.break_minutes),
        progress: planned > 0 ? used / planned : 0,
    }
}

/**
 * Upcoming boundaries after `usedSeconds`: break starts, break ends and the
 * run-out, each with the seconds of block time until it happens.
 * [{ event: 'break-start-N' | 'break-end-N' | 'finished', at, inSeconds, minutes? }]
 */
export function upcomingBoundaries(usedSeconds, plannedSeconds, everyMinutes, breakMinutes) {
    const list = []
    let n = 0
    for (const p of phases(plannedSeconds, everyMinutes, breakMinutes)) {
        if (p.phase !== 'break') continue
        n += 1
        if (p.start > usedSeconds) list.push({ event: `break-start-${n}`, at: p.start, minutes: breakMinutes })
        if (p.end > usedSeconds) list.push({ event: `break-end-${n}`, at: p.end })
    }
    if (plannedSeconds > usedSeconds) list.push({ event: 'finished', at: plannedSeconds })

    return list.map((b) => ({ ...b, inSeconds: b.at - usedSeconds }))
}

/** "41:20" or "1:05:09". */
export function formatClock(seconds) {
    const s = Math.max(0, Math.round(seconds))
    const h = Math.floor(s / 3600)
    const m = Math.floor((s % 3600) / 60)
    const sec = String(s % 60).padStart(2, '0')
    return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${sec}` : `${m}:${sec}`
}
