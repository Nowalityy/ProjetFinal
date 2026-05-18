import { defineConfig, loadEnv } from 'vite';
import react from '@vitejs/plugin-react';

/** Si le proxy envoie une URL absolue, ne garder que path + ?query pour Symfony */
function normalizeProxyPath(raw) {
  if (!raw || typeof raw !== 'string') return raw;
  if (raw.startsWith('http://') || raw.startsWith('https://')) {
    try {
      const u = new URL(raw);
      return u.pathname + u.search;
    } catch {
      /* ignore */
    }
  }
  return raw;
}

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  /** En Docker, Compose injecte VITE_PROXY_API_TARGET ; loadEnv lit surtout les fichiers .env */
  const env = loadEnv(mode, process.cwd(), '');
  const proxyApiTarget = process.env.VITE_PROXY_API_TARGET ?? env.VITE_PROXY_API_TARGET
    ?? 'http://127.0.0.1:8080';

  return {
    plugins: [react()],
    server: {
      host: true,
      allowedHosts: true,
      proxy: {
        '/api': {
          target: proxyApiTarget,
          changeOrigin: true,
          configure: (proxy) => {
            proxy.on('proxyReq', (proxyReq) => {
              const next = normalizeProxyPath(proxyReq.path);
              // eslint-disable-next-line no-param-reassign
              if (next !== proxyReq.path) proxyReq.path = next;
            });
          },
        },
      },
    },
    test: {
      globals: true,
      environment: 'happy-dom',
      setupFiles: './src/test/setupTests.js',
      coverage: {
        provider: 'v8',
        reporter: ['text', 'json-summary'],
        thresholds: {
          lines: 55,
          branches: 45,
          functions: 45,
          statements: 55,
        },
      },
    },
  };
});
