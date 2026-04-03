<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests\support;

use Yii;
use yii\helpers\ArrayHelper;
use yii\inertia\vue\Bootstrap;
use yii\inertia\vue\Vite;
use yii\web\Application;

/**
 * Creates Yii application instances for tests.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class ApplicationFactory
{
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
     * @param array<string, mixed> $override
     */
    public static function web(array $override = []): void
    {
        new Application(ArrayHelper::merge(self::commonBase(), $override));
    }

    /**
     * Returns the base configuration for a web application with the Vue bootstrap configured.
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
                    'manifestPath' => '@tests/data/build/.vite/manifest.json',
                    'baseUrl' => '@web/build',
                    'entrypoints' => ['resources/js/app.js'],
                ],
                'request' => [
                    'cookieValidationKey' => self::COOKIE_VALIDATION_KEY,
                    'hostInfo' => 'https://example.test',
                    'scriptFile' => dirname(__DIR__, 2) . '/index.php',
                    'scriptUrl' => '/index.php',
                    'isConsoleRequest' => false,
                ],
            ],
            'vendorPath' => dirname(__DIR__, 2) . '/vendor',
        ];
    }
}
