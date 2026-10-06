/**
 * Feature overview shown on the public Features page and the home/about summary.
 * `techniques` link each feature to entries in learningScience.js.
 */
export const features = [
    {
        id: 'adaptive-reviews',
        title: 'Reviews that adapt to you',
        desc: 'Grade each review Again, Hard, Good or Easy. Forgotten topics come back tomorrow; well-known ones are pushed further out. Later reviews are re-planned from the day you actually reviewed.',
        techniques: ['spaced-repetition', 'successive-relearning'],
    },
    {
        id: 'review-page',
        title: 'Question-first review',
        desc: 'A focused review page shows your recall questions first, hides the answers until you reveal them, and shows when each grade would bring the topic back.',
        techniques: ['retrieval-practice', 'feedback', 'desirable-difficulties'],
    },
    {
        id: 'recall-cards',
        title: 'Recall cards',
        desc: 'Every topic holds up to 10 recall questions, a summary that acts as your answer key, a practice prompt and a lane (Major, Minor or Work).',
        techniques: ['retrieval-practice', 'single-track-focus'],
    },
    {
        id: 'review-load',
        title: 'A daily review budget',
        desc: '“Due today: N topics ≈ M min”, estimated from your own timings, with a warning above your budget and soft warnings for review debt and missed days.',
        techniques: ['realistic-planning', 'consistency'],
    },
    {
        id: 'mixed-queue',
        title: 'Mixed review sessions',
        desc: 'Overdue reviews first, oldest first; then today’s reviews mixed across your categories, split at your time budget.',
        techniques: ['interleaving'],
    },
    {
        id: 'schedules',
        title: 'Schedules per category',
        desc: 'Short gaps for an exam weeks away, growing gaps for long-term goals, weekly reviews until an exam date — set per category, from presets or your own intervals.',
        techniques: ['spaced-repetition'],
    },
    {
        id: 'weekly-plan',
        title: 'Weekly plan with gears',
        desc: 'Pick a Green, Yellow or Red gear for the week, get blocks shaped for a job holder (office and off days) or a student (class and free days), pre-decide each task, run a block with a timer that alerts at breaks and records done or partial by itself, and score the week against an 80% success line.',
        techniques: ['implementation-intentions', 'realistic-planning', 'consistency'],
    },
    {
        id: 'mistakes',
        title: 'Mistakes notebook',
        desc: 'Log every wrong answer with its cause. It is reviewed at +1, +3 and +7 days, then merged into the topic it belongs to.',
        techniques: ['learning-from-errors', 'feedback'],
    },
    {
        id: 'agenda-calendar',
        title: 'Daily agenda and calendar',
        desc: 'See what to learn, review and practise each day, and every planned review on a monthly calendar.',
        techniques: ['spaced-repetition'],
    },
    {
        id: 'practice-reports',
        title: 'Practice logs and reports',
        desc: 'Log practice sessions with type, duration and outcome, and download or email monthly reports that include your recall grades.',
        techniques: ['desirable-difficulties'],
    },
]
