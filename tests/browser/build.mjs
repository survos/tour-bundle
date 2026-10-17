import { build } from 'esbuild';
import { fileURLToPath } from 'node:url';
await build({
    entryPoints: [fileURLToPath(new URL('./app.js', import.meta.url))],
    outfile: fileURLToPath(new URL('../../../../var/tour-fixture/app.js', import.meta.url)),
    bundle: true, format: 'esm', sourcemap: 'inline',
    nodePaths: [fileURLToPath(new URL('./node_modules', import.meta.url))],
});
