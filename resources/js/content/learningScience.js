/**
 * Learning-science catalog: the techniques and decision algorithms StudyTracker
 * uses, their benefits, evidence level and research references.
 *
 * Rendered by the public Features page (/features) and the in-app User Guide
 * (/app/guide). `scripts/check-learning-content.mjs` runs before every build and
 * fails on unknown, uncited or unverified references.
 *
 * When app behaviour changes, update the matching entry here.
 *
 * References were checked against Crossref (journal articles) and library
 * records (books) before being marked `verified`. `source` records where the
 * reference came from: the two source documents or an addition.
 */

export const EVIDENCE = {
    research: 'Research-backed',
    rule: 'Rule of thumb',
}

export const references = {
    adesope2017: {
        short: 'Adesope et al., 2017',
        apa: 'Adesope, O. O., Trevisan, D. A., & Sundararajan, N. (2017). Rethinking the use of tests: A meta-analysis of practice testing. Review of Educational Research, 87(3), 659–701.',
        doi: '10.3102/0034654316689306',
        source: 'learning-science guide',
        verified: true,
    },
    bjork1994: {
        short: 'Bjork, 1994',
        apa: 'Bjork, R. A. (1994). Memory and metamemory considerations in the training of human beings. In J. Metcalfe & A. P. Shimamura (Eds.), Metacognition: Knowing about knowing (pp. 185–206). MIT Press.',
        doi: '10.7551/mitpress/4561.003.0011',
        source: 'learning-science guide',
        verified: true,
    },
    brunmair2019: {
        short: 'Brunmair & Richter, 2019',
        apa: 'Brunmair, M., & Richter, T. (2019). Similarity matters: A meta-analysis of interleaved learning and its moderators. Psychological Bulletin, 145(11), 1029–1052.',
        doi: '10.1037/bul0000209',
        source: 'both documents',
        verified: true,
    },
    buehler1994: {
        short: 'Buehler et al., 1994',
        apa: 'Buehler, R., Griffin, D., & Ross, M. (1994). Exploring the "planning fallacy": Why people underestimate their task completion times. Journal of Personality and Social Psychology, 67(3), 366–381.',
        doi: '10.1037/0022-3514.67.3.366',
        source: 'learning system',
        verified: true,
    },
    butler2008: {
        short: 'Butler & Roediger, 2008',
        apa: 'Butler, A. C., & Roediger, H. L., III. (2008). Feedback enhances the positive effects and reduces the negative effects of multiple-choice testing. Memory & Cognition, 36(3), 604–616.',
        doi: '10.3758/MC.36.3.604',
        source: 'added',
        verified: true,
    },
    cepeda2006: {
        short: 'Cepeda et al., 2006',
        apa: 'Cepeda, N. J., Pashler, H., Vul, E., Wixted, J. T., & Rohrer, D. (2006). Distributed practice in verbal recall tasks: A review and quantitative synthesis. Psychological Bulletin, 132(3), 354–380.',
        doi: '10.1037/0033-2909.132.3.354',
        source: 'learning-science guide',
        verified: true,
    },
    cepeda2008: {
        short: 'Cepeda et al., 2008',
        apa: 'Cepeda, N. J., Vul, E., Rohrer, D., Wixted, J. T., & Pashler, H. (2008). Spacing effects in learning: A temporal ridgeline of optimal retention. Psychological Science, 19(11), 1095–1102.',
        doi: '10.1111/j.1467-9280.2008.02209.x',
        source: 'both documents',
        verified: true,
    },
    diekelmann2010: {
        short: 'Diekelmann & Born, 2010',
        apa: 'Diekelmann, S., & Born, J. (2010). The memory function of sleep. Nature Reviews Neuroscience, 11(2), 114–126.',
        doi: '10.1038/nrn2762',
        source: 'both documents',
        verified: true,
    },
    donoghue2021: {
        short: 'Donoghue & Hattie, 2021',
        apa: 'Donoghue, G. M., & Hattie, J. A. C. (2021). A meta-analysis of ten learning techniques. Frontiers in Education, 6, Article 581216.',
        doi: '10.3389/feduc.2021.581216',
        source: 'learning-science guide',
        verified: true,
    },
    dunlosky2013: {
        short: 'Dunlosky et al., 2013',
        apa: 'Dunlosky, J., Rawson, K. A., Marsh, E. J., Nathan, M. J., & Willingham, D. T. (2013). Improving students’ learning with effective learning techniques: Promising directions from cognitive and educational psychology. Psychological Science in the Public Interest, 14(1), 4–58.',
        doi: '10.1177/1529100612453266',
        source: 'learning-science guide',
        verified: true,
    },
    ebbinghaus1885: {
        short: 'Ebbinghaus, 1885/1913',
        apa: 'Ebbinghaus, H. (1913). Memory: A contribution to experimental psychology (H. A. Ruger & C. E. Bussenius, Trans.). Teachers College, Columbia University. (Original work published 1885)',
        doi: null,
        source: 'learning-science guide',
        verified: true,
    },
    gollwitzer2006: {
        short: 'Gollwitzer & Sheeran, 2006',
        apa: 'Gollwitzer, P. M., & Sheeran, P. (2006). Implementation intentions and goal achievement: A meta-analysis of effects and processes. Advances in Experimental Social Psychology, 38, 69–119.',
        doi: '10.1016/S0065-2601(06)38002-1',
        source: 'learning system',
        verified: true,
    },
    karpicke2011: {
        short: 'Karpicke & Blunt, 2011',
        apa: 'Karpicke, J. D., & Blunt, J. R. (2011). Retrieval practice produces more learning than elaborative studying with concept mapping. Science, 331(6018), 772–775.',
        doi: '10.1126/science.1199327',
        source: 'both documents',
        verified: true,
    },
    kornell2008: {
        short: 'Kornell & Bjork, 2008',
        apa: 'Kornell, N., & Bjork, R. A. (2008). Learning concepts and categories: Is spacing the "enemy of induction"? Psychological Science, 19(6), 585–592.',
        doi: '10.1111/j.1467-9280.2008.02127.x',
        source: 'learning-science guide',
        verified: true,
    },
    lally2010: {
        short: 'Lally et al., 2010',
        apa: 'Lally, P., van Jaarsveld, C. H. M., Potts, H. W. W., & Wardle, J. (2010). How are habits formed: Modelling habit formation in the real world. European Journal of Social Psychology, 40(6), 998–1009.',
        doi: '10.1002/ejsp.674',
        source: 'learning system',
        verified: true,
    },
    leitner1972: {
        short: 'Leitner, 1972',
        apa: 'Leitner, S. (1972). So lernt man lernen: Der Weg zum Erfolg [How to learn to learn: The way to success]. Herder.',
        doi: null,
        source: 'added',
        verified: true,
    },
    leroy2009: {
        short: 'Leroy, 2009',
        apa: 'Leroy, S. (2009). Why is it so hard to do my work? The challenge of attention residue when switching between work tasks. Organizational Behavior and Human Decision Processes, 109(2), 168–181.',
        doi: '10.1016/j.obhdp.2009.04.002',
        source: 'learning system',
        verified: true,
    },
    leroyglomb2018: {
        short: 'Leroy & Glomb, 2018',
        apa: 'Leroy, S., & Glomb, T. M. (2018). Tasks interrupted: How anticipating time pressure on resumption of an interrupted task causes attention residue and low performance on interrupting tasks and how a "ready-to-resume" plan mitigates the effects. Organization Science, 29(3), 380–397.',
        doi: '10.1287/orsc.2017.1184',
        source: 'learning system',
        verified: true,
    },
    metcalfe2017: {
        short: 'Metcalfe, 2017',
        apa: 'Metcalfe, J. (2017). Learning from errors. Annual Review of Psychology, 68, 465–489.',
        doi: '10.1146/annurev-psych-010416-044022',
        source: 'added',
        verified: true,
    },
    mazza2016: {
        short: 'Mazza et al., 2016',
        apa: 'Mazza, S., Gerbier, E., Gustin, M.-P., Kasikci, Z., Koenig, O., Toppino, T. C., & Magnin, M. (2016). Relearn faster and retain longer: Along with practice, sleep makes perfect. Psychological Science, 27(10), 1321–1330.',
        doi: '10.1177/0956797616659930',
        source: 'learning system',
        verified: true,
    },
    mcdaniel2009: {
        short: 'McDaniel et al., 2009',
        apa: 'McDaniel, M. A., Howard, D. C., & Einstein, G. O. (2009). The read-recite-review study strategy: Effective and portable. Psychological Science, 20(4), 516–522.',
        doi: '10.1111/j.1467-9280.2009.02325.x',
        source: 'learning-science guide',
        verified: true,
    },
    murre2015: {
        short: 'Murre & Dros, 2015',
        apa: 'Murre, J. M. J., & Dros, J. (2015). Replication and analysis of Ebbinghaus’ forgetting curve. PLOS ONE, 10(7), Article e0120644.',
        doi: '10.1371/journal.pone.0120644',
        source: 'learning-science guide',
        verified: true,
    },
    newport2016: {
        short: 'Newport, 2016',
        apa: 'Newport, C. (2016). Deep work: Rules for focused success in a distracted world. Grand Central Publishing.',
        doi: null,
        source: 'learning system',
        verified: true,
    },
    rawson2013: {
        short: 'Rawson et al., 2013',
        apa: 'Rawson, K. A., Dunlosky, J., & Sciartelli, S. M. (2013). The power of successive relearning: Improving performance on course exams and long-term retention. Educational Psychology Review, 25(4), 523–548.',
        doi: '10.1007/s10648-013-9240-4',
        source: 'both documents',
        verified: true,
    },
    richland2009: {
        short: 'Richland et al., 2009',
        apa: 'Richland, L. E., Kornell, N., & Kao, L. S. (2009). The pretesting effect: Do unsuccessful retrieval attempts enhance learning? Journal of Experimental Psychology: Applied, 15(3), 243–257.',
        doi: '10.1037/a0016496',
        source: 'both documents',
        verified: true,
    },
    roediger2006: {
        short: 'Roediger & Karpicke, 2006',
        apa: 'Roediger, H. L., III, & Karpicke, J. D. (2006). Test-enhanced learning: Taking memory tests improves long-term retention. Psychological Science, 17(3), 249–255.',
        doi: '10.1111/j.1467-9280.2006.01693.x',
        source: 'both documents',
        verified: true,
    },
    rohrer2007: {
        short: 'Rohrer & Taylor, 2007',
        apa: 'Rohrer, D., & Taylor, K. (2007). The shuffling of mathematics problems improves learning. Instructional Science, 35(6), 481–498.',
        doi: '10.1007/s11251-007-9015-8',
        source: 'learning-science guide',
        verified: true,
    },
    rowland2014: {
        short: 'Rowland, 2014',
        apa: 'Rowland, C. A. (2014). The effect of testing versus restudy on retention: A meta-analytic review of the testing effect. Psychological Bulletin, 140(6), 1432–1463.',
        doi: '10.1037/a0037559',
        source: 'learning-science guide',
        verified: true,
    },
    slamecka1978: {
        short: 'Slamecka & Graf, 1978',
        apa: 'Slamecka, N. J., & Graf, P. (1978). The generation effect: Delineation of a phenomenon. Journal of Experimental Psychology: Human Learning and Memory, 4(6), 592–604.',
        doi: '10.1037/0278-7393.4.6.592',
        source: 'both documents',
        verified: true,
    },
    soderstrom2015: {
        short: 'Soderstrom & Bjork, 2015',
        apa: 'Soderstrom, N. C., & Bjork, R. A. (2015). Learning versus performance: An integrative review. Perspectives on Psychological Science, 10(2), 176–199.',
        doi: '10.1177/1745691615569000',
        source: 'learning-science guide',
        verified: true,
    },
}

/**
 * Learning techniques the app applies.
 * `where` lists app features (label + route) where the technique shows up.
 */
export const techniques = [
    {
        id: 'retrieval-practice',
        name: 'Retrieval practice (the testing effect)',
        evidence: 'research',
        description:
            'Pulling information out of memory — answering a question without looking — instead of re-reading it. A test is not only a measurement: the act of recalling strengthens the memory.',
        why: 'Every review in StudyTracker is a self-test: the Review page shows your recall questions first and hides the answers until you have tried.',
        benefits: [
            'Much better long-term retention than re-reading: in one classic study, students who read a passage once and recalled it three times remembered about 61% a week later, versus about 40% for students who read it four times.',
            'Beats “deeper” study methods: retrieval practice outperformed elaborative concept mapping on a test one week later — even when that test was drawing a concept map.',
            'Shows you what you actually know, breaking the “illusion of fluency” that comes from familiar-looking notes.',
            'Consistent moderate-to-large advantage over restudying across hundreds of experiments; rated a high-utility technique in major reviews.',
        ],
        where: [
            { label: 'Review page', to: '/app/review' },
            { label: 'Recall questions on topics', to: '/app/topics' },
        ],
        refs: ['roediger2006', 'karpicke2011', 'rowland2014', 'adesope2017', 'dunlosky2013', 'donoghue2021'],
    },
    {
        id: 'spaced-repetition',
        name: 'Spaced repetition (distributed practice)',
        evidence: 'research',
        description:
            'Reviewing the same material several times with growing gaps in between, instead of cramming it in one sitting.',
        why: 'Each topic gets a schedule of review dates that grow apart — short gaps first, when forgetting is fastest, then longer ones.',
        benefits: [
            'Spaced study beats massed study in almost every one of the hundreds of experiments synthesised by Cepeda and colleagues.',
            'Forgetting is steepest in the first day or two (Ebbinghaus’ curve, replicated in 2015), so the first reviews come quickly.',
            'The best gap depends on how long you need to remember: roughly 10–20% of the retention interval, falling towards 5–10% for a year-long horizon — which is why categories can have short or long schedules.',
            'Rated a high-utility technique alongside practice testing.',
        ],
        where: [
            { label: 'Revision schedule on every topic', to: '/app/topics' },
            { label: 'Calendar', to: '/app/calendar' },
            { label: 'Study Settings', to: '/app/revision-templates' },
        ],
        refs: ['ebbinghaus1885', 'murre2015', 'cepeda2006', 'cepeda2008', 'dunlosky2013'],
    },
    {
        id: 'successive-relearning',
        name: 'Successive relearning',
        evidence: 'research',
        description:
            'Combining the two techniques above: test yourself at spaced intervals, and whenever recall fails, relearn the material until you get it right — then space it out again.',
        why: 'Your recall grade drives the schedule: a failed or shaky recall brings the topic back tomorrow; a solid one pushes it further out.',
        benefits: [
            'Improved course-exam performance and long-term retention in classroom studies.',
            'Weak material gets extra practice exactly where it is needed, while well-known material stops taking time.',
            'Relearning is faster each round, so effort shrinks as memory strengthens.',
        ],
        where: [
            { label: 'Recall grades on the Review page', to: '/app/review' },
            { label: 'Relearn checks in Daily Tasks', to: '/app/tasks' },
        ],
        refs: ['rawson2013'],
    },
    {
        id: 'feedback',
        name: 'Feedback after retrieval',
        evidence: 'research',
        description: 'Checking your answer against the correct one immediately after trying to recall it.',
        why: 'After you answer from memory, Reveal shows each question’s answer and the topic summary — your answer key — before you grade yourself.',
        benefits: [
            'Feedback increases the benefit of testing and reduces the risk of “learning” your own wrong answers.',
            'Even unsuccessful recall attempts improve later learning when the correct answer follows.',
        ],
        where: [
            { label: 'Reveal step on the Review page', to: '/app/review' },
            { label: 'Topic summary (answer key)', to: '/app/topics' },
        ],
        refs: ['butler2008', 'richland2009'],
    },
    {
        id: 'desirable-difficulties',
        name: 'Desirable difficulties and generation',
        evidence: 'research',
        description:
            'Learning methods that feel harder in the moment — recalling, generating answers yourself, spacing — often produce more durable learning than methods that feel smooth.',
        why: 'The app asks you to answer before revealing, and offers blank-page recall when a topic has no questions. A “Hard” or “Again” grade is part of learning, not a failure.',
        benefits: [
            'Information you generate yourself is remembered better than information you only read (the generation effect).',
            'Read–recite–review outperformed note-taking and re-reading in a direct comparison.',
            'Separating performance during practice from long-term learning helps you grade honestly instead of chasing easy “Good” grades.',
        ],
        where: [
            { label: 'Question-first review', to: '/app/review' },
            { label: 'Blank-page recall', to: '/app/review' },
        ],
        refs: ['bjork1994', 'slamecka1978', 'soderstrom2015', 'mcdaniel2009'],
    },
    {
        id: 'interleaving',
        name: 'Interleaving (mixing)',
        evidence: 'research',
        description: 'Mixing different kinds of problems or topics in one session instead of practising them in blocks.',
        why: 'Today’s reviews are mixed across your categories rather than grouped, so consecutive cards come from different tracks.',
        benefits: [
            'Builds the skill of recognising which method or concept applies: in one study, interleaved maths practice scored about 63% a week later versus about 20% for blocked practice.',
            'Learners judging painting styles learned better from mixed examples — even though most believed blocked practice worked better.',
        ],
        caveat:
            'The benefit is largest for similar, easily confused material (for example IELTS question types or ML algorithm choice). Mixing unrelated tracks mainly gives you spaced retrieval and variety — for real interleaving, mix problem types within a track.',
        where: [{ label: 'Review queue ordering', to: '/app/review' }],
        refs: ['rohrer2007', 'kornell2008', 'brunmair2019'],
    },
    {
        id: 'realistic-planning',
        name: 'Realistic planning',
        evidence: 'research',
        description:
            'People consistently underestimate how long tasks take and how often life interrupts — the planning fallacy. Plans built on optimistic guesses break at the first interruption.',
        why: 'StudyTracker estimates your review time from your own timed reviews and warns when the day’s load exceeds your budget, instead of letting the pile grow silently.',
        benefits: [
            'Estimates based on your real past timings replace optimistic guesses.',
            'A fixed daily budget keeps reviews sustainable on busy days.',
        ],
        where: [
            { label: 'Due-today banner', to: '/app' },
            { label: 'Study Settings — review budget', to: '/app/revision-templates' },
        ],
        refs: ['buehler1994'],
    },
    {
        id: 'consistency',
        name: 'Consistency over perfection',
        evidence: 'research',
        description:
            'Habits form through repetition in a stable context. In a real-world study, missing one opportunity did not materially affect habit formation — so a bad day matters far less than giving up.',
        why: 'After a day without reviews, the dashboard suggests a small minimum day instead of a marathon: never miss twice.',
        benefits: [
            'A single missed day is treated as normal, which removes the guilt that often leads to quitting.',
            'Small minimum days keep the habit alive when time is short.',
        ],
        where: [{ label: 'Never-miss-twice prompt on the dashboard', to: '/app' }],
        refs: ['lally2010'],
    },
    {
        id: 'single-track-focus',
        name: 'Single-track focus',
        evidence: 'research',
        description:
            'After switching tasks, part of your attention stays stuck on the previous one (attention residue). Keeping one track per study block, and writing a short “ready-to-resume” note when interrupted, reduces this cost.',
        why: 'Topics carry a lane — Major (deep blocks), Minor (light slot) or Work (learned on the job) — so you can keep each block on one track.',
        benefits: [
            'Less attention residue means more focus for the block in front of you.',
            'A one-line note on where you stopped makes resuming after an interruption faster.',
            'Rhythmic daily deep-work blocks fit around a full-time job (a practitioner recommendation rather than an experiment).',
        ],
        where: [{ label: 'Topic lanes', to: '/app/topics' }],
        refs: ['leroy2009', 'leroyglomb2018', 'newport2016'],
    },
    {
        id: 'implementation-intentions',
        name: 'Implementation intentions (if-then planning)',
        evidence: 'research',
        description:
            'Plans that fix when, where and what — “After breakfast, at my desk, I write Task 2 essay #4” — and if-then responses to likely obstacles, decided in advance while calm.',
        why: 'Weekly plans let you write the exact task for each block ahead of time, and the weekly review asks for one new if-then plan for whatever got in the way.',
        benefits: [
            'A meta-analysis of 94 studies found a medium-to-large effect of implementation intentions on reaching goals.',
            'Removes the decision at the moment of starting, when resistance is highest.',
            'If-then plans turn recurring breakdowns into a pre-decided response instead of a negotiation.',
        ],
        where: [
            { label: 'Weekly Plan — pre-decided block tasks', to: '/app/week' },
            { label: 'Weekly review — if-then plan', to: '/app/week' },
        ],
        refs: ['gollwitzer2006'],
    },
    {
        id: 'learning-from-errors',
        name: 'Learning from errors',
        evidence: 'research',
        description:
            'Errors are useful when they are followed by the correct answer: noticing a mistake and correcting it can make the right answer more memorable than if it had never been wrong.',
        why: 'Every wrong answer — from a mock test, an exercise or an interview — can be logged in the Mistakes notebook with its cause, reviewed at +1, +3 and +7 days, and then merged into the topic it belongs to.',
        benefits: [
            'Corrective feedback after an error supports learning, especially when you were confident in the wrong answer.',
            'Short spaced reviews of each error are successive relearning on exactly the material you got wrong.',
            'Tagging the cause (concept, memory or careless) shows whether to restudy, review or slow down.',
        ],
        where: [{ label: 'Mistakes notebook', to: '/app/mistakes' }],
        refs: ['metcalfe2017', 'rawson2013'],
    },
]

/**
 * Decision algorithms the app runs.
 */
export const algorithms = [
    {
        id: 'adaptive-scheduler',
        name: 'Adaptive review scheduler',
        evidence: 'research',
        decides: 'When each topic is reviewed next, based on how well you recalled it.',
        rule: [
            'A topic’s schedule is a list of day offsets, e.g. +1, +7, +30, +90. The topic remembers how many steps it has passed.',
            'Good — advance one step; the next review is spaced by the gap to the next offset.',
            'Easy — skip one step (advance two).',
            'Hard — keep your progress and check again tomorrow.',
            'Again — restart the steps from the beginning and relearn tomorrow (Leitner: a wrong card goes back to box 1).',
            'Every graded review re-plans the topic’s remaining reviews from the day you actually reviewed, so a late review never squeezes the next gap.',
        ],
        example:
            'Learned on 7 Oct with +1, +7, +30, +90. Graded Good each time on schedule: reviews fall on 8 Oct, 14 Oct, 6 Nov and 5 Jan — the classic schedule. Graded Easy on 8 Oct instead: the 14 Oct review is skipped and the next one is on 6 Nov. Graded Again on 6 Nov: relearn on 7 Nov, then 13 Nov, 6 Dec and 4 Feb.',
        benefits: [
            'Topics you know well stop taking review time; topics you struggle with come back sooner.',
            'If you always grade Good on time, you get exactly the familiar schedule — no surprises.',
            'Spacing is measured from your real review day, preserving the intended gaps.',
        ],
        menus: ['review', 'daily-tasks', 'topics'],
        refs: ['leitner1972', 'cepeda2006', 'rawson2013', 'dunlosky2013'],
    },
    {
        id: 'relearn-check',
        name: 'Relearn check',
        evidence: 'research',
        decides: 'What happens after a failed or shaky recall.',
        rule: [
            'Hard or Again schedules a short relearn check for the next day.',
            'Re-study the answer key right away (about 5 minutes), then sleep on it.',
            'When the check goes well, normal spacing resumes from that day.',
        ],
        example:
            'Graded Hard on 6 Nov at step 2 of +1, +7, +30, +90: relearn check on 7 Nov. Graded Good on 7 Nov: the next review is 60 days later, on 6 Jan.',
        benefits: [
            'Fixes forgetting while it is fresh instead of leaving a weak memory for a long gap.',
            'Relearning after a night’s sleep took fewer trials and was retained longer than relearning on the same day.',
        ],
        menus: ['review', 'daily-tasks'],
        refs: ['rawson2013', 'mazza2016'],
    },
    {
        id: 'interval-presets',
        name: 'Interval presets and exam-date repeat',
        evidence: 'research',
        decides: 'Which review gaps a new topic gets, based on its category.',
        rule: [
            'Standard: +1, +7, +30, +90 days — material you need for years.',
            'Exam soon: +1, +3, +7, +14, then weekly until the exam date you set.',
            'Long horizon: +1, +3, +7, +21, +60, then every 90 days.',
            'Mistakes: +1, +3, +7, then merge into the parent topic.',
            'A new topic takes its category’s schedule, else your default intervals; it keeps that schedule even if you change settings later.',
        ],
        example:
            'An IELTS topic first studied on 11 Oct under “Exam soon” until 5 Dec is reviewed on 12, 14, 18 and 25 Oct, then weekly (1 Nov, …) — never after 5 Dec.',
        benefits: [
            'Gaps match how long you need to remember: short for an exam weeks away, growing for a year-long goal.',
            'Weekly maintenance keeps exam material fresh right up to the test without extra planning.',
        ],
        menus: ['categories', 'topics', 'study-settings'],
        refs: ['cepeda2008'],
    },
    {
        id: 'due-queue',
        name: 'Due-review queue',
        evidence: 'rule',
        decides: 'Which reviews you see today, and in what order.',
        rule: [
            'One review per topic — the earliest one due.',
            'Overdue reviews first, oldest first.',
            'Then today’s reviews, mixed round-robin across categories.',
            'The queue is split at your daily time budget; the rest can carry over.',
        ],
        example:
            'With Algorithms reviews A1, A2 and a Backend review B1 due today, the order is A1, B1, A2. With 12 topics due at about 3 minutes each and a 25-minute budget, the first 8 are within budget.',
        benefits: [
            'After missed days you recover oldest-first at a steady pace instead of bingeing.',
            'No duplicate reviews of the same topic on one day.',
            'Varied sessions; caveat — mixing helps most when the material is similar.',
        ],
        menus: ['review'],
        refs: ['cepeda2006', 'brunmair2019'],
    },
    {
        id: 'review-estimate',
        name: 'Review-time estimate and budget',
        evidence: 'rule',
        decides: 'How long today’s reviews will take, and whether that exceeds your budget.',
        rule: [
            'Per-review time = the average of your last 30 timed reviews (at least 5), clamped to 1–15 minutes; otherwise your “minutes per review” setting.',
            'Estimate = due topics × per-review time, rounded up.',
            'Warn when the estimate is above your daily budget (default 25 minutes).',
        ],
        example: '10 topics due × 3 minutes = about 30 minutes, above a 25-minute budget: the banner turns into a warning.',
        benefits: [
            'Uses your own data instead of an optimistic guess.',
            'Makes overload visible before the pile grows.',
        ],
        menus: ['dashboard', 'daily-tasks', 'study-settings'],
        refs: ['buehler1994'],
    },
    {
        id: 'review-debt',
        name: 'Review-debt detection',
        evidence: 'rule',
        decides: 'When to warn you to stop adding new topics.',
        rule: [
            'Each day the app records your start-of-day review load.',
            'Three consecutive days above your debt threshold (default 30 minutes) turn the warning on.',
            'It turns off as soon as a day — or today after reviewing — is back under your budget.',
            'It only warns; you can still add topics.',
        ],
        example: 'Loads of 34, 38 and 31 minutes on three days in a row start review debt; reviewing down to 22 minutes clears it.',
        benefits: [
            'Protects the reviews that make spacing work.',
            'Stops new material from silently outgrowing the time you have.',
        ],
        menus: ['dashboard', 'topics'],
        refs: ['cepeda2006', 'buehler1994'],
    },
    {
        id: 'never-miss-twice',
        name: 'Never-miss-twice prompt',
        evidence: 'rule',
        decides: 'When to nudge you towards a minimum day.',
        rule: [
            'If no review was completed yesterday while reviews were due, the dashboard suggests 15–20 minutes of your oldest reviews today.',
        ],
        example: 'Skipped reviews on Tuesday → on Wednesday the dashboard shows the minimum-day prompt.',
        benefits: [
            'Treats one missed day as normal while stopping a second one.',
            'Restarts with a small, achievable step instead of a catch-up marathon.',
        ],
        menus: ['dashboard'],
        refs: ['lally2010'],
    },
    {
        id: 'weekly-gear-blocks',
        name: 'Weekly gear blocks',
        evidence: 'rule',
        decides: 'Which study blocks a week gets, based on its gear, your study profile (job holder or student) and your off days.',
        rule: [
            'Each day is a workday — an office day for a job holder, a class day for a student — or an off day (a free day for a student), from your off-days setting.',
            'Job holder, Green (about 19 hours): office days get a 90-minute morning block, 20 minutes of reviews and a 20-minute minor slot; off days get Block A (150 min), Block B (75 min) and 15 minutes of reviews.',
            'Job holder, Yellow (about 12 hours): mornings and 15-minute reviews; Block A on the first off day only.',
            'Student, Green (about 24 hours): class days get a 30-minute class recap, a 90-minute deep block, 25 minutes of reviews and a 30-minute minor slot; free days get Block A (150 min), Block B (120 min) and 25 minutes of reviews.',
            'Student, Yellow (assignment or deadline week, about 13 hours): a 20-minute recap, a 60-minute deep block and 20 minutes of reviews on class days; Block A (120 min) and reviews on free days.',
            'Red (illness, travel, Eid) for both: 20 minutes of reviews a day — the minimum day.',
            'Changing gear or profile mid-week replaces only blocks from today on that are still planned.',
        ],
        example:
            'With Friday and Saturday off, a week starting Sunday 11 Oct gets 21 / 13 / 7 blocks (Green / Yellow / Red) for a job holder, and 26 / 19 / 7 blocks for a student.',
        benefits: [
            'Fewer, bigger, pre-decided blocks with slack survive real weeks better than a packed schedule.',
            'Choosing a lighter gear in advance turns a hard week into a plan instead of a failure.',
            'A student’s class recap is a first review on the day of the lecture, while forgetting is steepest.',
            'Each block has one lane, so one track per block.',
        ],
        menus: ['weekly-plan', 'dashboard'],
        refs: ['buehler1994', 'gollwitzer2006', 'newport2016', 'murre2015'],
    },
    {
        id: 'weekly-score',
        name: 'Weekly consistency score',
        evidence: 'rule',
        decides: 'Whether the week went well.',
        rule: [
            'Count blocks before today plus any block already marked; red-day blocks are excluded.',
            'A planned block left unmarked after its day counts as missed.',
            'Score = (done + ½ × partial) ÷ counted blocks, compared with your success line (default 80%).',
        ],
        example: '7 done, 2 partial and 1 missed out of 10 counted blocks (plus one red-day block) = 80%: on track.',
        benefits: [
            'Rewards consistency, not perfection: 80% of planned blocks is a successful week.',
            'Partial credit and red days remove the all-or-nothing thinking that makes people quit.',
        ],
        menus: ['weekly-plan', 'dashboard'],
        refs: ['lally2010'],
    },
    {
        id: 'mistake-schedule',
        name: 'Mistake schedule and merge',
        evidence: 'research',
        decides: 'How a logged mistake is reviewed and when it rejoins its topic.',
        rule: [
            'A mistake gets reviews at +1, +3 and +7 days from the day you log it — no learn step.',
            'Grades work as for any topic: Again restarts the three reviews with a relearn check tomorrow.',
            'After the last review it is “ready”: merge adds its question and correct answer to the parent topic’s recall questions (no duplicates, at most 10), then closes it.',
        ],
        example:
            'A True/False/Not Given error logged on 11 Oct is reviewed on 12, 14 and 18 Oct; after a Good on 18 Oct you merge it into “IELTS Reading”, which gains the question.',
        benefits: [
            'Errors are corrected while fresh, with the right answer shown every time.',
            'Merging keeps the question in the parent topic’s long-term reviews instead of a growing separate list.',
        ],
        menus: ['mistakes', 'review'],
        refs: ['metcalfe2017', 'butler2008', 'rawson2013'],
    },
]

/** All reference ids cited by a list of technique/algorithm entries. */
export const citedIds = (entries) => [...new Set(entries.flatMap((entry) => entry.refs))]

/** References sorted alphabetically by first author. */
export const sortedReferences = (ids = Object.keys(references)) =>
    ids
        .filter((id) => references[id])
        .map((id) => ({ id, ...references[id] }))
        .sort((a, b) => a.apa.localeCompare(b.apa))

export const techniqueById = (id) => techniques.find((t) => t.id === id)
export const algorithmById = (id) => algorithms.find((a) => a.id === id)
