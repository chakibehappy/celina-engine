// resources/js/app.js

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'; // Add this helper
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

createInertiaApp({
  // This glob pattern (./Pages/**/*.vue) looks deep into subfolders
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .mount(el)
  },
})