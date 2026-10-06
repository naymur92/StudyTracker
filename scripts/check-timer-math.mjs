#!/usr/bin/env node
/**
 * Shared vectors for the block timer arithmetic. The same cases are asserted
 * on the PHP side in tests/Unit/BlockTimerMathTest.php, so the live display
 * and the server agree on phases and breaks.
 *
 * Runs as `npm run check:timer` and automatically before `npm run build`.
 */
import assert from 'node:assert/strict'
import { phases, phaseAt, nextBreakAt, liveTimer, upcomingBoundaries, formatClock } from '../resources/js/components/timer/timerMath.js'

const inMinutes = (planned, every, brk) => phases(planned * 60, every, brk).map((p) => [p.phase, p.start / 60, p.end / 60])
const cases = []
const check = (name, fn) => cases.push([name, fn])

check('150 min with 50/10 has five phases', () => assert.deepEqual(inMinutes(150, 50, 10), [
    ['work', 0, 50], ['break', 50, 60], ['work', 60, 110], ['break', 110, 120], ['work', 120, 150],
]))
check('120 min with 50/10 drops the break that reaches the end', () => assert.deepEqual(inMinutes(120, 50, 10), [
    ['work', 0, 50], ['break', 50, 60], ['work', 60, 120],
]))
check('20 min with 25/5 has no break', () => assert.deepEqual(inMinutes(20, 25, 5), [['work', 0, 20]]))
check('no pattern is work until the end', () => {
    assert.deepEqual(inMinutes(90, null, null), [['work', 0, 90]])
    assert.deepEqual(phaseAt(4800, 5400, null, null), { phase: 'work', left: 600 })
    assert.equal(nextBreakAt(0, 5400, null, null), null)
})
check('52 minutes into a 90-minute 50/10 block is a break with 480 s left', () => {
    assert.deepEqual(phaseAt(52 * 60, 90 * 60, 50, 10), { phase: 'break', left: 480 })
    assert.deepEqual(phaseAt(0, 90 * 60, 50, 10), { phase: 'work', left: 3000 })
    assert.deepEqual(phaseAt(60 * 60, 90 * 60, 50, 10), { phase: 'work', left: 1800 })
    assert.deepEqual(phaseAt(90 * 60, 90 * 60, 50, 10), { phase: 'work', left: 0 })
})
check('next break', () => {
    assert.equal(nextBreakAt(0, 9000, 50, 10), 3000)
    assert.equal(nextBreakAt(3100, 9000, 50, 10), 6600)
    assert.equal(nextBreakAt(6600, 9000, 50, 10), null)
})
check('live timer advances only while running', () => {
    const timer = { state: 'running', planned_seconds: 5400, used_seconds: 3000, break_every_minutes: 50, break_minutes: 10 }
    const live = liveTimer(timer, 1_000_000, 1_000_000 + 120_000)
    assert.equal(live.used, 3120)
    assert.equal(live.left, 2280)
    assert.equal(live.phase, 'break')
    assert.equal(live.phaseLeft, 480)
    assert.equal(liveTimer({ ...timer, state: 'paused' }, 0, 999_999).used, 3000)
    assert.equal(liveTimer(timer, 0, 10_000_000).left, 0, 'never past the planned time')
})
check('upcoming boundaries of a 12-minute 10/1 block', () => {
    assert.deepEqual(upcomingBoundaries(0, 720, 10, 1).map((b) => [b.event, b.inSeconds]), [
        ['break-start-1', 600], ['break-end-1', 660], ['finished', 720],
    ])
    assert.deepEqual(upcomingBoundaries(630, 720, 10, 1).map((b) => b.event), ['break-end-1', 'finished'])
})
check('clock format', () => {
    assert.equal(formatClock(2480), '41:20')
    assert.equal(formatClock(3909), '1:05:09')
    assert.equal(formatClock(-3), '0:00')
})

let failed = 0
for (const [name, fn] of cases) {
    try {
        fn()
    } catch (e) {
        failed += 1
        console.error(`✗ ${name}\n  ${e.message.split('\n').join('\n  ')}`)
    }
}

if (failed) {
    console.error(`\nTimer math check failed: ${failed} of ${cases.length} cases.`)
    process.exit(1)
}
console.log(`Timer math check passed (${cases.length} cases).`)
