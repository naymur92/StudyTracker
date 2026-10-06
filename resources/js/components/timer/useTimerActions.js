import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useBlockTimerStore } from '@/stores/blockTimer'
import { slotLabels } from '@/components/weekly/weeklyMeta'
import { fireAlert, pushToast, requestNotificationPermission, unlockAudio } from './timerAlerts'

const FINISH_COUNTDOWN_SECONDS = 5

/** Where a finished block leads: its topic's wrap-up, or the Review page. */
export const finishTarget = (block) => (block?.topic && block.lane !== 'review'
    ? { name: 'TopicDetail', params: { id: block.topic.id }, query: { wrapup: block.id } }
    : { name: 'Review' })

export const blockName = (block) => slotLabels[block?.slot] || 'Block'

/**
 * The "block finished" alert for an ended run (`recent` from the API).
 * `navigate`: open the wrap-up after a 5-second "Stay here" countdown;
 * otherwise only offer the link (finished while away).
 */
export function finishFlow(store, router, run, { navigate }) {
    const block = run.block
    const target = finishTarget(block)
    const isWrapup = target.name === 'TopicDetail'
    if (isWrapup) store.setWrapup(run)

    const linkLabel = isWrapup ? 'Log what you learned' : 'Open Review'
    const go = () => router.push(target)
    const body = `${run.minutes} min${block.topic ? ` on ${block.topic.title}` : ''}${block.planned_task ? ` — ${block.planned_task}` : ''}`

    return fireAlert({
        key: `${run.session_id}:finished`,
        title: navigate ? `${blockName(block)} finished` : `Block finished: ${blockName(block)}`,
        body,
        tone: 'finish',
        onNotificationClick: go,
        toast: navigate
            ? {
                countdown: FINISH_COUNTDOWN_SECONDS,
                countdownLabel: isWrapup ? 'Opening the wrap-up' : 'Opening Review',
                onExpire: go,
                actions: [
                    {
                        label: 'Stay here',
                        onClick: () => pushToast({ title: `${blockName(block)} finished`, body, tone: 'finish', actions: [{ label: linkLabel, onClick: go }] }),
                    },
                ],
            }
            : { actions: [{ label: linkLabel, onClick: go }] },
    })
}

/** Timer actions with their user-facing side effects, for pages and the panel. */
export function useTimerActions() {
    const store = useBlockTimerStore()
    const authStore = useAuthStore()
    const router = useRouter()
    const api = () => authStore.getApiClient()

    const start = async (block, extras = {}) => {
        // Inside the Start click: unlock sound and ask for notifications once.
        unlockAudio()
        requestNotificationPermission()
        const result = await store.start(api(), block.id, extras)
        store.shownSessionId = store.timer?.session_id ?? null
        return result
    }

    const pause = () => store.pause(api())
    const resume = () => store.resume(api())

    const stop = async () => {
        const result = await store.stop(api())
        store.shownSessionId = null
        const { outcome, recent } = result.data

        if (outcome === 'done' && recent) {
            await finishFlow(store, router, recent, { navigate: true })
        } else if (outcome === 'partial' && recent) {
            const target = finishTarget(recent.block)
            if (target.name === 'TopicDetail') store.setWrapup(recent)
            pushToast({
                title: `${blockName(recent.block)} marked partial`,
                body: `${recent.block.actual_minutes} of ${recent.block.planned_minutes} min recorded.`,
                actions: target.name === 'TopicDetail' ? [{ label: 'Log what you learned', onClick: () => router.push(target) }] : [],
                timeout: 12000,
            })
        } else {
            pushToast({ title: 'Run discarded', body: 'It was shorter than a minute, so the block keeps its status.', timeout: 6000 })
        }
        return result
    }

    const discard = async () => {
        const result = await store.discard(api())
        store.shownSessionId = null
        pushToast({ title: 'Run discarded', body: 'The block keeps its status and recorded time.', timeout: 6000 })
        return result
    }

    return { store, start, pause, resume, stop, discard }
}
