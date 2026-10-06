<template>
    <div class="space-y-3" v-if="load">
        <div v-if="load.review_debt_active" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 text-sm">
            <p class="font-semibold">Review debt</p>
            <p>
                Reviews have exceeded {{ load.debt_threshold_minutes }} minutes for three days. Add no new topics until
                you are back under {{ load.budget_minutes }} minutes — clear the oldest reviews first.
            </p>
        </div>
        <div v-if="showCap && load.new_topics_this_week >= load.weekly_new_topic_cap"
            class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 text-sm">
            <p class="font-semibold">Weekly new-topic cap reached</p>
            <p>
                You have added {{ load.new_topics_this_week }} topics this week (cap {{ load.weekly_new_topic_cap }}).
                Each new topic adds future reviews — you can still continue.
            </p>
        </div>
        <div v-if="showNeverMissTwice && load.never_miss_twice"
            class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-900 text-sm">
            <p class="font-semibold">Never miss twice</p>
            <p>
                No reviews were done yesterday. Start today with a minimum day: 15–20 minutes of your oldest due
                reviews.
            </p>
            <slot name="never-miss-twice-action" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    load: { type: Object, default: null },
    showCap: { type: Boolean, default: false },
    showNeverMissTwice: { type: Boolean, default: false },
})
</script>
