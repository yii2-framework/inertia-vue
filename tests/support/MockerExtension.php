<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests\support;

use PHPUnit\Event\Test\{PreparationStarted, PreparationStartedSubscriber};
use PHPUnit\Event\TestSuite\{Started, StartedSubscriber};
use PHPUnit\Runner\Extension\{Extension, Facade, ParameterCollection};
use PHPUnit\TextUI\Configuration\Configuration;
use Xepozz\InternalMocker\{Mocker, MockerState};
use yii\inertia\vue\tests\support\stub\MockerFunctions;

/**
 * PHPUnit extension that registers internal-function mocks for test execution.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class MockerExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $facade->registerSubscribers(
            new class implements StartedSubscriber {
                public function notify(Started $event): void
                {
                    MockerExtension::load();
                }
            },
            new class implements PreparationStartedSubscriber {
                public function notify(PreparationStarted $event): void
                {
                    MockerState::resetState();
                }
            },
        );
    }

    public static function load(): void
    {
        $mocks = [
            [
                'namespace' => 'yii\inertia',
                'name' => 'file_get_contents',
                'function' => static fn(
                    string $filename,
                    mixed ...$args,
                ): string|false => MockerFunctions::file_get_contents($filename, ...$args),
            ],
        ];

        $mocker = new Mocker(stubPath: __DIR__ . '/stub/stubs.php');

        $mocker->load($mocks);

        MockerState::saveState();
    }
}
