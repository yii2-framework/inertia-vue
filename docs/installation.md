# Installation guide

## System requirements

- [PHP](https://www.php.net/downloads) `8.3` or higher.
- [Composer](https://getcomposer.org/download/) for dependency management.

## Installation

### Method 1: Using [Composer](https://getcomposer.org/download/) (recommended)

Install the extension.

```bash
composer require yii2-extensions/inertia-vue:^0.1
```

### Method 2: Manual installation

Add to your `composer.json`.

```json
{
  "require": {
    "yii2-extensions/inertia-vue": "^0.1"
  }
}
```

Then run.

```bash
composer update
```

## Register the bootstrap integration

Enable the Vue adapter in your web configuration.

```php
// config/web.php
return [
    'bootstrap' => [
        \yii\inertia\vue\Bootstrap::class,
    ],
];
```

Do not register `yii\inertia\Bootstrap::class` separately. The Vue bootstrap already delegates that setup.

## Application client-side dependencies

`yii2-extensions/inertia-vue` only ships the PHP adapter. The Vue runtime, Inertia client, and Vite bundler live in the
consuming application's `package.json`. There are two supported ways to install them.

### Option 1: `php-forge/foxy` (recommended)

[`php-forge/foxy`](https://github.com/php-forge/foxy) is a Composer plugin that runs Bun, npm, Yarn, or pnpm as part
of every `composer install` / `composer update`, so a single command provisions both PHP and JavaScript dependencies.

Add `php-forge/foxy` to your application's `composer.json` and declare the client-side packages in your `package.json`
at the project root.

```json
{
  "require": {
    "php-forge/foxy": "^0.2",
    "yii2-extensions/inertia-vue": "^0.1"
  },
  "config": {
    "allow-plugins": {
      "php-forge/foxy": true
    },
    "foxy": {
      "manager": "npm"
    }
  }
}
```

```json
{
  "private": true,
  "type": "module",
  "dependencies": {
    "@inertiajs/vue3": "^2.0",
    "vue": "^3.5"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.2",
    "vite": "^8.0"
  }
}
```

Then run.

```bash
composer install
```

Foxy will detect the configured manager and install the Node modules automatically. Switch `foxy.manager` to `bun`,
`yarn`, or `pnpm` if you prefer a different tool.

### Option 2: direct `npm install`

If you prefer to keep Composer and your Node package manager decoupled, install the client-side packages yourself in the
Yii2 application project.

```bash
npm install vue @inertiajs/vue3 @vitejs/plugin-vue vite
```

Yarn, pnpm, and Bun are supported the same way.

## When not to install this package

Do not install `yii2-extensions/inertia-vue` for applications that do not use Vue as their frontend framework. In that
scenario, use `yii2-extensions/inertia-react` or `yii2-extensions/inertia-svelte` instead.

## Next steps

- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)
