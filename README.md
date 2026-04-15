<!-- markdownlint-disable MD041 -->
<p align="center">
    <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_dark.svg">
        <source media="(prefers-color-scheme: light)" srcset="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg">
        <img src="https://www.yiiframework.com/image/design/logo/yii3_full_for_light.svg" alt="Yii Framework" width="80%">
    </picture>
    <h1 align="center">Inertia Vue</h1>
    <br>
</p>
<!-- markdownlint-enable MD041 -->

<p align="center">
    <a href="https://github.com/yii2-extensions/inertia-vue/actions/workflows/build.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia-vue/build.yml?style=for-the-badge&logo=github&label=PHPUnit" alt="PHPUnit">
    </a>
    <a href="https://dashboard.stryker-mutator.io/reports/github.com/yii2-extensions/inertia-vue/main" target="_blank">
        <img src="https://img.shields.io/endpoint?style=for-the-badge&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyii2-extensions%2Finertia-vue%2Fmain" alt="Mutation Testing">
    </a>
    <a href="https://github.com/yii2-extensions/inertia-vue/actions/workflows/static.yml" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia-vue/static.yml?style=for-the-badge&logo=github&label=PHPStan" alt="PHPStan">
    </a>
</p>

<p align="center">
    <strong>Vue adapter helpers for <a href="https://github.com/yii2-extensions/inertia">yii2-extensions/inertia</a></strong><br>
    <em>Vue-friendly root view and Vite asset integration for Yii2 Inertia applications</em>
</p>

## Features

<picture>
    <source media="(max-width: 767px)" srcset="./docs/svgs/features-mobile.svg">
    <img src="./docs/svgs/features.svg" alt="Feature Overview" style="width: 100%;">
</picture>

## Overview

`yii2-extensions/inertia-vue` is a thin PHP-side adapter package for building Vue-based Inertia applications on top of
`yii2-extensions/inertia`.

This package does not install npm dependencies for you. Instead, it provides:

- a Vue-specific bootstrap class for Yii2;
- a default root view that outputs Vite tags plus the initial Inertia page payload;
- a Vite helper component for development-server and manifest-driven production assets;
- documentation and conventions for the application-owned Vue client entrypoint.

## Installation

```bash
composer require yii2-extensions/inertia-vue:^0.1
```

Register the Vue bootstrap class:

```php
return [
    'bootstrap' => [
        \yii\inertia\vue\Bootstrap::class,
    ],
    'components' => [
        'inertiaVue' => [
            'class' => \yii\inertia\Vite::class,
            'baseUrl' => '@web/build',
            'devMode' => YII_ENV_DEV,
            'devServerUrl' => 'http://localhost:5173',
            'entrypoints' => [
                'resources/js/app.js',
            ],
            'manifestPath' => '@webroot/build/.vite/manifest.json',
        ],
    ],
];
```

Use only `yii\inertia\vue\Bootstrap::class` in the bootstrap list. It already delegates the base
`yii2-extensions/inertia` bootstrap.

## Vue client entrypoint

Install the client-side dependencies in your application project:

```bash
npm install vue @vitejs/plugin-vue @inertiajs/vue3 vite
```

Then create your client entrypoint, for example `resources/js/app.js`:

```js
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";

createInertiaApp({
  resolve: (name) => {
    const pages = import.meta.glob("./Pages/**/*.vue", { eager: true });
    return pages[`./Pages/${name}.vue`];
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});
```

## Production asset integration

This package expects a Vite manifest file generated with `build.manifest = true`. In production it will render:

1. style sheet tags for the entrypoint chunk and its imported chunks;
2. module entry scripts for each entrypoint;
3. optional `modulepreload` tags for imported JavaScript chunks.

## Documentation

For detailed configuration options and advanced usage.

- 📚 [Installation Guide](docs/installation.md)
- ⚙️ [Configuration Reference](docs/configuration.md)
- 💡 [Usage Examples](docs/examples.md)
- 🧪 [Testing Guide](docs/testing.md)
- 🛠️ [Development Notes](docs/development.md)

## Package information

[![PHP](https://img.shields.io/badge/%3E%3D8.3-777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/releases/8.3/en.php)
[![Yii 22.0.x](https://img.shields.io/badge/22.0.x-0073AA.svg?style=for-the-badge&logo=yii&logoColor=white)](https://github.com/yiisoft/yii2/tree/22.0)
[![Latest Stable Version](https://img.shields.io/packagist/v/yii2-extensions/inertia-vue.svg?style=for-the-badge&logo=packagist&logoColor=white&label=Stable)](https://packagist.org/packages/yii2-extensions/inertia-vue)
[![Total Downloads](https://img.shields.io/packagist/dt/yii2-extensions/inertia-vue.svg?style=for-the-badge&logo=composer&logoColor=white&label=Downloads)](https://packagist.org/packages/yii2-extensions/inertia-vue)

## Quality code

[![Codecov](https://img.shields.io/codecov/c/github/yii2-extensions/inertia-vue.svg?style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/github/yii2-extensions/inertia-vue)
[![PHPStan Level Max](https://img.shields.io/badge/PHPStan-Level%20Max-4F5D95.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.com/yii2-extensions/inertia-vue/actions/workflows/static.yml)
[![Super-Linter](https://img.shields.io/github/actions/workflow/status/yii2-extensions/inertia-vue/linter.yml?style=for-the-badge&label=Super-Linter&logo=github)](https://github.com/yii2-extensions/inertia-vue/actions/workflows/linter.yml)
[![StyleCI](https://img.shields.io/badge/StyleCI-Passed-44CC11.svg?style=for-the-badge&logo=github&logoColor=white)](https://github.styleci.io/repos/1196180037?branch=main)

## License

[![License](https://img.shields.io/badge/License-BSD--3--Clause-brightgreen.svg?style=for-the-badge&logo=opensourceinitiative&logoColor=white&labelColor=555555)](LICENSE)
