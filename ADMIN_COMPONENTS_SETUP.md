# Admin Vue Components Setup

Admin Vue components are stored in `resources/js/admin/components/` and are mounted directly into Blade templates using the data attribute method.

## Existing Admin Components

The following admin-related Vue components have been organized in the admin directory:

- `role/RoleCreateModal.vue` - Create new role modal
- `role/RoleEditModal.vue` - Edit existing role modal
- `permission/PermissionCreateModal.vue` - Create new permission modal
- `settings/SettingsPage.vue` - Admin settings page component

## Directory Structure

```
resources/
├── js/admin/
│   ├── bootstrap.js          # Initializes Vue component mounting
│   └── components/           # Place Vue components here
│       ├── YourComponent.vue
│       ├── Another.vue
│       └── ...
└── views/admin/
    ├── dashboard.blade.php   # Blade templates that use Vue components
    ├── users/
    │   └── index.blade.php
    └── ...
```

## How to Use Admin Vue Components in Blade

### Step 1: Create a Vue Component

Create your Vue component in `resources/js/admin/components/`:

```vue
<!-- resources/js/admin/components/DashboardMetrics.vue -->
<template>
    <div class="card">
        <h3>{{ title }}</h3>
        <p>{{ metric }}</p>
    </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
    title: String,
    metric: Number,
});
</script>
```

### Step 2: Use in Blade Template

In your Blade template, add the component with `data-vue-component` attribute:

```blade
<!-- resources/views/admin/dashboard.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="container">
  <!-- Mount Vue component here -->
  <div id="metrics-container" data-vue-component="DashboardMetrics" data-title="Total Users" data-metric="245"></div>
</div>

@endsection

@push('scripts')
  @vite('resources/js/admin/bootstrap.js')
@endpush
```

### Step 3: Pass Props to Components

Props are passed via `data-*` attributes:

```blade
<!-- In Blade -->
<div data-vue-component="UserList" data-search="true" data-limit="10"></div>

<!-- In Vue component -->
<script setup>
import { defineProps } from 'vue'

const props = defineProps({
  search: Boolean,
  limit: Number,
})
</script>
```

## Complete Example

### Vue Component (Admin Stats)

```vue
<!-- resources/js/admin/components/StatCard.vue -->
<template>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm">{{ label }}</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ value }}</p>
                <p class="text-green-600 text-xs mt-2" v-if="change">
                    {{ change }}
                </p>
            </div>
            <div
                class="w-12 h-12 rounded-lg flex items-center justify-center"
                :style="{ backgroundColor: bgColor }"
            >
                <svg class="w-6 h-6" :style="{ color: iconColor }">
                    <!-- Icon here -->
                </svg>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
    label: String,
    value: Number,
    change: String,
    bgColor: { type: String, default: "#EFF6FF" },
    iconColor: { type: String, default: "#2563EB" },
});
</script>
```

### Blade Template Usage

```blade
<!-- resources/views/admin/dashboard.blade.php -->
@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto p-6">
  <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>

  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div data-vue-component="StatCard" data-label="Total Users" data-value="245" data-change="+12 this month" data-bg-color="#EFF6FF" data-icon-color="#2563EB"></div>

    <div data-vue-component="StatCard" data-label="Total Topics" data-value="1240" data-change="+85 this month" data-bg-color="#F0FDF4" data-icon-color="#16A34A"></div>

    <div data-vue-component="StatCard" data-label="Tasks Completed" data-value="8320" data-change="85% completion" data-bg-color="#ECFDF5" data-icon-color="#10B981"></div>

    <div data-vue-component="StatCard" data-label="Active Sessions" data-value="34" data-change="18 today" data-bg-color="#FFF7ED" data-icon-color="#EA580C"></div>
  </div>
</div>
@endsection

@push('scripts')
  @vite('resources/js/admin/bootstrap.js')
@endpush
```

## Important Notes

### 1. Include Bootstrap in Every Admin Blade File

At the bottom of each Blade template that uses Vue components, include:

```blade
@push('scripts')
  @vite('resources/js/admin/bootstrap.js')
@endpush
```

Or if you have a layout file, add it once:

```blade
<!-- resources/views/layouts/admin.blade.php -->
<!DOCTYPE html>
<html>
<head>
    @vite('resources/css/app.css')
</head>
<body>
    @yield('content')

    <!-- Include admin bootstrap for Vue components -->
    @vite('resources/js/admin/bootstrap.js')
</body>
</html>
```

### 2. Component Naming

The `data-vue-component` value must match the Vue component filename (without extension):

```
Component file: DashboardMetrics.vue
HTML attribute: data-vue-component="DashboardMetrics"
```

### 3. Props with Dashes

Data attributes with dashes are converted to camelCase in props:

```blade
<!-- Blade HTML -->
<div data-vue-component="MyComponent" data-user-name="John" data-is-active="true"></div>

<!-- Vue component -->
<script setup>
const props = defineProps({
  userName: String,   // from data-user-name
  isActive: Boolean,  // from data-is-active
})
</script>
```

### 4. Multiple Components on Same Page

You can use multiple Vue components on the same Blade page:

```blade
<div id="stats" data-vue-component="StatCard" data-label="Users"></div>
<div id="users" data-vue-component="UsersList"></div>
<div id="topics" data-vue-component="TopicsList"></div>
```

All will initialize when the page loads, as long as `@vite('resources/js/admin/bootstrap.js')` is included.

## Workflow

1. **Create Vue component** → `resources/js/admin/components/MyComponent.vue`
2. **Use in Blade** → `<div data-vue-component="MyComponent"></div>`
3. **Include bootstrap** → `@vite('resources/js/admin/bootstrap.js')`
4. **Run dev server** → `npm run dev`
5. **Test in browser** → Navigate to admin page

## Troubleshooting

### Component not showing

- ✅ Check component filename matches `data-vue-component` value
- ✅ Ensure `@vite('resources/js/admin/bootstrap.js')` is included
- ✅ Check browser console for errors
- ✅ Verify component is in `resources/js/admin/components/` directory

### Props not working

- ✅ Use `data-*` attribute names matching prop names
- ✅ Convert dashes to camelCase (data-user-name → userName)
- ✅ Data attributes are always strings; convert in component if needed

### Browser console shows "Component not found"

- ✅ Verify component filename (case-sensitive)
- ✅ Check that component is exported as default export
- ✅ Ensure file has `.vue` extension
