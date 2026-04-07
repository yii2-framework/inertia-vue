<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests;

use Yii;
use yii\inertia\{Manager, Vite};
use yii\inertia\vue\Bootstrap;
use yii\web\Application;

/**
 * Unit tests for {@see \yii\inertia\vue\Bootstrap}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class BootstrapTest extends TestCase
{
    public function testBootstrapPreservesCustomInertiaRootView(): void
    {
        $this->mockWebApplication(
            [
                'components' => [
                    'inertia' => [
                        'class' => Manager::class,
                        'rootView' => '@app/views/layouts/custom.php',
                    ],
                ],
            ],
        );

        $manager = Yii::$app->get('inertia');

        self::assertInstanceOf(
            Manager::class,
            $manager,
            'Bootstrap should register the Manager component.',
        );
        self::assertSame(
            '@app/views/layouts/custom.php',
            $manager->rootView,
            'Bootstrap should preserve a custom root view when already configured.',
        );
    }

    public function testBootstrapRegistersAliasAndVueComponent(): void
    {
        self::assertSame(
            dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src',
            Yii::getAlias('@inertia-vue'),
            'Bootstrap should register the @inertia-vue alias pointing to the src/ directory.',
        );
        self::assertInstanceOf(
            Vite::class,
            Yii::$app->get('inertiaVue'),
            'Bootstrap should register the inertiaVue component as a Vite instance.',
        );
    }

    public function testBootstrapRegistersDefaultVueComponentWhenNotConfigured(): void
    {
        new Application(
            [
                'id' => 'testapp',
                'aliases' => [
                    '@tests' => dirname(__DIR__) . '/tests',
                ],
                'basePath' => dirname(__DIR__) . '/tests',
                'bootstrap' => [
                    Bootstrap::class,
                ],
                'components' => [
                    'request' => [
                        'cookieValidationKey' => 'test',
                        'hostInfo' => 'https://example.test',
                        'isConsoleRequest' => false,
                        'scriptFile' => dirname(__DIR__) . '/index.php',
                        'scriptUrl' => '/index.php',
                    ],
                ],
                'vendorPath' => dirname(__DIR__) . '/vendor',
            ],
        );

        self::assertInstanceOf(
            Vite::class,
            Yii::$app->get('inertiaVue'),
            'Bootstrap should register a default Vite component when inertiaVue is not configured.',
        );
    }

    public function testBootstrapSwitchesDefaultRootViewToVueView(): void
    {
        $manager = Yii::$app->get('inertia');

        self::assertInstanceOf(
            Manager::class,
            $manager,
            'Bootstrap should register the Inertia Manager component.',
        );
        self::assertSame(
            '@inertia-vue/views/app.php',
            $manager->rootView,
            'Bootstrap should switch the default root view to the Vue-aware template.',
        );
    }
}
