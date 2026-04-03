<?php

declare(strict_types=1);

namespace yii\inertia\vue\tests;

use Yii;
use yii\base\InvalidConfigException;
use yii\inertia\vue\tests\support\stub\MockerFunctions;
use yii\inertia\vue\Vite;

/**
 * Unit tests for {@see \yii\inertia\vue\Vite}.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class ViteTest extends TestCase
{
    public function testManifestIsCachedAfterFirstRead(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $first = $vite->renderTags();
        $second = $vite->renderTags();

        self::assertSame(
            $first,
            $second,
            'Manifest should be cached after the first read and produce identical output.',
        );
    }

    public function testRenderTagsBaseUrlTrailingSlashIsTrimmed(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'baseUrl' => '/build/',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'src="/build/assets/app-BRBmoGS9.js"',
            $tags,
            'Trailing slash on baseUrl should be trimmed to avoid double slashes.',
        );
        self::assertStringNotContainsString(
            '/build//assets',
            $tags,
            'BaseUrl should not produce double slashes in asset URLs.',
        );
    }

    public function testRenderTagsCssDeduplication(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/multi-entry-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                    'resources/js/admin.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertSame(
            1,
            substr_count($tags, 'assets/app-abc123.css'),
            'Shared CSS file should appear only once even when referenced by multiple entrypoints.',
        );
    }

    public function testRenderTagsCssEntrypoint(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/css-entrypoint-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/css/app.css',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            '<link href="/build/assets/app-abc123.css" rel="stylesheet">',
            $tags,
            'Entrypoint whose file ends in .css should be rendered as a stylesheet.',
        );
        self::assertStringNotContainsString(
            '<script',
            $tags,
            'CSS-only entrypoint should not produce a script tag.',
        );
    }

    public function testRenderTagsDeduplicatesEntrypoints(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'baseUrl' => '@web/build',
            ],
        );

        $tags = $vite->renderTags(
            [
                'resources/js/app.js',
                'resources/js/app.js',
            ],
        );

        self::assertSame(
            1,
            substr_count($tags, 'app-BRBmoGS9.js'),
            'Duplicate entrypoints should be deduplicated and rendered only once.',
        );
    }

    public function testRenderTagsDeduplicatesScriptTagsForSameFile(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/duplicate-file-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                    'resources/js/app-legacy.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertSame(
            1,
            substr_count($tags, 'assets/bundle.js'),
            'Two entrypoints mapping to the same output file should produce only one script tag.',
        );
    }

    public function testRenderTagsDevModeMultipleEntrypoints(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => 'http://localhost:5173',
                'entrypoints' => [
                    'resources/js/app.js',
                    'resources/js/admin.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'http://localhost:5173/resources/js/app.js',
            $tags,
            'Development mode should include the first entrypoint.',
        );
        self::assertStringContainsString(
            'http://localhost:5173/resources/js/admin.js',
            $tags,
            'Development mode should include the second entrypoint.',
        );
    }

    public function testRenderTagsDevModeTrimsLeadingSlashFromEntrypoint(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => 'http://localhost:5173',
                'entrypoints' => [
                    '/resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'http://localhost:5173/resources/js/app.js',
            $tags,
            'Leading slash in entrypoint should be trimmed to avoid double slashes.',
        );
        self::assertStringNotContainsString(
            'http://localhost:5173//resources',
            $tags,
            'Entrypoint should not produce double slashes.',
        );
    }

    public function testRenderTagsDevModeTrimsTrailingSlashFromUrl(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => 'http://localhost:5173/',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'http://localhost:5173/@vite/client',
            $tags,
            'Dev server URL trailing slash should be trimmed to avoid double slashes.',
        );
        self::assertStringNotContainsString(
            'http://localhost:5173//',
            $tags,
            'Dev server URL should not produce double slashes.',
        );
    }

    public function testRenderTagsDevModeWithoutViteClient(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => 'http://localhost:5173',
                'includeViteClient' => false,
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringNotContainsString(
            '@vite/client',
            $tags,
            'Development mode with includeViteClient disabled should not render the @vite/client script.',
        );
        self::assertStringContainsString(
            '<script type="module" src="http://localhost:5173/resources/js/app.js"></script>',
            $tags,
            'Development mode should still include the entrypoint script tag.',
        );
    }

    public function testRenderTagsFiltersBlankEntrypoints(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'baseUrl' => '@web/build',
            ],
        );

        $tags = $vite->renderTags(
            [
                'resources/js/app.js',
                '  ',
                '',
            ],
        );

        self::assertStringContainsString(
            '<script type="module"',
            $tags,
            'Blank entrypoints should be filtered out and valid ones should render.',
        );
    }

    public function testRenderTagsForDevelopmentMode(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => 'http://localhost:5173',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            $tags,
            'Development mode should include the @vite/client script tag with type="module".',
        );
        self::assertStringContainsString(
            '<script type="module" src="http://localhost:5173/resources/js/app.js"></script>',
            $tags,
            'Development mode should include the entrypoint script tag with type="module".',
        );
    }

    // -- Manifest / production mode ----------------------------------------

    public function testRenderTagsForManifestBuild(): void
    {
        /** @phpstan-var Vite $vite */
        $vite = Yii::$app->get('inertiaVue');

        $tags = $vite->renderTags();

        self::assertSame(
            implode(
                "\n",
                [
                    '<link href="/build/assets/app-BRBmoGS9.css" rel="stylesheet">',
                    '<link href="/build/assets/shared-ChJ_j-JJ.css" rel="stylesheet">',
                    '<script type="module" src="/build/assets/app-BRBmoGS9.js"></script>',
                    '<link href="/build/assets/shared-B7PI925R.js" rel="modulepreload">',
                ],
            ),
            $tags,
            'Production mode should render CSS, script, and modulepreload tags from the manifest.',
        );
    }

    public function testRenderTagsHandlesCircularImports(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/circular-import-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            '<script type="module" src="/build/assets/app-circular.js"></script>',
            $tags,
            'Circular imports should be handled without infinite recursion.',
        );
        self::assertSame(
            1,
            substr_count($tags, 'assets/chunk-a.js'),
            'Each circular chunk should appear only once in the output.',
        );
        self::assertSame(
            1,
            substr_count($tags, 'assets/chunk-b.js'),
            'Each circular chunk should appear only once in the output.',
        );
    }

    public function testRenderTagsHandlesDeepImportChain(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/deep-import-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        // Entrypoint imports [B, A]. B imports C. All three must appear as modulepreload.
        self::assertSame(
            implode(
                "\n",
                [
                    '<script type="module" src="/build/assets/app-deep.js"></script>',
                    '<link href="/build/assets/chunk-c.js" rel="modulepreload">',
                    '<link href="/build/assets/chunk-b.js" rel="modulepreload">',
                    '<link href="/build/assets/chunk-a.js" rel="modulepreload">',
                ],
            ),
            $tags,
            'Multiple imports with nested sub-imports should resolve all chunks via array_merge.',
        );
    }

    public function testRenderTagsHandlesNonArrayImports(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/non-array-import-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            '<script type="module" src="/build/assets/app-abc123.js"></script>',
            $tags,
            'Non-array imports value should be silently skipped without errors.',
        );
        self::assertStringNotContainsString(
            'modulepreload',
            $tags,
            'Non-array imports should not produce any modulepreload tags.',
        );
    }

    public function testRenderTagsImportedChunkCssFiles(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/multi-entry-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'assets/shared-abc123.css',
            $tags,
            'CSS files from imported chunks should be included in the output.',
        );
    }

    public function testRenderTagsPreloadDeduplication(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/multi-entry-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                    'resources/js/admin.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertSame(
            1,
            substr_count($tags, 'assets/shared-abc123.js'),
            'Shared imported chunk should have only one modulepreload tag.',
        );
    }

    public function testRenderTagsPreloadSkipsCssChunksBeforeJsChunks(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/css-chunk-import-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringNotContainsString(
            'modulepreload',
            $tags,
            'Imported chunk whose file ends in .css should not get a modulepreload tag.',
        );
    }

    public function testRenderTagsSkipsNonStringCssFile(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/non-string-css-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'assets/app-abc123.css',
            $tags,
            'Valid CSS files should still be rendered when non-string CSS entries are present.',
        );
        self::assertSame(
            1,
            substr_count($tags, 'rel="stylesheet"'),
            'Only valid string CSS entries should produce stylesheet tags.',
        );
    }

    public function testRenderTagsSkipsNonStringImport(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/non-string-import-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            'assets/shared-abc123.js',
            $tags,
            'Valid string imports should still be processed when non-string imports are present.',
        );
    }

    public function testRenderTagsWithModulePreloadDisabled(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
                'modulePreload' => false,
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringNotContainsString(
            'modulepreload',
            $tags,
            'Production mode with modulePreload disabled should not render modulepreload tags.',
        );
        self::assertStringContainsString(
            '<script type="module"',
            $tags,
            'Production mode should still render module script tags.',
        );
    }

    public function testRenderTagsWithMultipleEntrypointsInBuildMode(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/multi-entry-manifest.json',
                'baseUrl' => '@web/build',
                'entrypoints' => [
                    'resources/js/app.js',
                    'resources/js/admin.js',
                ],
            ],
        );

        $tags = $vite->renderTags();

        self::assertStringContainsString(
            '<script type="module" src="/build/assets/app-abc123.js"></script>',
            $tags,
            'Production mode should render the first entrypoint script.',
        );
        self::assertStringContainsString(
            '<script type="module" src="/build/assets/admin-def456.js"></script>',
            $tags,
            'Production mode should render the second entrypoint script.',
        );
        // Verify all expected preload tags are present (tests array_merge in getImportedChunks)
        self::assertStringContainsString(
            '<link href="/build/assets/shared-abc123.js" rel="modulepreload">',
            $tags,
            'Shared imported chunk should have a modulepreload tag.',
        );
        self::assertStringContainsString(
            '<link href="/build/assets/utils-def456.js" rel="modulepreload">',
            $tags,
            'Utils imported chunk should have a modulepreload tag.',
        );
    }

    public function testRenderTagsWithStringEntrypointParameter(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'baseUrl' => '@web/build',
            ],
        );

        $tags = $vite->renderTags('resources/js/app.js');

        self::assertStringContainsString(
            '<script type="module"',
            $tags,
            'String entrypoint parameter should be accepted and rendered.',
        );
    }

    public function testThrowInvalidConfigExceptionForAllBlankEntrypoints(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('At least one Vite entrypoint');

        $vite->renderTags(['  ', '']);
    }

    public function testThrowInvalidConfigExceptionForEmptyDevServerUrl(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => '   ',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('devServerUrl');

        $vite->renderTags();
    }

    public function testThrowInvalidConfigExceptionForEmptyEntrypointsArray(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('At least one Vite entrypoint');

        $vite->renderTags([]);
    }

    public function testThrowInvalidConfigExceptionForInvalidJsonManifest(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/invalid-manifest.json',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Unable to decode');

        $vite->renderTags();
    }

    public function testThrowInvalidConfigExceptionForInvalidManifestChunk(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/invalid-chunk-manifest.json',
                'entrypoints' => [
                    'resources/js/bad.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('is invalid');

        $vite->renderTags();
    }

    public function testThrowInvalidConfigExceptionForMissingDevServerUrl(): void
    {
        $vite = new Vite(
            [
                'devMode' => true,
                'devServerUrl' => null,
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('devServerUrl');

        $vite->renderTags();
    }

    public function testThrowInvalidConfigExceptionForMissingManifestEntrypoint(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'entrypoints' => [
                    'resources/js/missing.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('resources/js/missing.js');

        $vite->renderTags();
    }

    public function testThrowInvalidConfigExceptionForNonArrayManifest(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/scalar-manifest.json',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('must decode to an array');

        $vite->renderTags();
    }

    // -- Manifest errors ---------------------------------------------------

    public function testThrowInvalidConfigExceptionForNonExistentManifestFile(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/nonexistent.json',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('does not exist');

        $vite->renderTags();
    }

    public function testThrowInvalidConfigExceptionForNonStringEntrypoint(): void
    {
        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Each Vite entrypoint must be a string');

        /** @phpstan-ignore argument.type (testing runtime type validation) */
        $vite->renderTags([123]);
    }

    public function testThrowInvalidConfigExceptionForUnreadableManifest(): void
    {
        MockerFunctions::setFileGetContentsShouldFail();

        $vite = new Vite(
            [
                'manifestPath' => '@tests/data/build/.vite/manifest.json',
                'entrypoints' => [
                    'resources/js/app.js',
                ],
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Unable to read the Vite manifest file');

        $vite->renderTags();
    }
}
