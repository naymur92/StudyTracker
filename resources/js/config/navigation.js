/**
 * Sidebar navigation for the authenticated app.
 *
 * `guideSection` is the anchor of the menu's section in the User Guide
 * (/app/guide#<guideSection>); the content check requires one per item.
 */
export const mainNavigation = [
    {
        name: 'Dashboard',
        label: 'Dashboard',
        to: '/app',
        guideSection: 'dashboard',
        icon: 'M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 16l4-4m0 0a9 9 0 11-12.99-12.99 9 9 0 0112.99 12.99z',
    },
    {
        name: 'DailyTasks',
        label: 'Daily Tasks',
        to: '/app/tasks',
        guideSection: 'daily-tasks',
        icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
    },
    {
        name: 'Review',
        label: 'Review',
        to: '/app/review',
        guideSection: 'review',
        icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    },
    {
        name: 'WeeklyPlan',
        label: 'Weekly Plan',
        to: '/app/week',
        guideSection: 'weekly-plan',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2zm4-5h2m2 0h2',
    },
    {
        name: 'Topics',
        label: 'Topics',
        to: '/app/topics',
        guideSection: 'topics',
        icon: 'M12 6.253v13m0-13C6.5 6.253 2 10.998 2 12s4.5 5.747 10 5.747m0-13c5.5 0 10 4.745 10 5.747s-4.5 5.747-10 5.747m0-13v13m0-13C6.5 6.253 2 10.998 2 12s4.5 5.747 10 5.747m0 0c5.5 0 10-4.745 10-5.747s-4.5-5.747-10-5.747',
    },
    {
        name: 'Mistakes',
        label: 'Mistakes',
        to: '/app/mistakes',
        guideSection: 'mistakes',
        icon: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
    },
    {
        name: 'Categories',
        label: 'Categories',
        to: '/app/categories',
        guideSection: 'categories',
        icon: 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
    },
    {
        name: 'PracticeLogs',
        label: 'Practice Logs',
        to: '/app/practice-logs',
        guideSection: 'practice-logs',
        icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    },
    {
        name: 'Calendar',
        label: 'Calendar',
        to: '/app/calendar',
        guideSection: 'calendar',
        icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        name: 'Reports',
        label: 'Reports',
        to: '/app/reports',
        guideSection: 'reports',
        icon: 'M7 20h10a2 2 0 002-2V6.828a2 2 0 00-.586-1.414l-2.828-2.828A2 2 0 0014.172 2H7a2 2 0 00-2 2v14a2 2 0 002 2zM9 13h6M9 17h6M9 9h2',
    },
    {
        name: 'RevisionTemplates',
        label: 'Study Settings',
        to: '/app/revision-templates',
        guideSection: 'study-settings',
        icon: 'M9.75 3a3 3 0 00-2.995 2.824L6.75 6v.086a2.25 2.25 0 01-1.062 1.914l-.074.044-.074.043a2.25 2.25 0 00-1.058 2.66l.026.08.026.08a2.25 2.25 0 010 1.506l-.026.08-.026.08a2.25 2.25 0 001.058 2.66l.074.043.074.044a2.25 2.25 0 011.062 1.914V18l.005.176A3 3 0 009.75 21h.5a3 3 0 002.995-2.824L13.25 18v-.086a2.25 2.25 0 011.062-1.914l.074-.044.074-.043a2.25 2.25 0 001.058-2.66l-.026-.08-.026-.08a2.25 2.25 0 010-1.506l.026-.08.026-.08a2.25 2.25 0 00-1.058-2.66l-.074-.043-.074-.044a2.25 2.25 0 01-1.062-1.914V6l-.005-.176A3 3 0 0010.25 3h-.5zM10 9.75a2.25 2.25 0 100 4.5 2.25 2.25 0 000-4.5z',
    },
    {
        name: 'UserGuide',
        label: 'User Guide',
        to: '/app/guide',
        guideSection: 'getting-started',
        icon: 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        name: 'Profile',
        label: 'Profile',
        to: '/app/profile',
        guideSection: 'profile',
        icon: 'M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0',
    },
]

/** Guide section anchor for a route name, or null. */
export const guideSectionFor = (routeName) => mainNavigation.find((item) => item.name === routeName)?.guideSection ?? null
