# Development notes

## Scope

This package is a thin adapter over `yii2-extensions/inertia`.

It intentionally does not include.

- Vue single-file components for your application pages.
- npm dependency installation.
- SSR setup.
- A replacement for classic Yii2 jQuery widgets.

## Adapter responsibility

The PHP-side responsibility of this package is to connect the base Inertia server package with a Vue/Vite frontend by
providing.

- a Vue-oriented bootstrap class;
- a root view that renders the page payload and asset tags;
- a Vite helper that understands the manifest and development server modes.

## Inertia v3 alignment

The package assumes the v3-style initial page payload output via a `<script type="application/json">` element, which
matches the current `yii2-extensions/inertia` base package implementation.

## HMR / dev server workflow

`vite build` is a one-shot build: it emits hashed assets into `public/build/` and exits. Editing a `.vue` file after
`npm run build` has no effect on the browser until another build is run. To develop against live source, the Vite dev
server must be running and Yii2 must be in dev mode at the same time.

Run two processes side by side:

```bash
# Terminal 1 — Vite dev server (HMR websocket on :5173)
npm run dev

# Terminal 2 — Yii2 with YII_ENV=dev
YII_ENV=dev ./yii serve
```

### How the pieces connect

1. `public/index.php` reads the `YII_ENV` environment variable. The application configuration should flip
   `inertiaVue.devMode` based on that value (for example, `YII_ENV === 'dev'`).
2. When `devMode` is `true`, `\yii\inertia\Vite::renderDevelopmentTags()` emits, in order:
   - `<script type="module" src="{devServerUrl}/@vite/client">`, which opens the Vite HMR WebSocket;
   - `<script type="module" src="{devServerUrl}/{entrypoint}">` for each configured entrypoint.
3. Vite detects source changes, pushes module updates over the WebSocket, and `@vitejs/plugin-vue` performs Vue HMR on
   single-file components while preserving local state. No framework preamble is required.

### CORS and `server.origin`

The PHP application and the Vite dev server run on different origins (typically `http://localhost:8080` and
`http://localhost:5173`). Vite must advertise the dev server origin so the browser accepts cross-origin module
imports. Configure `server.origin` in `vite.config.js` to match the `devServerUrl` value on the PHP side:

```js
// vite.config.js
export default defineConfig({
  // ...
  server: {
    origin: "http://localhost:5173",
  },
});
```

If you change Vite's port, update `devServerUrl` in the PHP configuration to the same value.

### Troubleshooting

- **Browser shows 404 for `/@vite/client`** — the Vite dev server is not running, or `YII_ENV` is not `dev` and
  `inertiaVue.devMode` is still `false`. Confirm `npm run dev` is live and that the environment variable reached the
  PHP process.
- **Port 5173 already in use** — free the port, or change Vite's `server.port` and the PHP `devServerUrl` to the new
  value together. Mismatches silently break the page.
- **Mixed-content warnings over HTTPS** — either terminate TLS in front of Yii2 with the same protocol on both sides
  and enable `server.https` in `vite.config.js`, or run both over plain HTTP during development.
- **Stale assets after switching modes** — hard refresh the browser (Ctrl+Shift+R) after toggling `YII_ENV` so the
  browser discards the previous module graph.

### Switching back to production

```bash
# stop the Vite dev server, then:
npm run build
unset YII_ENV   # or: YII_ENV=prod
./yii serve
```

In production mode the Vite helper reads `public/build/.vite/manifest.json` and emits hashed asset tags; the dev server
is not contacted.

## Next steps

- 📚 [Installation Guide](installation.md)
- ⚙️ [Configuration Reference](configuration.md)
- 💡 [Usage Examples](examples.md)
- 🧪 [Testing Guide](testing.md)
