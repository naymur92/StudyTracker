<template>
    <div class="icon-picker relative">
        <!-- Input with trigger button -->
        <div class="flex items-center gap-2">
            <input :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" type="text"
                placeholder="Icon class or click button..." class="input-base flex-1" @focus="showDropdown = true" />
            <button type="button" @click="showDropdown = !showDropdown"
                class="px-4 py-2 bg-primary-100 text-primary-600 hover:bg-primary-200 rounded-lg transition-colors flex items-center gap-2 flex-shrink-0">
                <span :class="getIconClass(modelValue)" class="text-lg"></span>
                <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showDropdown }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
            </button>
        </div>

        <!-- Dropdown - Mobile responsive -->
        <div v-if="showDropdown"
            class="fixed md:absolute top-auto md:top-full bottom-0 md:bottom-auto left-0 right-0 md:left-0 md:right-0 md:mt-2 z-50 bg-white border border-gray-200 rounded-t-lg md:rounded-lg shadow-lg p-4 max-h-[70vh] md:max-h-96 w-full md:min-w-[350px]">
            <!-- Search bar -->
            <input v-model="searchQuery" type="text" placeholder="Search icons..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4 focus:outline-none focus:ring-2 focus:ring-primary-500" />

            <!-- Icon grid - 4 columns responsive -->
            <div class="grid grid-cols-4 gap-2 overflow-y-auto" style="max-height: calc(70vh - 120px);">
                <button v-for="icon in filteredIcons" :key="icon.id" type="button" @click="selectIcon(icon)" :class="[
                'p-2 md:p-3 rounded-lg border-2 transition-all hover:border-primary-500 hover:bg-primary-50',
                modelValue.includes(icon.id)
                    ? 'border-primary-500 bg-primary-50 text-primary-600'
                    : 'border-gray-200 text-gray-700 hover:text-primary-600'
            ]" :title="icon.name" class="flex flex-col items-center gap-1 group">
                    <span :class="getIconClass(icon.icon)" class="text-xl md:text-2xl"></span>
                    <span class="text-xs text-center truncate w-full group-hover:text-primary-600">{{ icon.id }}</span>
                </button>
            </div>

            <!-- Display count -->
            <div class="mt-4 text-xs text-gray-500 text-center">
                Showing {{ filteredIcons.length }} of {{ icons.length }} icons
            </div>
        </div>

        <!-- Click outside to close -->
        <div v-if="showDropdown" class="fixed inset-0 z-40" @click="showDropdown = false"></div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['update:modelValue'])

const showDropdown = ref(false)
const searchQuery = ref('')

// Comprehensive Font Awesome icon dataset (~180 most commonly used icons for study/productivity apps)
const icons = ref([
    // Study & Learning
    { id: 'book', name: 'Book', icon: 'fas fa-book' },
    { id: 'book-open', name: 'Book Open', icon: 'fas fa-book-open' },
    { id: 'book-reader', name: 'Book Reader', icon: 'fas fa-book-reader' },
    { id: 'graduation-cap', name: 'Graduation Cap', icon: 'fas fa-graduation-cap' },
    { id: 'pencil', name: 'Pencil', icon: 'fas fa-pencil' },
    { id: 'pencil-alt', name: 'Pencil Alt', icon: 'fas fa-pen' },
    { id: 'highlighter', name: 'Highlighter', icon: 'fas fa-highlighter' },
    { id: 'lightbulb', name: 'Lightbulb', icon: 'fas fa-lightbulb' },
    { id: 'brain', name: 'Brain', icon: 'fas fa-brain' },
    { id: 'flask-vial', name: 'Flask', icon: 'fas fa-flask-vial' },
    { id: 'microscope', name: 'Microscope', icon: 'fas fa-microscope' },
    { id: 'beaker', name: 'Beaker', icon: 'fas fa-flask' },
    { id: 'book-bookmark', name: 'Book Bookmark', icon: 'fas fa-book-bookmark' },
    { id: 'scroll', name: 'Scroll', icon: 'fas fa-scroll' },
    { id: 'chalkboard', name: 'Chalkboard', icon: 'fas fa-chalkboard' },
    { id: 'chart-pie', name: 'Chart Pie', icon: 'fas fa-chart-pie' },

    // Organization
    { id: 'folder', name: 'Folder', icon: 'fas fa-folder' },
    { id: 'folder-open', name: 'Folder Open', icon: 'fas fa-folder-open' },
    { id: 'folder-plus', name: 'Folder Plus', icon: 'fas fa-folder-plus' },
    { id: 'folder-minus', name: 'Folder Minus', icon: 'fas fa-folder-minus' },
    { id: 'bookmark', name: 'Bookmark', icon: 'fas fa-bookmark' },
    { id: 'tag', name: 'Tag', icon: 'fas fa-tag' },
    { id: 'tags', name: 'Tags', icon: 'fas fa-tags' },
    { id: 'list', name: 'List', icon: 'fas fa-list' },
    { id: 'list-ul', name: 'List UL', icon: 'fas fa-list-ul' },
    { id: 'list-ol', name: 'List OL', icon: 'fas fa-list-ol' },
    { id: 'list-check', name: 'List Check', icon: 'fas fa-list-check' },
    { id: 'indent', name: 'Indent', icon: 'fas fa-indent' },
    { id: 'outdent', name: 'Outdent', icon: 'fas fa-outdent' },
    { id: 'inbox', name: 'Inbox', icon: 'fas fa-inbox' },
    { id: 'file', name: 'File', icon: 'fas fa-file' },
    { id: 'file-pdf', name: 'File PDF', icon: 'fas fa-file-pdf' },
    { id: 'file-word', name: 'File Word', icon: 'fas fa-file-word' },
    { id: 'file-excel', name: 'File Excel', icon: 'fas fa-file-excel' },
    { id: 'file-powerpoint', name: 'File PowerPoint', icon: 'fas fa-file-powerpoint' },
    { id: 'file-lines', name: 'File Lines', icon: 'fas fa-file-lines' },
    { id: 'clipboard', name: 'Clipboard', icon: 'fas fa-clipboard' },
    { id: 'clipboard-check', name: 'Clipboard Check', icon: 'fas fa-clipboard-check' },
    { id: 'clipboard-list', name: 'Clipboard List', icon: 'fas fa-clipboard-list' },

    // Social & Users
    { id: 'user', name: 'User', icon: 'fas fa-user' },
    { id: 'users', name: 'Users', icon: 'fas fa-users' },
    { id: 'user-group', name: 'User Group', icon: 'fas fa-people-group' },
    { id: 'user-plus', name: 'User Plus', icon: 'fas fa-user-plus' },
    { id: 'user-minus', name: 'User Minus', icon: 'fas fa-user-minus' },
    { id: 'user-check', name: 'User Check', icon: 'fas fa-user-check' },
    { id: 'user-clock', name: 'User Clock', icon: 'fas fa-user-clock' },
    { id: 'user-circle', name: 'User Circle', icon: 'fas fa-user-circle' },
    { id: 'user-tie', name: 'User Tie', icon: 'fas fa-user-tie' },
    { id: 'user-graduate', name: 'User Graduate', icon: 'fas fa-user-graduate' },
    { id: 'user-secret', name: 'User Secret', icon: 'fas fa-user-secret' },
    { id: 'users-line', name: 'Users Line', icon: 'fas fa-people-line' },

    // Achievements & Goals
    { id: 'star', name: 'Star', icon: 'fas fa-star' },
    { id: 'star-half', name: 'Star Half', icon: 'fas fa-star-half' },
    { id: 'star-half-stroke', name: 'Star Half Stroke', icon: 'fas fa-star-half-stroke' },
    // { id: 'stars', name: 'Stars', icon: 'fas fa-stars' },
    { id: 'check', name: 'Check', icon: 'fas fa-check' },
    { id: 'check-double', name: 'Check Double', icon: 'fas fa-check-double' },
    { id: 'check-circle', name: 'Check Circle', icon: 'fas fa-circle-check' },
    { id: 'xmark', name: 'X Mark', icon: 'fas fa-xmark' },
    { id: 'xmark-circle', name: 'X Mark Circle', icon: 'fas fa-circle-xmark' },
    { id: 'trophy', name: 'Trophy', icon: 'fas fa-trophy' },
    { id: 'medal', name: 'Medal', icon: 'fas fa-medal' },
    { id: 'award', name: 'Award', icon: 'fas fa-award' },
    { id: 'crown', name: 'Crown', icon: 'fas fa-crown' },
    { id: 'thumbs-up', name: 'Thumbs Up', icon: 'fas fa-thumbs-up' },
    { id: 'thumbs-down', name: 'Thumbs Down', icon: 'fas fa-thumbs-down' },
    { id: 'handshake', name: 'Handshake', icon: 'fas fa-handshake' },

    // Time & Calendar
    { id: 'calendar', name: 'Calendar', icon: 'fas fa-calendar' },
    { id: 'calendar-days', name: 'Calendar Days', icon: 'fas fa-calendar-days' },
    { id: 'calendar-alt', name: 'Calendar Alt', icon: 'fas fa-calendar-alt' },
    { id: 'calendar-check', name: 'Calendar Check', icon: 'fas fa-calendar-check' },
    { id: 'calendar-plus', name: 'Calendar Plus', icon: 'fas fa-calendar-plus' },
    { id: 'calendar-minus', name: 'Calendar Minus', icon: 'fas fa-calendar-minus' },
    { id: 'calendar-week', name: 'Calendar Week', icon: 'fas fa-calendar-week' },
    { id: 'clock', name: 'Clock', icon: 'fas fa-clock' },
    { id: 'clock-eight', name: 'Clock Eight', icon: 'fas fa-clock' },
    { id: 'hourglass', name: 'Hourglass', icon: 'fas fa-hourglass' },
    { id: 'hourglass-start', name: 'Hourglass Start', icon: 'fas fa-hourglass-start' },
    { id: 'hourglass-end', name: 'Hourglass End', icon: 'fas fa-hourglass-end' },
    { id: 'history', name: 'History', icon: 'fas fa-history' },
    { id: 'stopwatch', name: 'Stopwatch', icon: 'fas fa-stopwatch' },
    // { id: 'timer', name: 'Timer', icon: 'fas fa-timer' },

    // Actions & UI
    { id: 'search', name: 'Search', icon: 'fas fa-search' },
    { id: 'magnifying-glass', name: 'Magnifying Glass', icon: 'fas fa-magnifying-glass' },
    { id: 'filter', name: 'Filter', icon: 'fas fa-filter' },
    // { id: 'funnel', name: 'Funnel', icon: 'fas fa-funnel' },
    { id: 'edit', name: 'Edit', icon: 'fas fa-edit' },
    { id: 'pencil-square', name: 'Pencil Square', icon: 'fas fa-pencil-square' },
    { id: 'pen-to-square', name: 'Pen to Square', icon: 'fas fa-pen-to-square' },
    { id: 'trash', name: 'Trash', icon: 'fas fa-trash' },
    { id: 'delete', name: 'Delete', icon: 'fas fa-delete-left' },
    { id: 'download', name: 'Download', icon: 'fas fa-download' },
    { id: 'upload', name: 'Upload', icon: 'fas fa-upload' },
    { id: 'share', name: 'Share', icon: 'fas fa-share' },
    { id: 'share-alt', name: 'Share Alt', icon: 'fas fa-share-alt' },
    { id: 'share-nodes', name: 'Share Nodes', icon: 'fas fa-share-nodes' },
    { id: 'print', name: 'Print', icon: 'fas fa-print' },
    { id: 'copy', name: 'Copy', icon: 'fas fa-copy' },
    { id: 'paste', name: 'Paste', icon: 'fas fa-paste' },
    { id: 'redo', name: 'Redo', icon: 'fas fa-redo' },
    { id: 'undo', name: 'Undo', icon: 'fas fa-undo' },
    { id: 'refresh', name: 'Refresh', icon: 'fas fa-refresh' },
    { id: 'sync', name: 'Sync', icon: 'fas fa-sync' },

    // Navigation & Direction
    { id: 'arrow-right', name: 'Arrow Right', icon: 'fas fa-arrow-right' },
    { id: 'arrow-left', name: 'Arrow Left', icon: 'fas fa-arrow-left' },
    { id: 'arrow-up', name: 'Arrow Up', icon: 'fas fa-arrow-up' },
    { id: 'arrow-down', name: 'Arrow Down', icon: 'fas fa-arrow-down' },
    // { id: 'arrow-turn-right', name: 'Arrow Turn Right', icon: 'fas fa-arrow-turn-right' },
    { id: 'arrow-turn-down', name: 'Arrow Turn Down', icon: 'fas fa-arrow-turn-down' },
    { id: 'chevron-right', name: 'Chevron Right', icon: 'fas fa-chevron-right' },
    { id: 'chevron-left', name: 'Chevron Left', icon: 'fas fa-chevron-left' },
    { id: 'chevron-up', name: 'Chevron Up', icon: 'fas fa-chevron-up' },
    { id: 'chevron-down', name: 'Chevron Down', icon: 'fas fa-chevron-down' },
    { id: 'angles-right', name: 'Angles Right', icon: 'fas fa-angles-right' },
    { id: 'angles-left', name: 'Angles Left', icon: 'fas fa-angles-left' },
    { id: 'angles-up', name: 'Angles Up', icon: 'fas fa-angles-up' },
    { id: 'angles-down', name: 'Angles Down', icon: 'fas fa-angles-down' },

    // Technology & Code
    { id: 'code', name: 'Code', icon: 'fas fa-code' },
    { id: 'code-branch', name: 'Code Branch', icon: 'fas fa-code-branch' },
    { id: 'code-compare', name: 'Code Compare', icon: 'fas fa-code-compare' },
    { id: 'code-merge', name: 'Code Merge', icon: 'fas fa-code-merge' },
    // { id: 'git', name: 'Git', icon: 'fas fa-git' },
    { id: 'github', name: 'GitHub', icon: 'fab fa-github' },
    { id: 'laptop', name: 'Laptop', icon: 'fas fa-laptop' },
    { id: 'desktop', name: 'Desktop', icon: 'fas fa-desktop' },
    { id: 'tablet', name: 'Tablet', icon: 'fas fa-tablet' },
    { id: 'mobile', name: 'Mobile', icon: 'fas fa-mobile' },
    { id: 'database', name: 'Database', icon: 'fas fa-database' },
    { id: 'server', name: 'Server', icon: 'fas fa-server' },
    { id: 'terminal', name: 'Terminal', icon: 'fas fa-terminal' },
    { id: 'computer', name: 'Computer', icon: 'fas fa-computer' },
    { id: 'wifi', name: 'WiFi', icon: 'fas fa-wifi' },

    // Objects & Symbols
    { id: 'puzzle', name: 'Puzzle', icon: 'fas fa-puzzle-piece' },
    { id: 'cubes', name: 'Cubes', icon: 'fas fa-cubes' },
    { id: 'cube', name: 'Cube', icon: 'fas fa-cube' },
    { id: 'dice', name: 'Dice', icon: 'fas fa-dice' },
    { id: 'chart-line', name: 'Chart Line', icon: 'fas fa-chart-line' },
    { id: 'chart-bar', name: 'Chart Bar', icon: 'fas fa-chart-bar' },
    { id: 'chart-area', name: 'Chart Area', icon: 'fas fa-chart-area' },
    { id: 'chart-column', name: 'Chart Column', icon: 'fas fa-chart-column' },
    { id: 'graph-up', name: 'Graph Up', icon: 'fas fa-arrow-trend-up' },
    { id: 'graph-down', name: 'Graph Down', icon: 'fas fa-arrow-trend-down' },
    { id: 'bell', name: 'Bell', icon: 'fas fa-bell' },
    { id: 'bell-slash', name: 'Bell Slash', icon: 'fas fa-bell-slash' },
    { id: 'heart', name: 'Heart', icon: 'fas fa-heart' },
    { id: 'heart-pulse', name: 'Heart Pulse', icon: 'fas fa-heart-pulse' },
    { id: 'circle', name: 'Circle', icon: 'fas fa-circle' },
    { id: 'circle-dot', name: 'Circle Dot', icon: 'fas fa-circle-dot' },
    { id: 'square', name: 'Square', icon: 'fas fa-square' },
    // { id: 'rectangle', name: 'Rectangle', icon: 'fas fa-rectangle' },
    { id: 'diamond', name: 'Diamond', icon: 'fas fa-diamond' },
    { id: 'hexagon', name: 'Hexagon', icon: 'fas fa-hexagon' },
    // { id: 'triangle', name: 'Triangle', icon: 'fas fa-triangle' },

    // Communication
    { id: 'envelope', name: 'Envelope', icon: 'fas fa-envelope' },
    { id: 'envelope-open', name: 'Envelope Open', icon: 'fas fa-envelope-open' },
    { id: 'envelope-circle-check', name: 'Envelope Check', icon: 'fas fa-envelope-circle-check' },
    { id: 'comment', name: 'Comment', icon: 'fas fa-comment' },
    { id: 'comments', name: 'Comments', icon: 'fas fa-comments' },
    { id: 'comment-dots', name: 'Comment Dots', icon: 'fas fa-comment-dots' },
    { id: 'message', name: 'Message', icon: 'fas fa-message' },
    { id: 'phone', name: 'Phone', icon: 'fas fa-phone' },
    { id: 'phone-slash', name: 'Phone Slash', icon: 'fas fa-phone-slash' },

    // Status & Indicators
    { id: 'circle-exclamation', name: 'Exclamation', icon: 'fas fa-circle-exclamation' },
    { id: 'triangle-exclamation', name: 'Warning', icon: 'fas fa-triangle-exclamation' },
    { id: 'circle-info', name: 'Info', icon: 'fas fa-circle-info' },
    { id: 'circle-question', name: 'Question', icon: 'fas fa-circle-question' },
    { id: 'circle-plus', name: 'Circle Plus', icon: 'fas fa-circle-plus' },
    { id: 'circle-minus', name: 'Circle Minus', icon: 'fas fa-circle-minus' },
    { id: 'square-check', name: 'Square Check', icon: 'fas fa-square-check' },
    { id: 'square-minus', name: 'Square Minus', icon: 'fas fa-square-minus' },
    { id: 'square-plus', name: 'Square Plus', icon: 'fas fa-square-plus' },

    // Activity
    { id: 'running', name: 'Running', icon: 'fas fa-person-running' },
    { id: 'walking', name: 'Walking', icon: 'fas fa-person-walking' },
    { id: 'person-hiking', name: 'Hiking', icon: 'fas fa-person-hiking' },
    { id: 'person-biking', name: 'Biking', icon: 'fas fa-person-biking' },
    { id: 'fire', name: 'Fire', icon: 'fas fa-fire' },
    { id: 'bolt', name: 'Bolt', icon: 'fas fa-bolt' },
    { id: 'wind', name: 'Wind', icon: 'fas fa-wind' },

    // Nature
    { id: 'leaf', name: 'Leaf', icon: 'fas fa-leaf' },
    { id: 'tree', name: 'Tree', icon: 'fas fa-tree' },
    { id: 'clover', name: 'Clover', icon: 'fas fa-clover' },
    { id: 'sun', name: 'Sun', icon: 'fas fa-sun' },
    { id: 'moon', name: 'Moon', icon: 'fas fa-moon' },
    { id: 'cloud', name: 'Cloud', icon: 'fas fa-cloud' },
    { id: 'cloud-sun', name: 'Cloud Sun', icon: 'fas fa-cloud-sun' },
    { id: 'cloud-rain', name: 'Cloud Rain', icon: 'fas fa-cloud-rain' },
    { id: 'mountain', name: 'Mountain', icon: 'fas fa-mountain' },

    // Objects & More
    // { id: 'gift', name: 'Gift', icon: 'fas fa-gift' },
    // { id: 'gift-open', name: 'Gift Open', icon: 'fas fa-gift-open' },
    { id: 'gem', name: 'Gem', icon: 'fas fa-gem' },
    { id: 'ring', name: 'Ring', icon: 'fas fa-ring' },
    { id: 'key', name: 'Key', icon: 'fas fa-key' },
    { id: 'lock', name: 'Lock', icon: 'fas fa-lock' },
    { id: 'lock-open', name: 'Lock Open', icon: 'fas fa-lock-open' },
    { id: 'unlock', name: 'Unlock', icon: 'fas fa-unlock' },
    { id: 'shield', name: 'Shield', icon: 'fas fa-shield' },
    // { id: 'shield-check', name: 'Shield Check', icon: 'fas fa-shield-check' },
    // { id: 'sword', name: 'Sword', icon: 'fas fa-sword' },
    // { id: 'wand-magic', name: 'Magic Wand', icon: 'fas fa-wand-magic-sprkles' },
    // { id: 'lantern', name: 'Lantern', icon: 'fas fa-lantern' },
    { id: 'coffee', name: 'Coffee', icon: 'fas fa-mug-hot' },
    { id: 'utensils', name: 'Utensils', icon: 'fas fa-utensils' },
])

const filteredIcons = computed(() => {
    if (!searchQuery.value) return icons.value

    const query = searchQuery.value.toLowerCase()
    return icons.value.filter(
        (icon) =>
            icon.id.toLowerCase().includes(query) ||
            icon.name.toLowerCase().includes(query)
    )
})

const getIconClass = (iconValue) => {
    if (!iconValue) return 'fas fa-folder text-2xl'

    const hasStylePrefix = ['fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands']
        .some((prefix) => iconValue.includes(prefix))

    if (hasStylePrefix) return `${iconValue}`
    if (iconValue.startsWith('fa-')) return `fas ${iconValue}`
    return `fas fa-${iconValue}`
}

const selectIcon = (icon) => {
    emit('update:modelValue', icon.id)
    showDropdown.value = false
    searchQuery.value = ''
}
</script>

<style scoped>
.icon-picker {
    position: relative;
}

/* Custom scrollbar for icon grid */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
