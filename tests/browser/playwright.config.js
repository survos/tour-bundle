import { defineConfig } from '@playwright/test';
export default defineConfig({
    testDir: '.', testMatch: '*.spec.js', fullyParallel: true, workers: 2,
    use: { baseURL: 'http://127.0.0.1:18763', channel: process.env.PW_CHANNEL, reducedMotion: 'reduce' },
    webServer: {
        command: 'node build.mjs && php -S 127.0.0.1:18763 ../Fixture/router.php',
        url: 'http://127.0.0.1:18763', reuseExistingServer: false,
    },
});
