# StudyTracker Frontend - Vue.js Application

A mobile-first, responsive Vue 3 frontend application for the StudyTracker REST API. Features a modern, clean design using Tailwind CSS with complete study management functionality.

## 🎯 Features

### Authentication

- User registration with email verification
- Secure login with Passport OAuth
- Token-based authentication
- Session persistence

### Dashboard

- Daily overview with statistics
- Task progress tracking
- Daily agenda view
- Date navigation

### Topics Management

- Create, read, update, delete topics
- Organize topics by categories
- Difficulty levels (easy, medium, hard)
- Source links and notes
- Topic detail view with related tasks

### Categories

- Manage study categories with custom colors and icons
- Organize topics by category
- Visual category representation

### Study Tasks

- View daily study tasks
- Mark tasks as complete/skip
- Task filtering and organization
- Task status tracking

### Practice Logs

- Record practice sessions
- Track practice types (problem solving, implementation, reading, etc.)
- Duration and outcome tracking
- Date-based filtering

### Calendar View

- Monthly calendar view
- Task visualization
- Easy date navigation

## 🛠️ Technology Stack

- **Framework**: Vue 3 with Composition API
- **State Management**: Pinia with persistence
- **Routing**: Vue Router v4
- **Styling**: Tailwind CSS 3
- **UI Components**: Headless UI + Hero Icons
- **HTTP Client**: Axios
- **Date Handling**: date-fns
- **Build Tool**: Vite
- **Backend Integration**: Laravel REST API

## 📋 Project Structure

```
resources/
├── css/
│   └── app.css                 # Tailwind CSS entry point
├── js/
│   ├── app.js                  # Vue app entry point
│   ├── router.js               # Vue Router configuration
│   ├── App.vue                 # Root component
│   ├── stores/                 # Pinia stores
│   │   ├── auth.js
│   │   ├── user.js
│   │   ├── topics.js
│   │   ├── categories.js
│   │   ├── tasks.js
│   │   └── practiceLogs.js
│   ├── layouts/                # Layout components
│   │   ├── MainLayout.vue
│   │   └── AuthLayout.vue
│   └── pages/                  # Page components
│       ├── auth/
│       │   ├── LoginPage.vue
│       │   ├── RegisterPage.vue
│       │   └── VerifyEmailPage.vue
│       ├── topics/
│       │   ├── ListPage.vue
│       │   ├── DetailPage.vue
│       │   └── CreatePage.vue
│       ├── categories/
│       │   └── ListPage.vue
│       ├── tasks/
│       │   └── DailyPage.vue
│       ├── practice-logs/
│       │   └── ListPage.vue
│       ├── calendar/
│       │   └── CalendarPage.vue
│       ├── DashboardPage.vue
│       ├── ProfilePage.vue
│       └── NotFoundPage.vue
└── views/
    └── app.blade.php           # Laravel Blade entry point
```

## 🚀 Getting Started

### 1. Install Dependencies

```bash
npm install
```

### 2. Environment Configuration

Ensure your `.env` file has:

```env
VITE_API_URL=http://studytracker.test/api
```

### 3. Set OAuth Credentials

Before logging in, you need to configure OAuth credentials in the store. Edit the auth store initialization or get them from your Laravel admin panel.

### 4. Run Development Server

```bash
npm run dev
```

The Vite dev server will start at `http://localhost:5173` with hot module reload enabled.

### 5. Build for Production

```bash
npm run build
```

This creates optimized production builds in `public/build/`.

## 🎨 Design System

### Color Palette

- **Primary**: Blue (professional, trust)
- **Success**: Green (completion, positive)
- **Warning**: Yellow (attention)
- **Error**: Red (danger, deletion)

### Typography

- **Font**: Inter (system fallback for web fonts)
- **Sizes**: Responsive from mobile to desktop

### Responsive Breakpoints

- **Mobile**: < 768px (md)
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

## 📱 Mobile-First Design

The entire application is built with mobile-first principles:

- Touch-friendly interface with larger tap targets
- Adaptive layouts that scale with screen size
- Responsive navigation (hamburger menu on mobile)
- Optimized performance for mobile networks

## 🔐 Authentication Flow

1. **Register**: User creates account (initially inactive)
2. **Verify**: Verification email sent, user clicks link
3. **Login**: User signs in with verified account
4. **Token**: Receives JWT access token and refresh token
5. **Auth Guard**: All authenticated routes check for valid token
6. **Refresh**: Automatic token refresh when expired

## 🔗 API Integration

All API calls use Axios with automatic bearer token injection:

```javascript
const api = authStore.getApiClient();
const response = await api.get("/study/dashboard");
```

### API Endpoints Used

- `POST /api/auth/register` - Register user
- `POST /api/auth/token` - Get access token
- `POST /api/auth/token/refresh` - Refresh token
- `GET /api/study/dashboard` - Dashboard data
- `GET/POST /api/study/topics` - Topic management
- `GET/POST /api/study/categories` - Category management
- `GET /api/study/daily-tasks` - Daily tasks
- `POST/PUT /api/study/tasks/{id}/complete` - Complete task
- `GET/POST /api/study/practice-logs` - Practice logs
- `GET /api/user` - User profile

## 📊 State Management (Pinia)

Each store handles its domain:

### Auth Store

- User authentication state
- Token management
- API client setup

### User Store

- User profile data
- User statistics

### Topic Store

- Topics list and filtering
- Topic CRUD operations
- Pagination

### Category Store

- Categories list
- Category CRUD operations

### Task Store

- Daily tasks
- Task status updates
- Task rescheduling

### Practice Log Store

- Practice logs list
- Log filtering and pagination
- Create/update/delete logs

## 🧪 Testing

(To be implemented)

## 📝 Conventions

### Component Names

- Page components: PascalCase.vue (e.g., `DashboardPage.vue`)
- Layout components: PascalCase.vue (e.g., `MainLayout.vue`)
- Reusable components: PascalCase.vue (e.g., `TaskCard.vue`)

### File Organization

- `pages/`: Full page components (routable)
- `layouts/`: Layout wrapper components
- `components/`: Reusable components (to be created)
- `stores/`: Pinia state management
- `services/`: (Optional) API service utilities

### Styling

- Use Tailwind utility classes
- Custom CSS in `@layer components` for reusable patterns
- Responsive design with mobile-first approach
- Color palette from Tailwind config

## 🐛 Common Issues

### CORS Issues

Ensure your API server allows requests from the frontend URL:

```php
// config/cors.php
'allowed_origins' => ['http://studytracker.test'],
```

### Token Expiration

The auth store handles refresh tokens automatically. If issues persist, clear localStorage and re-login.

### Styling Not Applied

Run `npm run build` after modifying Tailwind config, or restart the dev server.

## 📚 Resources

- [Vue 3 Documentation](https://vuejs.org)
- [Vue Router Guide](https://router.vuejs.org)
- [Pinia Documentation](https://pinia.vuejs.org)
- [Tailwind CSS](https://tailwindcss.com)
- [Axios Documentation](https://axios-http.com)

## 🤝 Contributing

When adding new features:

1. Create feature branch
2. Follow the project structure
3. Use mobile-first responsive design
4. Test on multiple screen sizes
5. Update this README if needed

## 📄 License

Part of the StudyTracker application.
