# Admin Vue Components - Quick Reference

## File Organization

```
resources/js/admin/
├── bootstrap.js          ← Handles Vue component mounting (included in @vite)
└── components/           ← Your Vue components go here
    ├── StatCard.vue
    ├── UsersList.vue
    ├── MetricsChart.vue
    └── ...
```

## Quick Start

### 1. Create Component

```vue
<!-- resources/js/admin/components/Hello.vue -->
<template>
    <div>{{ message }}</div>
</template>

<script setup>
import { defineProps } from "vue";
const props = defineProps({ message: String });
</script>
```

### 2. Use in Blade

```blade
<!-- resources/views/admin/dashboard.blade.php -->
<div data-vue-component="Hello" data-message="Welcome!"></div>

@push('scripts')
  @vite('resources/js/admin/bootstrap.js')
@endpush
```

## Common Patterns

### Simple Component (No Props)

```blade
<div data-vue-component="Dashboard"></div>
```

### With Props

```blade
<div data-vue-component="Card" data-title="Users" data-count="245"></div>
```

### Multiple Components

```blade
<div data-vue-component="StatCard" data-label="Users" data-value="245"></div>
<div data-vue-component="StatCard" data-label="Topics" data-value="1240"></div>
<div data-vue-component="UsersList"></div>
```

## Props Data Types

All data attributes are strings. Convert in component if needed:

```vue
<script setup>
const props = defineProps({
    title: String, // "Hello" → "Hello" ✓
    count: Number, // "245" → needs parseInt()
    isActive: Boolean, // "true" → needs JSON.parse()
});

// Computed for type conversion
const numCount = computed(() => Number(props.count));
</script>
```

## Always Remember

✅ Include `@vite('resources/js/admin/bootstrap.js')` in Blade file  
✅ Component filename must match `data-vue-component` value  
✅ Component must be in `resources/js/admin/components/`  
✅ Use `data-` attributes for props  
✅ Run `npm run dev` for changes to reflect

## Example: Real Admin Component

```vue
<!-- resources/js/admin/components/UserStats.vue -->
<template>
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-blue-100 p-4 rounded">
            <h3>{{ title }}</h3>
            <p class="text-2xl font-bold">{{ stats.total }}</p>
        </div>
        <div class="bg-green-100 p-4 rounded">
            <h3>Active</h3>
            <p class="text-2xl font-bold">{{ stats.active }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, defineProps, onMounted } from "vue";

const props = defineProps({
    title: String,
});

const stats = ref({ total: 0, active: 0 });

onMounted(async () => {
    const res = await fetch("/api/admin/user-stats");
    stats.value = await res.json();
});
</script>
```

```blade
<!-- resources/views/admin/dashboard.blade.php -->
<div data-vue-component="UserStats" data-title="User Statistics"></div>

@push('scripts')
  @vite('resources/js/admin/bootstrap.js')
@endpush
```

## Debugging

- Open browser DevTools (F12)
- Check Console for errors
- Look for "Component not found" warnings
- Verify component path: `resources/js/admin/components/NAME.vue`
- Check that bootstrap is included via `@vite()`
