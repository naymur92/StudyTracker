import { createApp } from 'vue'

// Import admin helpers (attaches setSpinner/unsetSpinner/notify to window)
import './helpers/spinner.js'
import './helpers/notifications.js'

// Form components registered globally on every admin Vue app instance
import CheckboxGroup from './components/form/CheckboxGroup.vue'
import FormInput from './components/form/FormInput.vue'
import ResetButton from './components/form/ResetButton.vue'
import SubmitButton from './components/form/SubmitButton.vue'
import Select2 from './components/form/Select2.vue'

/**
 * Admin Vue Components Bootstrap
 * 
 * This file initializes Vue components that are mounted in Blade templates.
 * Each Blade template can mount individual Vue components using data-vue-component attribute.
 * 
 * Usage in Blade:
 * <div id="app-dashboard-stats" data-vue-component="DashboardStats"></div>
 * 
 * Then in Vite:
 * @vite('resources/js/admin/bootstrap.js')
 */

// Dynamic component loading
const componentModules = import.meta.glob('./components/**/*.vue', { eager: true })

// Mount Vue components dynamically from Blade templates
document.addEventListener('DOMContentLoaded', () => {
    const componentElements = document.querySelectorAll('[data-vue-component]')

    componentElements.forEach((element) => {
        const componentName = element.dataset.vueComponent

        // Find the component module
        let component = null
        for (const [path, module] of Object.entries(componentModules)) {
            if (path.includes(componentName)) {
                component = module.default
                break
            }
        }

        if (component) {
            const app = createApp(component)

            // Register form components globally
            app.component('checkbox-group', CheckboxGroup)
            app.component('form-input', FormInput)
            app.component('reset-button', ResetButton)
            app.component('submit-button', SubmitButton)
            app.component('select2', Select2)

            // Pass data attributes as props
            const props = {}
            for (const [key, value] of Object.entries(element.dataset)) {
                if (key !== 'vueComponent') {
                    props[key] = value
                }
            }

            if (Object.keys(props).length > 0) {
                app.mount(element, props)
            } else {
                app.mount(element)
            }
        } else {
            console.warn(`Component ${componentName} not found`)
        }
    })
})
