import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layout components
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import PublicLayout from '@/layouts/PublicLayout.vue'

// Pages
import LoginPage from '@/pages/auth/LoginPage.vue'
import RegisterPage from '@/pages/auth/RegisterPage.vue'
import VerifyEmailPage from '@/pages/auth/VerifyEmailPage.vue'
import VerifyErrorPage from '@/pages/auth/VerifyErrorPage.vue'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage.vue'
import HomePage from '@/pages/HomePage.vue'
import AboutPage from '@/pages/AboutPage.vue'
import DashboardPage from '@/pages/DashboardPage.vue'
import TopicsListPage from '@/pages/topics/ListPage.vue'
import DailyTasksPage from '@/pages/tasks/DailyPage.vue'
import PracticeLogsPage from '@/pages/practice-logs/ListPage.vue'
import CalendarPage from '@/pages/calendar/CalendarPage.vue'
import ReportsPage from '@/pages/reports/ReportsPage.vue'
import ProfilePage from '@/pages/ProfilePage.vue'
import NotFoundPage from '@/pages/NotFoundPage.vue'

const routes = [
    // Public routes (accessible to everyone, but home redirects if authenticated)
    {
        path: '/',
        component: PublicLayout,
        meta: { requiresGuest: true },
        children: [
            {
                path: '',
                name: 'Home',
                component: HomePage,
            },
        ],
    },
    {
        path: '/features',
        component: PublicLayout,
        children: [
            {
                path: '',
                name: 'Features',
                component: () => import('@/pages/features/FeaturesPage.vue'),
            },
        ],
    },
    {
        path: '/about',
        component: PublicLayout,
        children: [
            {
                path: '',
                name: 'About',
                component: AboutPage,
            },
        ],
    },

    // Authenticated app routes
    {
        path: '/app',
        component: MainLayout,
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: DashboardPage,
            },
            {
                path: 'topics',
                name: 'Topics',
                component: TopicsListPage,
            },
            {
                path: 'topics/create',
                name: 'CreateTopic',
                component: () => import('@/pages/topics/CreatePage.vue'),
            },
            {
                path: 'topics/:id',
                name: 'TopicDetail',
                component: () => import('@/pages/topics/DetailPage.vue'),
            },
            {
                path: 'topics/:id/edit',
                name: 'EditTopic',
                component: () => import('@/pages/topics/EditPage.vue'),
            },
            {
                path: 'categories',
                name: 'Categories',
                component: () => import('@/pages/categories/ListPage.vue'),
            },
            {
                path: 'tasks',
                name: 'DailyTasks',
                component: DailyTasksPage,
            },
            {
                path: 'practice-logs',
                name: 'PracticeLogs',
                component: PracticeLogsPage,
            },
            {
                path: 'calendar',
                name: 'Calendar',
                component: CalendarPage,
            },
            {
                path: 'reports',
                name: 'Reports',
                component: ReportsPage,
            },
            {
                path: 'profile',
                name: 'Profile',
                component: ProfilePage,
            },
            {
                path: 'week',
                name: 'WeeklyPlan',
                component: () => import('@/pages/weekly/WeeklyPlanPage.vue'),
            },
            {
                path: 'mistakes',
                name: 'Mistakes',
                component: () => import('@/pages/mistakes/MistakesPage.vue'),
            },
            {
                path: 'guide',
                name: 'UserGuide',
                component: () => import('@/pages/guide/UserGuidePage.vue'),
            },
            {
                path: 'review',
                name: 'Review',
                component: () => import('@/pages/review/ReviewPage.vue'),
            },
            {
                path: 'revision-templates',
                name: 'RevisionTemplates',
                component: () => import('@/pages/settings/RevisionTemplatesPage.vue'),
            },
        ],
    },
    {
        path: '/auth',
        component: AuthLayout,
        meta: { requiresGuest: true },
        children: [
            {
                path: 'login',
                name: 'Login',
                component: LoginPage,
            },
            {
                path: 'register',
                name: 'Register',
                component: RegisterPage,
            },
            {
                path: 'verify-email',
                name: 'VerifyEmail',
                component: VerifyEmailPage,
            },
            {
                path: 'verify-error',
                name: 'VerifyError',
                component: VerifyErrorPage,
            },
            {
                path: 'forgot-password',
                name: 'ForgotPassword',
                component: ForgotPasswordPage,
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'NotFound',
        component: NotFoundPage,
    },
]

const router = createRouter({
    history: createWebHistory('/'),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        }
        // Deep links such as /features#references or /app/guide#review
        if (to.hash) {
            return { el: to.hash, top: 80, behavior: 'smooth' }
        }
        return { top: 0 }
    },
})

// Navigation guards
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()

    // Demo users clicking "Register" should be logged out first
    if (to.name === 'Register' && authStore.isDemoUser) {
        authStore.logout()
        next()
        return
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'Home' })
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        next({ name: 'Dashboard' })
    } else {
        next()
    }
})

export default router
