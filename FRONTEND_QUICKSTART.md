# 🚀 Quick Start Guide - StudyTracker Frontend

## Installation & Setup (5 minutes)

### Step 1: Install Dependencies

```bash
cd c:\laragon\www\StudyTracker
npm install
```

This will install:

- Vue 3 & Vue Router
- Pinia (state management)
- Tailwind CSS + components
- Axios for API calls
- date-fns for date handling

### Step 2: Start Development Server

```bash
npm run dev
```

This starts Vite dev server with hot reload:

- Frontend: http://localhost:5173
- API: http://studytracker.test/api

### Step 3: Build for Production

```bash
npm run build
```

Creates optimized build in `public/build/`

## 🔑 OAuth Setup (Important!)

Before you can login, you need to set OAuth credentials:

1. **Get credentials from Laravel admin:**
    - Login to your admin panel
    - Generate new OAuth Password Grant client
    - Note the Client ID and Client Secret

2. **Set in your app:**
   You have two options:

    **Option A: Via Environment (Recommended)**
    Add to `.env`:

    ```env
    VITE_OAUTH_CLIENT_ID=your-client-id
    VITE_OAUTH_CLIENT_SECRET=your-client-secret
    ```

    **Option B: Via Code**
    In `resources/js/App.vue`, add:

    ```javascript
    onMounted(() => {
        authStore.setClientCredentials("your-client-id", "your-client-secret");
    });
    ```

3. **Restart dev server** after changes

## 📖 Application Routes

### Public (No Auth Required)

- `/auth/login` - Login page
- `/auth/register` - Registration page
- `/auth/verify-email` - Email verification

### Private (Auth Required)

- `/` - Dashboard (main landing)
- `/topics` - Topics list
- `/topics/create` - Create new topic
- `/topics/:id` - Topic detail
- `/categories` - Categories management
- `/tasks` - Daily tasks
- `/practice-logs` - Practice logs
- `/calendar` - Calendar view
- `/profile` - User profile

## 🎯 Key Features

### Dashboard

- Daily stats and agenda
- Task completion tracking
- Date navigation

### Topics

- Full CRUD operations
- Category organization
- Difficulty levels
- Source links support

### Study Management

- Daily task view with status
- Practice log recording
- Calendar visualization

### Responsive Design

- Mobile-first approach
- Works on all screen sizes
- Touch-friendly interface

## 🧪 Test Credentials

Use these to test during development:

- **Email**: user@example.com
- **Password**: Password@123

Or create your own account via registration.

## 📱 Mobile Development

Test on mobile devices using:

```bash
# Get your machine IP
ipconfig

# Access from mobile: http://YOUR_IP:5173
```

## 🐛 Troubleshooting

### Port Already in Use

```bash
# Kill process on port 5173
netstat -ano | findstr :5173
taskkill /PID <PID> /F
```

### Styles Not Loading

```bash
# Clear cache and restart
rm node_modules/.vite
npm run dev
```

### API Connection Errors

1. Check `.env` has `VITE_API_URL=http://studytracker.test/api`
2. Verify Laravel API is running
3. Check CORS configuration in Laravel
4. Clear browser cache and cookies

### Token Issues

1. Clear localStorage: `localStorage.clear()`
2. Logout and login again
3. Regenerate OAuth credentials if needed

## 📚 Project Structure Reference

```
resources/
├── js/
│   ├── pages/          # Full page components
│   ├── layouts/        # Layout wrappers
│   ├── stores/         # Pinia state
│   ├── router.js       # Route definitions
│   └── app.js          # Entry point
└── css/
    └── app.css         # Tailwind + customs
```

## 🔧 Development Tips

### Add New Page

1. Create `resources/js/pages/YourPage.vue`
2. Add route in `resources/js/router.js`
3. Import in router
4. Access via navigation or URL

### Add New Store

1. Create `resources/js/stores/yourstore.js`
2. Define state, getters, actions
3. Import in components with `useYourStore()`

### Style Component

- Use Tailwind utility classes
- Add custom components in `resources/css/app.css` under `@layer components`
- Reference: `class="btn-primary"` (already defined)

### Modify API Integration

- Update stores in `resources/js/stores/`
- All API calls go through auth store's API client
- Tokens are automatically attached

## 🚀 Next Steps

1. ✅ Install & run dev server
2. ✅ Set OAuth credentials
3. ✅ Create a test account
4. ✅ Explore dashboard
5. ✅ Create a topic
6. ✅ Track a daily task
7. 🎉 Deploy to production when ready!

## 📞 Support

For API issues, check `API-DOCUMENTATION.md`
For frontend issues, refer to `FRONTEND_SETUP.md`

Happy studying! 📚
