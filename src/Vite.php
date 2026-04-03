<?php

declare(strict_types=1);

namespace yii\inertia\vue;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;

use function array_key_exists;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Renders Vite entrypoint tags for Vue-based Inertia applications.
 *
 * Supports a development mode that points directly to the Vite dev server, and a production mode that reads Vite's
 * manifest file and renders stylesheets, module entry scripts, and optional modulepreload tags.
 *
 * Usage example:
 *
 * ```php
 * // config/web.php
 * return [
 *     'components' => [
 *         'inertiaVue' => [
 *             'class' => \yii\inertia\vue\Vite::class,
 *             'manifestPath' => '@webroot/build/.vite/manifest.json',
 *             'baseUrl' => '@web/build',
 *             'entrypoints' => ['resources/js/app.js'],
 *             'devMode' => YII_ENV_DEV,
 *             'devServerUrl' => 'http://localhost:5173',
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class Vite extends Component
{
    /**
     * Base URL prefix for built assets referenced by the Vite manifest.
     */
    public string $baseUrl = '@web/build';
    /**
     * Whether the Vite dev server should be used instead of the build manifest.
     */
    public bool $devMode = false;
    /**
     * Vite development server URL.
     */
    public string|null $devServerUrl = null;
    /**
     * @phpstan-var string[] Vite entrypoints to render.
     */
    public array $entrypoints = [
        'resources/js/app.js',
    ];
    /**
     * Whether to include the `@vite/client` development script when in development mode.
     */
    public bool $includeViteClient = true;
    /**
     * Path to the Vite manifest file.
     */
    public string $manifestPath = '@webroot/build/.vite/manifest.json';
    /**
     * Whether to render `modulepreload` tags for imported JavaScript chunks in production mode.
     */
    public bool $modulePreload = true;

    /**
     * @phpstan-var array<string, array<string, mixed>>|null Cached Vite manifest contents.
     */
    private array|null $manifest = null;

    /**
     * Renders the HTML tags for the configured or provided entrypoints.
     *
     * Returns CSS stylesheet tags, module script tags, and optional modulepreload tags in production mode, or dev
     * server script tags in development mode.
     *
     * Usage example:
     *
     * ```php
     * $vite = Yii::$app->get('inertiaVue');
     *
     * // render tags for the default configured entrypoints.
     * echo $vite->renderTags();
     *
     * // render tags for a specific entrypoint.
     * echo $vite->renderTags('resources/js/admin.js');
     * ```
     *
     * @param array|string|null $entrypoints Entrypoints to render, or `null` to use the configured defaults.
     *
     * @throws InvalidConfigException if the manifest is missing, invalid, or entrypoints cannot be resolved.
     * @return string Concatenated HTML tags ready for output.
     *
     * @phpstan-param string[]|string|null $entrypoints
     */
    public function renderTags(array|string|null $entrypoints = null): string
    {
        $entrypoints = $this->normalizeEntrypoints($entrypoints ?? $this->entrypoints);

        return $this->devMode
            ? $this->renderDevelopmentTags($entrypoints)
            : $this->renderBuildTags($entrypoints);
    }

    /**
     * Recursively collects all transitive import chunks for a given manifest chunk.
     *
     * Tracks already-visited imports via `$seen` to prevent infinite recursion on circular dependencies.
     *
     * @param array $manifest Parsed Vite manifest.
     * @param array $chunk Chunk descriptor whose `imports` key will be traversed.
     * @param array $seen Import keys already visited; passed by reference to accumulate across recursive calls.
     *
     * @return array Flat list of imported chunk descriptors in dependency order.
     *
     * @phpstan-param array<string, array<string, mixed>> $manifest
     * @phpstan-param array<string, mixed> $chunk
     * @phpstan-param array<string, true> $seen
     * @phpstan-return array<int, array<string, mixed>>
     */
    private function getImportedChunks(array $manifest, array $chunk, array &$seen = []): array
    {
        $chunks = [];

        /** @var mixed $imports */
        $imports = $chunk['imports'] ?? [];

        if (!is_array($imports)) {
            return $chunks;
        }

        foreach ($imports as $import) {
            if (!is_string($import) || array_key_exists($import, $seen)) {
                continue;
            }

            $seen[$import] = true;
            $importedChunk = $this->getManifestChunk($manifest, $import);
            $chunks = array_merge($chunks, $this->getImportedChunks($manifest, $importedChunk, $seen));
            $chunks[] = $importedChunk;
        }

        return $chunks;
    }

    /**
     * Reads, decodes, validates, and caches the Vite manifest file.
     *
     * @throws InvalidConfigException if the manifest file is missing, unreadable, or contains invalid data.
     * @return array Parsed manifest keyed by source entrypoint path.
     *
     * @phpstan-return array<string, array<string, mixed>>
     */
    private function getManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        $path = Yii::getAlias($this->manifestPath);

        if (!is_file($path)) {
            throw new InvalidConfigException(
                sprintf('The Vite manifest file "%s" does not exist.', $path),
            );
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new InvalidConfigException(
                sprintf('Unable to read the Vite manifest file "%s".', $path),
            );
        }

        try {
            $manifest = Json::decode($content);
        } catch (\Throwable $e) {
            throw new InvalidConfigException(
                sprintf('Unable to decode the Vite manifest file "%s".', $path),
                0,
                $e,
            );
        }

        if (!is_array($manifest)) {
            throw new InvalidConfigException(
                sprintf('The Vite manifest file "%s" must decode to an array.', $path),
            );
        }

        /** @phpstan-var array<string, array<string, mixed>> $manifest */
        return $this->manifest = $manifest;
    }

    /**
     * Returns the validated chunk descriptor for a single entrypoint from the manifest.
     *
     * @param array $manifest Parsed Vite manifest.
     * @param string $entrypoint Source entrypoint path (e.g. `resources/js/app.js`).
     *
     * @throws InvalidConfigException if the entrypoint is missing or its chunk is malformed.
     * @return array Chunk descriptor containing at least a `file` key.
     *
     * @phpstan-param array<string, array<string, mixed>> $manifest
     * @phpstan-return array<string, mixed>
     */
    private function getManifestChunk(array $manifest, string $entrypoint): array
    {
        if (!array_key_exists($entrypoint, $manifest)) {
            throw new InvalidConfigException(
                sprintf('The Vite manifest does not contain the entrypoint "%s".', $entrypoint),
            );
        }

        $chunk = $manifest[$entrypoint];

        if (!is_array($chunk) || !isset($chunk['file']) || !is_string($chunk['file'])) { // @phpstan-ignore function.alreadyNarrowedType
            throw new InvalidConfigException(
                sprintf('The Vite manifest entry "%s" is invalid.', $entrypoint),
            );
        }

        return $chunk;
    }

    /**
     * Validates and normalizes entrypoints into a deduplicated, non-empty list of trimmed strings.
     *
     * @param array|string|null $entrypoints Raw entrypoints to normalize.
     *
     * @throws InvalidConfigException if the resulting list is empty or contains non-string values.
     * @return array Non-empty list of unique, trimmed entrypoint paths.
     *
     * @phpstan-param string[]|string|null $entrypoints
     * @phpstan-return string[]
     */
    private function normalizeEntrypoints(array|string|null $entrypoints): array
    {
        if ($entrypoints === null) { // @codeCoverageIgnore
            throw new InvalidConfigException('At least one Vite entrypoint must be configured.'); // @codeCoverageIgnore
        } // @codeCoverageIgnore

        $entrypoints = is_array($entrypoints) ? $entrypoints : [$entrypoints];
        $entrypoints = array_values(array_unique(array_filter(array_map(static function ($entrypoint): string {
            if (!is_string($entrypoint)) { // @phpstan-ignore function.alreadyNarrowedType
                throw new InvalidConfigException('Each Vite entrypoint must be a string.');
            }

            return trim($entrypoint);
        }, $entrypoints), static fn(string $entrypoint): bool => $entrypoint !== '')));

        if ($entrypoints === []) {
            throw new InvalidConfigException('At least one Vite entrypoint must be configured.');
        }

        return $entrypoints;
    }

    /**
     * Appends a CSS stylesheet tag if the file is a valid string and has not been seen before.
     *
     * @param array $cssTags Accumulated CSS tags; modified by reference.
     * @param array $cssSeen Set of already-emitted CSS file paths; modified by reference.
     * @param mixed $cssFile CSS file path from the manifest, or a non-string value to skip.
     *
     * @phpstan-param array<int, string> $cssTags
     * @phpstan-param array<string, true> $cssSeen
     */
    private function pushCssTag(array &$cssTags, array &$cssSeen, mixed $cssFile): void
    {
        if (!is_string($cssFile) || array_key_exists($cssFile, $cssSeen)) {
            return;
        }

        $cssSeen[$cssFile] = true;
        $cssTags[] = Html::cssFile($this->resolveAssetUrl($cssFile));
    }

    /**
     * Appends a module script or CSS stylesheet tag for an entrypoint output file.
     *
     * Routes `.css` files to {@see pushCssTag()} and deduplicates script files via `$scriptSeen`.
     *
     * @param array $scriptTags Accumulated script tags; modified by reference.
     * @param array $scriptSeen Set of already-emitted script file paths; modified by reference.
     * @param array $cssTags Accumulated CSS tags; modified by reference.
     * @param array $cssSeen Set of already-emitted CSS file paths; modified by reference.
     * @param string $file Output file path from the manifest chunk.
     *
     * @phpstan-param array<int, string> $scriptTags
     * @phpstan-param array<string, true> $scriptSeen
     * @phpstan-param array<int, string> $cssTags
     * @phpstan-param array<string, true> $cssSeen
     */
    private function pushEntrypointTag(
        array &$scriptTags,
        array &$scriptSeen,
        array &$cssTags,
        array &$cssSeen,
        string $file,
    ): void {
        if (str_ends_with($file, '.css')) {
            $this->pushCssTag($cssTags, $cssSeen, $file);
            return;
        }

        if (array_key_exists($file, $scriptSeen)) {
            return;
        }

        $scriptSeen[$file] = true;
        $scriptTags[] = Html::jsFile($this->resolveAssetUrl($file), ['type' => 'module']);
    }

    /**
     * Renders production-mode HTML tags from the Vite manifest for the given entrypoints.
     *
     * Emits CSS stylesheet tags, module script tags, and optional modulepreload tags for imported chunks.
     *
     * @param array $entrypoints Normalized list of entrypoint paths.
     *
     * @return string Concatenated HTML tags.
     *
     * @phpstan-param string[] $entrypoints
     */
    private function renderBuildTags(array $entrypoints): string
    {
        $manifest = $this->getManifest();
        $cssTags = [];
        $cssSeen = [];
        $scriptTags = [];
        $scriptSeen = [];
        $preloadTags = [];
        $preloadSeen = [];

        foreach ($entrypoints as $entrypoint) {
            $entryChunk = $this->getManifestChunk($manifest, $entrypoint);
            $importedChunks = $this->getImportedChunks($manifest, $entryChunk);

            /** @var mixed $cssFiles */
            $cssFiles = $entryChunk['css'] ?? [];

            if (is_array($cssFiles)) {
                foreach ($cssFiles as $cssFile) {
                    $this->pushCssTag($cssTags, $cssSeen, $cssFile);
                }
            }

            foreach ($importedChunks as $importedChunk) {
                /** @var mixed $importedCssFiles */
                $importedCssFiles = $importedChunk['css'] ?? [];

                if (is_array($importedCssFiles)) {
                    foreach ($importedCssFiles as $cssFile) {
                        $this->pushCssTag($cssTags, $cssSeen, $cssFile);
                    }
                }
            }

            /** @var string $file */
            $file = $entryChunk['file']; // @phpstan-ignore offsetAccess.notFound
            $this->pushEntrypointTag($scriptTags, $scriptSeen, $cssTags, $cssSeen, $file);

            if ($this->modulePreload) {
                foreach ($importedChunks as $importedChunk) {
                    /** @var string $preloadFile */
                    $preloadFile = $importedChunk['file']; // @phpstan-ignore offsetAccess.notFound

                    if (str_ends_with($preloadFile, '.css') || array_key_exists($preloadFile, $preloadSeen)) {
                        continue;
                    }

                    $preloadSeen[$preloadFile] = true;
                    $preloadTags[] = Html::tag('link', '', [
                        'rel' => 'modulepreload',
                        'href' => $this->resolveAssetUrl($preloadFile),
                    ]);
                }
            }
        }

        return implode("\n", array_merge($cssTags, $scriptTags, $preloadTags));
    }

    /**
     * Renders development-mode script tags pointing to the Vite dev server.
     *
     * Includes the `@vite/client` script when enabled, followed by one module script per entrypoint.
     *
     * @param array $entrypoints Normalized list of entrypoint paths.
     *
     * @return string Concatenated HTML script tags.
     *
     * @phpstan-param string[] $entrypoints
     */
    private function renderDevelopmentTags(array $entrypoints): string
    {
        $devServerUrl = $this->resolveDevServerUrl();
        $tags = [];

        if ($this->includeViteClient) {
            $tags[] = Html::jsFile($devServerUrl . '/@vite/client', ['type' => 'module']);
        }

        foreach ($entrypoints as $entrypoint) {
            $tags[] = Html::jsFile($devServerUrl . '/' . ltrim($entrypoint, '/'), ['type' => 'module']);
        }

        return implode("\n", $tags);
    }

    /**
     * Prepends the configured base URL to a manifest asset path.
     *
     * @param string $path Relative asset path from the manifest (e.g. `assets/app-abc123.js`).
     *
     * @return string Absolute URL suitable for use in HTML tags.
     */
    private function resolveAssetUrl(string $path): string
    {
        $baseUrl = rtrim(Yii::getAlias($this->baseUrl), '/');

        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Validates and returns the trimmed Vite dev server URL.
     *
     * @throws InvalidConfigException if `devServerUrl` is empty or not configured.
     * @return string Dev server base URL without trailing slash.
     */
    private function resolveDevServerUrl(): string
    {
        $devServerUrl = trim((string) $this->devServerUrl);

        if ($devServerUrl === '') {
            throw new InvalidConfigException(
                'The "devServerUrl" property must be configured when Vite development mode is enabled.',
            );
        }

        return rtrim($devServerUrl, '/');
    }
}
