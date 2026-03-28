# StudyTracker Frontend Documentation Index

Welcome! This guide helps you navigate all frontend documentation and get started quickly.

## 📖 Documentation Files

### **🚀 START HERE: `FRONTEND_QUICKSTART.md`**

- **5-minute setup guide**
- Installation in 3 steps
- How to set OAuth credentials
- Test credentials and features
- **Best for**: Getting running fast

### **✅ `FRONTEND_CHECKLIST.md`**

- **Complete verification checklist**
- Pre-installation requirements
- Step-by-step setup with expected outputs
- Troubleshooting guide for common issues
- Device testing instructions
- **Best for**: Ensuring everything works

### **📚 `FRONTEND_SETUP.md`**

- **Comprehensive technical documentation**
- Full project structure explained
- Technology stack details
- State management (Pinia) architecture
- API integration details
- Component conventions
- Development tips and best practices
- **Best for**: Understanding the codebase

### **🔌 `API-DOCUMENTATION.md`**

- **Backend REST API reference**
- All endpoints documented
- Request/response examples
- Authentication flow
- **Best for**: API integration questions

## 🎯 Choose Your Path

### "I just want to run it"

1. Read: `FRONTEND_QUICKSTART.md` (5 min)
2. Follow steps 1-3
3. Run `npm run dev`
4. Done! ✅

### "I want to make sure everything works"

1. Read: `FRONTEND_CHECKLIST.md`
2. Follow pre-installation checklist
3. Run installation steps
4. Go through verification checklist
5. Test on different devices
6. You're ready! ✅

### "I need to understand the codebase"

1. Read: `FRONTEND_SETUP.md` (full guide)
2. Understand project structure
3. Review Pinia stores
4. Explore state management
5. Check development tips
6. Now you can develop! ✅

### "I'm stuck / something's broken"

1. Check: `FRONTEND_CHECKLIST.md` troubleshooting
2. Look for your error message
3. Try suggested fix
4. Still broken? Check `FRONTEND_SETUP.md` for details
5. Or review `API-DOCUMENTATION.md` if it's API-related

## 🚀 Quick Command Reference

```bash
# Install dependencies
npm install

# Start development server (with hot reload)
npm run dev

# Build for production
npm run build

# Clear cache and reinstall
rm -r node_modules package-lock.json
npm install
```

## 📱 Key Features

✅ **Authentication** - Secure login with email verification
✅ **Dashboard** - Daily overview and stats
✅ **Topics** - Full CRUD management
✅ **Categories** - Organize topics
✅ **Daily Tasks** - Track study sessions
✅ **Practice Logs** - Record practice details
✅ **Calendar** - Monthly visualization
✅ **Responsive** - Mobile-first design
✅ **Fast** - Built with Vite

## 🛠️ Technology Stack

| Purpose   | Technology            |
| --------- | --------------------- |
| Framework | Vue 3 Composition API |
| Routing   | Vue Router v4         |
| State     | Pinia + persistence   |
| Styling   | Tailwind CSS          |
| Build     | Vite                  |
| HTTP      | Axios                 |
| Dates     | date-fns              |
| Backend   | Laravel REST API      |

## 📁 Project Structure

```
resources/
├── js/
│   ├── app.js              ← Entry point
│   ├── router.js           ← Routes
│   ├── stores/             ← Pinia state
│   ├── layouts/            ← Main & Auth layouts
│   ├── pages/              ← Page components
│   └── components/         ← Reusable components
└── css/
    └── app.css             ← Tailwind CSS
```

## 🔐 Authentication

### Demo Credentials

- **Email**: user@example.com
- **Password**: Password@123

### OAuth Setup

Need OAuth credentials?

1. **Get from admin panel**: `http://studytracker.test/admin`
2. **Add to `.env`**:
    ```env
    VITE_OAUTH_CLIENT_ID=your-id
    VITE_OAUTH_CLIENT_SECRET=your-secret
    ```
3. **Restart dev server**

## 📝 Common Tasks

### Create a New Page

1. Create `resources/js/pages/YourPage.vue`
2. Add route in `resources/js/router.js`
3. Import component in router

### Add New Store

1. Create `resources/js/stores/yourstore.js`
2. Define with `defineStore()`
3. Import with `useYourStore()`

### Style Component

- Use Tailwind classes: `class="text-lg font-bold text-primary-600"`
- Pre-defined utils in `resources/css/app.css` like `btn-primary`

### Make API Call

```javascript
const api = authStore.getApiClient();
const response = await api.get("/study/topics");
```

## 🔧 Development Environment

### Recommended Setup

- **Editor**: VS Code
- **Extensions**: Vue - Official, Tailwind CSS IntelliSense
- **Node**: v16+ (check: `node --version`)
- **npm**: v8+ (comes with Node)

### Hot Module Reload (HMR)

- Changes are live instantly
- No page refresh needed
- Very fast development experience

## 📞 Need Help?

1. **Read relevant doc** → Pick your path above
2. **Check troubleshooting** → `FRONTEND_CHECKLIST.md`
3. **Search keywords** → Use Ctrl+F on docs
4. **Review code** → Check `resources/js/pages/` for examples

## ✨ Pro Tips

1. **Use browser DevTools** → Components tab helps debug Vue
2. **Check localStorage** → Tokens stored at `auth` key
3. **Inspect Network** → See API calls in Network tab
4. **Use console** → Error messages help troubleshoot
5. **Restart when needed** → Many issues fixed by restarting dev server

## 🎓 Learning Resources

- [Vue 3 Guide](https://vuejs.org)
- [Vue Router](https://router.vuejs.org)
- [Pinia Docs](https://pinia.vuejs.org)
- [Tailwind CSS](https://tailwindcss.com)
- [Vite Guide](https://vitejs.dev)

## 🚀 Ready to Go!

You now have everything you need. Pick a documentation file above and get started!

**Happy coding! 🚀**

---

## Additional Quick Links

- API Endpoints: See `API-DOCUMENTATION.md`
- Postman Collection: `StudyTracker-API.postman_collection.json`
- Backend Docs: Check Laravel API-DOCUMENTATION.md
- Admin Panel: http://studytracker.test/admin
