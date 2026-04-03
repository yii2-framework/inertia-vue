# Usage examples

## Vue bootstrap in Yii2

```php
// config/web.php
return [
    'bootstrap' => [\yii\inertia\vue\Bootstrap::class],
    'components' => [
        'inertiaVue' => [
            'class' => \yii\inertia\vue\Vite::class,
            'manifestPath' => '@webroot/build/.vite/manifest.json',
            'baseUrl' => '@web/build',
            'entrypoints' => ['resources/js/app.js'],
            'devMode' => YII_ENV_DEV,
            'devServerUrl' => 'http://localhost:5173',
        ],
    ],
];
```

## Vue client entrypoint

```js
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
```

## Vite configuration

```js
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    build: {
        manifest: true,
        rollupOptions: {
            input: 'resources/js/app.js',
        },
    },
})
```

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 🧪 [Testing Guide](testing.md)
