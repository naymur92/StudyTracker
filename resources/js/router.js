import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layout components
import MainLayout from '@/layouts/MainLayout.vue'
import AuthLayout from '@/layouts/AuthLayout.vue'

// Pages
import LoginPage from '@/pages/auth/LoginPage.vue'
import RegisterPage from '@/pages/auth/RegisterPage.vue'
import VerifyEmailPage from '@/pages/auth/VerifyEmailPage.vue'
import ForgotPasswordPage from '@/pages/auth/ForgotPasswordPage.vue'
import DashboardPage from '@/pages/DashboardPage.vue'
import TopicsListPage from '@/pages/topics/ListPage.vue'
import TopicDetailPage from '@/pages/topics/DetailPage.vue'
import TopicCreatePage from '@/pages/topics/CreatePage.vue'
import TopicEditPage from '@/pages/topics/EditPage.vue'
import CategoriesPage from '@/pages/categories/ListPage.vue'
import DailyTasksPage from '@/pages/tasks/DailyPage.vue'
import PracticeLogsPage from '@/pages/practice-logs/ListPage.vue'
import CalendarPage from '@/pages/calendar/CalendarPage.vue'
import ProfilePage from '@/pages/ProfilePage.vue'
import RevisionTemplatesPage from '@/pages/settings/RevisionTemplatesPage.vue'
import NotFoundPage from '@/pages/NotFoundPage.vue'

const routes = [
    {
        path: '/',
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
                component: TopicCreatePage,
            },
            {
                path: 'topics/:id',
                name: 'TopicDetail',
                component: TopicDetailPage,
            },
            {
                path: 'topics/:id/edit',
                name: 'EditTopic',
                component: TopicEditPage,
            },
            {
                path: 'categories',
                name: 'Categories',
                component: CategoriesPage,
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
                path: 'profile',
                name: 'Profile',
                component: ProfilePage,
            },
            {
                path: 'revision-templates',
                name: 'RevisionTemplates',
                component: RevisionTemplatesPage,
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
    scrollBehavior() {
        return { top: 0 }
    },
})

// Navigation guards
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'Login', query: { redirect: to.fullPath } })
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        next({ name: 'Dashboard' })
    } else {
        next()
    }
})

export default router
