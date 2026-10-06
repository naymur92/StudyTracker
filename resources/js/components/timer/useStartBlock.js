import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useBlockTimerStore } from '@/stores/blockTimer'
import { showError } from '@/helpers/alerts'
import { useTimerActions } from './useTimerActions'

/**
 * Start buttons for weekly blocks: which blocks can start, and starting one
 * directly or through StartBlockDialog when it lacks a topic or minutes.
 */
export function useStartBlock(today) {
    const store = useBlockTimerStore()
    const authStore = useAuthStore()
    const { start } = useTimerActions()
    const dialogBlock = ref(null)

    const isDemo = computed(() => authStore.isDemoUser)

    /** Today's planned or partial block with time left, and no timer of its own. */
    const canStart = (block) => block.block_date === today
        && ['planned', 'partial'].includes(block.status)
        && !block.timer
        && store.activeBlockId !== block.id
        && (!block.planned_minutes || (block.actual_minutes || 0) < block.planned_minutes)

    /** Why Start is disabled, or '' when it can be pressed. */
    const startBlockedReason = () => {
        if (isDemo.value) return 'Not available in the demo'
        if (store.isActive) return 'Another block is running — stop it first'
        return ''
    }

    const startBlock = async (block) => {
        if ((block.lane !== 'review' && !block.topic) || !block.planned_minutes) {
            dialogBlock.value = block
            return false
        }
        try {
            await start(block)
            return true
        } catch (err) {
            const errors = err.response?.data?.errors
            await showError((errors && Object.values(errors).flat()[0]) || err.response?.data?.msg || 'The timer could not start.')
            return false
        }
    }

    return { store, dialogBlock, canStart, startBlockedReason, startBlock }
}
