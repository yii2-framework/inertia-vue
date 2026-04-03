# Installation guide

## System requirements

- [PHP](https://www.php.net/downloads) `8.2` or higher.
- [Composer](https://getcomposer.org/download/) for dependency management.

## Installation

### Method 1: Using [Composer](https://getcomposer.org/download/) (recommended)

Install the extension.

```bash
composer require yii2-framework/inertia-vue:^0.1
```

### Method 2: Manual installation

Add to your `composer.json`.

```json
{
    "require": {
        "yii2-framework/inertia-vue": "^0.1"
    }
}
```

Then run.

```bash
composer update
```

## Register the bootstrap integration

Enable the Vue adapter in your web configuration:

```php
// config/web.php
return [
    'bootstrap' => [
        \yii\inertia\vue\Bootstrap::class,
    ],
];
```

Do not register `yii\inertia\Bootstrap::class` separately. The Vue bootstrap already delegates that setup.

## Application npm dependencies

Install the client-side packages in your Yii2 application project:

```bash
npm install vue @vitejs/plugin-vue @inertiajs/vue3 vite
```

## When not to install this package

Do not install `yii2-framework/inertia-vue` for applications that do not use Vue as their frontend framework. In that
scenario, use `yii2-framework/inertia-react` or `yii2-framework/inertia-svelte` instead.

## Next steps

- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)
