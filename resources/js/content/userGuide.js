/**
 * In-app User Guide content (/app/guide).
 *
 * One section per navigation item (`id` = the item's `guideSection` in
 * resources/js/config/navigation.js), plus Getting started and the routine.
 * `techniques` / `algorithms` point at entries in learningScience.js and are
 * shown with their benefits and references. Tips may cite references too.
 *
 * When a menu changes, update its section here. `npm run check:content`
 * verifies that every navigation item has a section.
 */

export const guideSections = [
    {
        id: 'getting-started',
        title: 'Getting started',
        route: null,
        purpose:
            'StudyTracker turns what you study into a schedule of short self-tests. Set it up once, then spend most days on the Review page.',
        steps: [
            'Open Study Settings and set your daily review budget (25 minutes is a good start), your week start day and your off days.',
            'Create a few categories for your tracks (for example IELTS, Maths, ML, Backend).',
            'Add a topic only after you have actually learned it with recall. Give it a one-session title (“pandas: groupby / agg / transform”, not “pandas”).',
            'Write 3–5 recall questions and a five-line summary in your own words — the summary is your answer key.',
            'Each day, open Review, answer from memory, reveal, and grade yourself honestly.',
            'Watch the due-today banner: if it says you are over budget, do the oldest reviews and let the rest carry over.',
        ],
        tips: [
            { text: 'Recall before you re-read. A review that only re-reads notes gives a feeling of knowing without the memory benefit.', refs: ['roediger2006'] },
            { text: 'Keep new topics to about one per deep study block — every topic you add creates future reviews.' },
        ],
        techniques: ['retrieval-practice', 'spaced-repetition'],
        algorithms: [],
    },
    {
        id: 'routine',
        title: 'Daily and weekly routine',
        route: null,
        purpose: 'A rhythm that fits around a full-time job: one deep block, a short review, and a weekly look back.',
        steps: [
            'Mornings: one deep block on your Major track. Decide the exact task the night before.',
            'Evenings: about 20 minutes of reviews on the Review page, mixed across all tracks, then a short Minor-track slot.',
            'Before you stop, write tomorrow’s first task so starting takes no decision.',
            'Once a week: look at what got in the way and write one if-then plan for it (“If the office runs late, then I do 10 minutes of due reviews only”).',
            'Bad day? Do a minimum day — 15–20 minutes of reviews. Never miss twice.',
        ],
        tips: [
            { text: 'Underestimating how long things take is normal. Plan fewer, bigger blocks with slack, not a full day.', refs: ['buehler1994'] },
            { text: 'Keep one track per block. Switching leaves attention behind on the previous task; a one-line “where I stopped” note helps you resume.', refs: ['leroy2009', 'leroyglomb2018'] },
            { text: 'A single missed day does not break a habit — repeated misses do.', refs: ['lally2010'] },
        ],
        techniques: ['realistic-planning', 'consistency', 'single-track-focus'],
        algorithms: [],
    },
    {
        id: 'dashboard',
        title: 'Dashboard',
        route: '/app',
        purpose: 'Your starting point: today’s review load, warnings, and the day’s agenda at a glance.',
        steps: [
            'Read the due-today banner: “Due today: N topics ≈ M min”. Select Start review to open the Review page.',
            'If a warning appears — review debt, the weekly new-topic cap, or never miss twice — follow its advice before adding new material.',
            'Use the arrows or Today to move between days and see each day’s agenda and stats.',
            'Tick a task to complete it. Revisions ask for a recall grade first (see Review).',
            'The “This week” card shows your gear, the score so far against your success line, today’s blocks with quick Done/Partial/Missed buttons, and “Next up” — the task you decided in advance, or your oldest due review if you did not.',
        ],
        tips: [
            { text: 'Over budget? Do the oldest reviews first and stop at your budget — the rest carry over without penalty.' },
        ],
        techniques: ['consistency', 'realistic-planning', 'implementation-intentions'],
        algorithms: ['review-estimate', 'review-debt', 'never-miss-twice', 'weekly-score'],
    },
    {
        id: 'daily-tasks',
        title: 'Daily Tasks',
        route: '/app/tasks',
        purpose: 'Every task for a day, grouped as Learn → Revisions → Practice → Overdue, with skip and reschedule actions.',
        steps: [
            'Pick a day with the arrows. Learn tasks are new topics planned for that day.',
            'Tick a learn task when you have studied it. If you learn it later than planned, its reviews move to start from that day.',
            'Tick a revision and choose Again, Hard, Good or Easy — the grade decides the next date.',
            'Use Skip for a review you deliberately drop, or Reschedule to move a pending task.',
        ],
        tips: [
            { text: 'Prefer the Review page for revisions: it shows the questions first and times you automatically.' },
            { text: 'A pending review you rescheduled by hand may be moved again the next time you grade that topic.' },
        ],
        techniques: ['spaced-repetition', 'successive-relearning'],
        algorithms: ['adaptive-scheduler', 'relearn-check', 'review-estimate'],
    },
    {
        id: 'review',
        title: 'Review',
        route: '/app/review',
        purpose: 'A question-first self-test session for today’s due topics — the heart of StudyTracker.',
        steps: [
            'Read the topic title and its recall questions. Answer each from memory — out loud, on paper or in code.',
            'No questions on a topic? Do a blank-page recall: write everything you remember, then compare.',
            'Press Reveal (or Space) to see the answers and the summary.',
            'Grade yourself (keys 1–4). Each button shows when the topic will come back.',
            'Again — you could not recall it: relearn now, check tomorrow, steps restart.',
            'Hard — shaky: relearn now and check tomorrow, progress kept.',
            'Good — recalled with effort: normal next interval.',
            'Easy — effortless: skip one step.',
            'When the session passes your budget you can stop; the remaining reviews stay due.',
            'Got something wrong? After Again or Hard, select “Log a mistake” to add it to your Mistakes notebook.',
        ],
        tips: [
            { text: 'Grade honestly. A hard review that feels bad is often the one that teaches you most.', refs: ['bjork1994', 'soderstrom2015'] },
            { text: 'After Again or Hard, spend about five minutes re-studying the answer key — the relearn check tomorrow confirms it stuck.', refs: ['rawson2013'] },
            { text: 'Reviewing in the evening and checking again the next morning, with sleep in between, made relearning faster and memory longer-lasting.', refs: ['mazza2016'] },
        ],
        techniques: ['retrieval-practice', 'feedback', 'desirable-difficulties', 'successive-relearning', 'interleaving'],
        algorithms: ['adaptive-scheduler', 'relearn-check', 'due-queue'],
    },
    {
        id: 'weekly-plan',
        title: 'Weekly Plan',
        route: '/app/week',
        purpose: 'Plan the week as a gear and a set of pre-decided blocks, then score it against your success line.',
        steps: [
            'Before the week starts, open Weekly Plan and pick a gear: Green for a normal week, Yellow for a busy one, Red for Eid, illness or travel.',
            'Blocks are generated from your profile and off days (Study Settings): office days and off days for a job holder, class days and free days for a student. Add, remove or edit blocks as needed.',
            'Students get a short class recap on class days — recall today’s lectures from memory the same day — plus a deep block, and two long blocks on free days. Use Yellow for assignment or deadline weeks.',
            'Write the exact task in each block (“Write Task 2 essay #4”), not just “Study”.',
            'During the week, mark each block Done, Partial or Missed once its day has come (future days can only be planned or marked Red). Mark planned holidays as Red so they never count as failures.',
            'Week turned out harder? Switch gear: only blocks from today on that are still planned change.',
            'At the end of the week, fill in the weekly review — what got in the way, one if-then plan, what you shipped — and plan next week.',
        ],
        tips: [
            { text: 'Downgrading is part of the plan. The only real failure is stopping.', refs: ['lally2010'] },
            { text: 'Switching profile only changes blocks generated afterwards — regenerate the week if you want the new profile’s blocks now.' },
            { text: 'Inside a deep block: recall warm-up (10 min) → pre-test the new section (5) → learn in two chunks and rebuild from memory (50) → mixed practice (15) → summary and recall questions (10).', refs: ['richland2009', 'slamecka1978'] },
            { text: 'Do your reviews in the evening: sleep after learning helps consolidation, and relearning after a night’s sleep was faster and lasted longer.', refs: ['diekelmann2010', 'mazza2016'] },
            { text: 'If a block gets interrupted, write one line — where you stopped and the next step — before leaving it.', refs: ['leroyglomb2018'] },
        ],
        techniques: ['implementation-intentions', 'realistic-planning', 'consistency', 'single-track-focus'],
        algorithms: ['weekly-gear-blocks', 'weekly-score'],
    },
    {
        id: 'topics',
        title: 'Topics',
        route: '/app/topics',
        purpose: 'Everything you are learning, each topic a self-test card with its own review schedule.',
        steps: [
            'Select New Topic. Choose a category, a one-session title and the first study date.',
            'Add 3–5 recall questions (answers optional), a five-line summary in your own words, and a practice prompt.',
            'Choose a lane: Major (deep blocks), Minor (light slot) or Work (learned on the job).',
            'Open a topic to see its questions, step progress (“Step 2 of 4”), lapses, next review date and every review task.',
            'Filter the list by status, category, difficulty or lane. Archive topics you no longer need.',
        ],
        tips: [
            { text: 'Good questions ask for meaning, not copying: “Why does transform keep the original shape?” beats “What is transform?”.', refs: ['slamecka1978'] },
            { text: 'Write the summary yourself instead of pasting it — generating the words strengthens memory.', refs: ['slamecka1978'] },
            { text: 'A topic keeps the schedule it was created with, even if you change templates later. The create form shows which schedule the chosen category will apply.' },
        ],
        techniques: ['retrieval-practice', 'desirable-difficulties', 'single-track-focus'],
        algorithms: ['adaptive-scheduler', 'interval-presets', 'review-debt'],
    },
    {
        id: 'mistakes',
        title: 'Mistakes',
        route: '/app/mistakes',
        purpose: 'A notebook for every wrong answer: each one becomes a short review and is then folded back into its topic.',
        steps: [
            'Select Log mistake right after a mock, exercise or interview question you got wrong (or use “Log a mistake” after an Again/Hard grade on the Review page).',
            'Enter the question, your answer, the correct answer, and why it was wrong: concept, memory or careless.',
            'Pick the parent topic it belongs to, and the source (for example “IELTS mock 3”).',
            'The mistake is reviewed at +1, +3 and +7 days on the Review page, marked “Mistake”.',
            'When its reviews are done it shows “Ready to merge”: select Merge to add the question to the parent topic, or Close if it has no parent.',
        ],
        tips: [
            { text: 'Look at the causes: many “concept” mistakes mean restudy the topic; many “careless” ones mean slow down in tests.' },
            { text: 'Log the mistake the same day — correcting an error right after it happens is when it is most useful.', refs: ['metcalfe2017'] },
        ],
        techniques: ['learning-from-errors', 'feedback'],
        algorithms: ['mistake-schedule'],
    },
    {
        id: 'categories',
        title: 'Categories',
        route: '/app/categories',
        purpose: 'Your tracks, with colours, icons and their own review schedules. Reviews are mixed across categories on the Review page.',
        steps: [
            'Select New Category and give it a name, colour and icon.',
            'Select Review schedule on a category to choose a preset — Standard, Exam soon, Long horizon — or your own offsets.',
            'For an exam, choose Exam soon and set the exam date as “Until”: reviews repeat weekly up to that date.',
            'Tick “Also apply to existing topics” to give the category’s current topics the new schedule from their next review.',
            'Edit or delete your own categories; system categories are shared and read-only, but your schedule on them is private.',
        ],
        tips: [
            { text: 'Mixing helps most within a track where items are easy to confuse (for example different IELTS question types).', refs: ['brunmair2019'] },
        ],
        techniques: ['interleaving'],
        algorithms: ['interval-presets', 'due-queue'],
    },
    {
        id: 'practice-logs',
        title: 'Practice Logs',
        route: '/app/practice-logs',
        purpose: 'A record of practice sessions: problem solving, implementation, reading, note making and mock interviews.',
        steps: [
            'Select Log Practice, choose the topic and practice type, and add details, duration and outcome.',
            'Filter the list by type to see where your effort goes.',
            'Use the practice prompt on a topic as the exercise to log.',
        ],
        tips: [
            { text: 'Practise across different problem types rather than one type at a time.', refs: ['rohrer2007'] },
        ],
        techniques: ['interleaving', 'desirable-difficulties'],
        algorithms: [],
    },
    {
        id: 'calendar',
        title: 'Calendar',
        route: '/app/calendar',
        purpose: 'A month view of how many tasks fall on each day and how many are done.',
        steps: [
            'Move between months with the arrows.',
            'Check upcoming busy days before adding many new topics in the same week.',
        ],
        tips: [
            { text: 'Future review dates are a projection: grading a review re-plans that topic’s later dates.' },
        ],
        techniques: ['spaced-repetition'],
        algorithms: ['adaptive-scheduler'],
    },
    {
        id: 'reports',
        title: 'Reports',
        route: '/app/reports',
        purpose: 'Download or email monthly summaries of tasks and practice, including recall grades.',
        steps: [
            'Choose a start and end month (up to 2 months) and select Download CSV.',
            'Or choose months and select Send Report To My Email (up to twice a month; not available for demo accounts).',
        ],
        tips: [{ text: 'Look at the recall-grade column: topics with many Again grades need better questions or a smaller scope.' }],
        techniques: ['successive-relearning'],
        algorithms: ['adaptive-scheduler'],
    },
    {
        id: 'study-settings',
        title: 'Study Settings',
        route: '/app/revision-templates',
        purpose: 'Your study preferences and the default review intervals for topics.',
        steps: [
            'Set your daily review budget, the review-debt threshold and the minutes-per-review estimate.',
            'Choose your profile: Job holder (office days and off days) or Student (class days and free days). It decides the blocks of new weekly plans.',
            'Set the weekly new-topic cap, the day your week starts, your off days (for a student: days without classes) and your weekly success line.',
            'Edit the default review intervals (for example +1, +7, +30, +90), or start from a preset. They apply to new topics in categories without their own schedule.',
        ],
        tips: [
            { text: 'Choose gaps by how long you need to remember: short gaps for an exam in a few weeks, growing gaps for a year-long goal.', refs: ['cepeda2008'] },
        ],
        techniques: ['spaced-repetition', 'realistic-planning'],
        algorithms: ['interval-presets', 'review-estimate', 'review-debt'],
    },
    {
        id: 'profile',
        title: 'Profile',
        route: '/app/profile',
        purpose: 'Your account details, your password, and requests to delete your study data.',
        steps: [
            'Update your name.',
            'Change your password with your current password.',
            'To clear data, go to Delete my data, tick what to remove (topics, mistakes, practice logs, weekly plans, categories, study settings, report history or review-load history) and select Request deletion.',
            'Read the confirmation: it shows how many items each choice holds and what else changes (for example, deleting categories leaves your topics uncategorised). Type DELETE to send the request.',
            'An admin reviews the request. Its status (pending, completed or rejected, with the reason) is shown under Your requests.',
        ],
        tips: [
            { text: 'Your email address cannot be changed here.' },
            { text: 'Deleting data never deletes your account. Before anything is removed, it is archived; an admin can restore it from the archive for a limited time (90 days by default), after which the archive is purged for good.' },
            { text: 'You can have one deletion request in progress at a time. The demo account cannot request deletion.' },
        ],
        techniques: [],
        algorithms: [],
    },
]
