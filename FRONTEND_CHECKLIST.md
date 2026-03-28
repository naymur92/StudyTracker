# StudyTracker Frontend - Setup Checklist

## ✅ Pre-Installation

- [ ] Node.js (v16+) installed
- [ ] npm or yarn package manager available
- [ ] Laravel backend running on http://studytracker.test
- [ ] Database configured and migrations run
- [ ] OAuth credentials generated (or available from admin panel)

## 🚀 Installation Steps

### Step 1: Install Dependencies

```bash
cd c:\laragon\www\StudyTracker
npm install
```

**Expected output**: All packages installed successfully (~2-3 minutes)

### Step 2: Configure OAuth Credentials

**Option A: Via Environment Variables (Recommended)**

1. Open `.env` file
2. Find or add these lines:
    ```env
    VITE_OAUTH_CLIENT_ID=your-client-id-here
    VITE_OAUTH_CLIENT_SECRET=your-client-secret-here
    ```
3. Replace with actual credentials from Laravel admin panel
4. Save file

**Option B: Via Admin Panel**

1. Login to http://studytracker.test/admin
2. Find OAuth section
3. Create new "Password Grant" client
4. Copy Client ID and Secret
5. Use in Option A above

### Step 3: Verify Backend API

```bash
# Check if API is accessible
curl http://studytracker.test/api/auth/token
```

Expected: Returns 422 (error about missing credentials) - this is OK, means API is running

### Step 4: Start Development Server

```bash
npm run dev
```

**Expected output:**

```
  VITE v7.x.x  build 0.00s
  ➜  Local:   http://localhost:5173
  ➜  Press q to stop
```

### Step 5: Open Application

1. Open browser: `http://localhost:5173`
2. Should redirect to login page
3. Login with demo credentials:
    - **Email**: user@example.com
    - **Password**: Password@123

## ✅ Verification Checklist

After login, verify these features work:

### Authentication

- [ ] Login page loads with demo credentials shown
- [ ] Login successful → redirects to dashboard
- [ ] Logout button works
- [ ] Refresh page → still logged in (token persisted)
- [ ] Clear localStorage → redirected to login

### Dashboard

- [ ] Dashboard loads with stats
- [ ] Date navigation works (previous/next day)
- [ ] "Today" button works
- [ ] Can see tasks if any exist

### Navigation

- [ ] Sidebar visible on desktop (left side)
- [ ] Mobile: hamburger menu appears
- [ ] All menu items clickable
- [ ] Links to: Topics, Categories, Tasks, Practice Logs, Calendar, Profile

### Topics

- [ ] Topics page loads
- [ ] Can create new topic
- [ ] Search functionality works
- [ ] Status filter works
- [ ] Can view topic details
- [ ] Can delete topic

### Categories

- [ ] Categories load
- [ ] Can create category with custom color
- [ ] Can delete category

### Other Features

- [ ] Daily tasks page works
- [ ] Calendar view shows month
- [ ] Practice logs page works
- [ ] Profile page shows user info

## 🐛 Troubleshooting

### Issue: "Cannot GET /localhost:5173"

**Solution**: Make sure npm run dev started and terminal shows "Local: http://localhost:5173"

### Issue: Login fails with "Network Error"

**Solutions**:

1. Check `.env` has correct `VITE_API_URL=http://studytracker.test/api`
2. Verify Laravel API is running
3. Check OAuth credentials are set correctly
4. Check Laravel CORS config allows frontend origin

### Issue: "CORS error" in console

**Solutions**:

1. In Laravel `config/cors.php`, ensure frontend URL is in allowed origins
2. Restart Laravel dev server
3. Clear browser cache (Ctrl+Shift+Delete)

### Issue: OAuth credentials error

**Solutions**:

1. Generate new OAuth client in admin panel
2. Verify credentials in `.env` are correct (no extra spaces)
3. Check client is "Personal Access Client" or "Password Grant"
4. Restart dev server after changing `.env`

### Issue: Styles not loading (page looks ugly)

**Solutions**:

1. Restart dev server: `npm run dev`
2. Clear browser cache
3. Check Tailwind is initialized: `npm run dev` should show no errors

### Issue: Components not found / "Cannot find module"

**Solutions**:

1. Install dependencies again: `npm install`
2. Delete node_modules and package-lock.json: `rm -r node_modules package-lock.json`
3. Run: `npm install`

### Issue: Port 5173 already in use

**Solutions**:

```bash
# Windows: Find and kill process
netstat -ano | findstr :5173
taskkill /PID <PID> /F
```

### Issue: API calls fail with 401 Unauthorized

**Solutions**:

1. Try logging out and logging in again
2. Check token is in localStorage
3. Verify token expiration time
4. Generate new access token

## 📱 Testing on Different Devices

### Mobile Testing (via network)

```bash
# Get your machine IP (Windows)
ipconfig

# From mobile on same network, visit:
# http://YOUR_IP:5173
```

### Test Checklist

- [ ] Hamburger menu works on mobile
- [ ] Content readable on phone
- [ ] Buttons are touch-friendly (large enough)
- [ ] No horizontal scrolling
- [ ] Forms work on mobile keyboard

## 🎉 You're Ready!

If all checks pass, your StudyTracker frontend is working correctly!

### Next Steps

1. Create more test topics
2. Try creating study tasks
3. Log practice sessions
4. Explore calendar view
5. Build for production when ready:
    ```bash
    npm run build
    ```

## 📞 Support

**For API issues**: Check `API-DOCUMENTATION.md`
**For frontend issues**: Check `FRONTEND_SETUP.md`
**For quick help**: See `FRONTEND_QUICKSTART.md`

## 🚢 Production Deployment

When ready to deploy:

```bash
# Build optimized bundle
npm run build

# Output files go to: public/build/
# Serve through Laravel routes configured in routes/web.php
```

Deploy your app and enjoy studying! 📚✨
