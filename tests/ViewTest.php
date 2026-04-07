<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests;

use Yii;
use yii\base\{Component, InvalidConfigException};
use yii\inertia\{Inertia, Vite};
use yii\inertia\vue\Bootstrap;
use yii\web\{Application, Response};

/**
 * Integration tests for the Vue root view and the base Inertia package.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class ViewTest extends TestCase
{
    public function testThrowInvalidConfigExceptionForNonViteComponent(): void
    {
        $this->destroyApplication();

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

        // replace the auto-registered Vite component with a plain Component.
        Yii::$app->set('inertiaVue', new Component());

        $this->setAbsoluteUrl('/dashboard');
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage(Vite::class);

        Inertia::render('Dashboard', ['stats' => ['visits' => 42]]);
    }

    public function testVueRootViewRendersViteTagsAndPagePayload(): void
    {
        $this->setAbsoluteUrl('/dashboard');

        $response = Inertia::render(
            'Dashboard',
            [
                'stats' => [
                    'visits' => 42,
                ],
            ],
        );

        $content = (string) $response->content;

        $page = $this->extractPage($response);

        self::assertSame(
            Response::FORMAT_HTML,
            $response->format,
            'View response should be HTML format.',
        );
        self::assertStringContainsString(
            '<meta name="csrf-param"',
            $content,
            'View should include CSRF meta tag.',
        );
        self::assertStringContainsString(
            '<link href="/build/assets/app-BRBmoGS9.css" rel="stylesheet">',
            $content,
            'View should include the CSS stylesheet from the manifest.',
        );
        self::assertStringContainsString(
            '<script type="module" src="/build/assets/app-BRBmoGS9.js"></script>',
            $content,
            'View should include the module script from the manifest.',
        );
        self::assertStringContainsString(
            '<script type="application/json">',
            $content,
            'View should include the Inertia page payload script.',
        );
        self::assertSame(
            'Dashboard',
            $page['component'], // @phpstan-ignore offsetAccess.notFound
            'Page payload should contain the rendered component name.',
        );
        self::assertSame(
            ['visits' => 42],
            $page['props']['stats'], // @phpstan-ignore offsetAccess.notFound, offsetAccess.nonOffsetAccessible
            'Page payload should contain the passed props.',
        );
    }
}
