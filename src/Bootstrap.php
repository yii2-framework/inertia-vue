<?php

declare(strict_types=1);

namespace yii\inertia\vue;

use Yii;
use yii\base\BootstrapInterface;
use yii\inertia\Manager;

/**
 * Bootstraps the Vue adapter for yii2-framework/inertia.
 *
 * Delegates the base Inertia bootstrap, registers the `@inertia-vue` alias, ensures the `inertiaVue` helper component
 * is available, and switches the default Inertia root view to the Vue-aware view shipped by this package.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        (new \yii\inertia\Bootstrap())->bootstrap($app);

        Yii::setAlias('@inertia-vue', __DIR__);

        if (!$app->has('inertiaVue')) {
            $app->set('inertiaVue', ['class' => Vite::class]);
        }

        $manager = $app->get('inertia');

        if ($manager instanceof Manager && $manager->rootView === '@inertia/views/app.php') {
            $manager->rootView = '@inertia-vue/views/app.php';
        }
    }
}
