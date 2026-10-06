<template>
    <div class="space-y-6 pt-6 border-t border-gray-200">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Recall card</h3>
            <p class="text-sm text-gray-600">
                Turns every review into a self-test. See the
                <router-link to="/app/guide#topics" class="text-primary-600 hover:underline">User Guide</router-link>.
            </p>
        </div>

        <RecallQuestionsEditor :model-value="form.recall_questions"
            @update:model-value="form.recall_questions = $event" :errors="errors" />
        <p v-if="errors.recall_questions" class="text-xs text-red-600">{{ errors.recall_questions[0] }}</p>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Summary (answer key)</label>
            <textarea v-model="form.summary" maxlength="2000" rows="5" class="input-base"
                placeholder="A five-line summary in your own words."></textarea>
            <p v-if="errors.summary" class="text-xs text-red-600 mt-1">{{ errors.summary[0] }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Practice prompt</label>
                <input v-model="form.practice_prompt" type="text" maxlength="500" class="input-base"
                    placeholder="An exercise, re-implementation or diagram to redraw" />
                <p v-if="errors.practice_prompt" class="text-xs text-red-600 mt-1">{{ errors.practice_prompt[0] }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lane</label>
                <select v-model="form.lane" class="input-base">
                    <option :value="null">No lane</option>
                    <option value="major">Major — deep blocks</option>
                    <option value="minor">Minor — light evening slot</option>
                    <option value="work">Work — learned on the job</option>
                </select>
                <p v-if="errors.lane" class="text-xs text-red-600 mt-1">{{ errors.lane[0] }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import RecallQuestionsEditor from '@/components/topics/RecallQuestionsEditor.vue'

defineProps({
    form: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
})
</script>
