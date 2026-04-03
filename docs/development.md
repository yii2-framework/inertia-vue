# Development notes

## Scope

This package is a thin adapter over `yii2-framework/inertia`.

It intentionally does not include:

- Vue single-file components for your application pages.
- npm dependency installation.
- SSR setup.
- A replacement for classic Yii2 jQuery widgets.

## Adapter responsibility

The PHP-side responsibility of this package is to connect the base Inertia server package with a Vue/Vite frontend by
providing:

- a Vue-oriented bootstrap class;
- a root view that renders the page payload and asset tags;
- a Vite helper that understands the manifest and development server modes.

## Inertia v3 alignment

The package assumes the v3-style initial page payload output via a `<script type="application/json">` element, which
matches the current `yii2-framework/inertia` base package implementation.

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)
