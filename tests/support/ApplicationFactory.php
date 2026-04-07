<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests\support;

use Yii;
use yii\helpers\ArrayHelper;
use yii\inertia\Vite;
use yii\inertia\vue\Bootstrap;
use yii\web\Application;

/**
 * Creates Yii application instances for tests.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class ApplicationFactory
{
    /**
     * A random string used as the cookie validation key for test applications.
     */
    private const COOKIE_VALIDATION_KEY = 'test-cookie-validation-key';

    /**
     * Destroys the current application.
     */
    public static function destroy(): void
    {
        if (Yii::$app !== null && Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }

        Yii::$app = null; // @phpstan-ignore assign.propertyType (Yii2 test teardown pattern)
    }

    /**
     * Creates a web application with the Vue bootstrap configured.
     *
     * @param array<string, mixed> $override Configuration values to override the defaults with.
     */
    public static function web(array $override = []): void
    {
        new Application(ArrayHelper::merge(self::commonBase(), $override));
    }

    /**
     * Returns the base configuration for a web application with the Vue bootstrap configured.
     *
     * @return array Base configuration array for a web application with the Vue bootstrap configured.
     *
     * @phpstan-return array<string, mixed>
     */
    private static function commonBase(): array
    {
        return [
            'id' => 'testapp',
            'aliases' => [
                '@root' => dirname(__DIR__, 2),
                '@tests' => dirname(__DIR__),
            ],
            'basePath' => dirname(__DIR__),
            'bootstrap' => [
                Bootstrap::class,
            ],
            'components' => [
                'inertiaVue' => [
                    'class' => Vite::class,
                    'baseUrl' => '@web/build',
                    'entrypoints' => [
                        'resources/js/app.js',
                    ],
                    'manifestPath' => '@tests/data/build/.vite/manifest.json',
                ],
                'request' => [
                    'cookieValidationKey' => self::COOKIE_VALIDATION_KEY,
                    'hostInfo' => 'https://example.test',
                    'isConsoleRequest' => false,
                    'scriptFile' => dirname(__DIR__, 2) . '/index.php',
                    'scriptUrl' => '/index.php',
                ],
            ],
            'vendorPath' => dirname(__DIR__, 2) . '/vendor',
        ];
    }
}
