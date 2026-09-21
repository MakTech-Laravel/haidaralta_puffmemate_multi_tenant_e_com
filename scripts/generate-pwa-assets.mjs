import { copyFileSync, mkdirSync, writeFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';

const root = process.cwd();
const pwaDir = path.join(root, 'public', 'pwa');

mkdirSync(pwaDir, { recursive: true });

const result = spawnSync(
    'pnpm',
    ['exec', 'pwa-assets-generator', '--config', 'pwa-assets.config.ts'],
    {
        cwd: root,
        encoding: 'utf8',
        shell: true,
    },
);

if (result.stdout) {
    process.stdout.write(result.stdout);
}

if (result.stderr) {
    process.stderr.write(result.stderr);
}

if (result.status !== 0) {
    process.exit(result.status ?? 1);
}

const appleTouchSource = path.join(pwaDir, 'apple-touch-icon-180x180.png');
const appleTouchDest = path.join(root, 'public', 'apple-touch-icon.png');

copyFileSync(appleTouchSource, appleTouchDest);

const faviconSource = path.join(pwaDir, 'favicon.ico');
const faviconDest = path.join(root, 'public', 'favicon.ico');

copyFileSync(faviconSource, faviconDest);

const output = `${result.stdout ?? ''}\n${result.stderr ?? ''}`;
const links = [];

for (const tag of output.matchAll(/<link\b[^>]*rel="apple-touch-startup-image"[^>]*>/g)) {
    const href = tag[0].match(/href="([^"]+)"/)?.[1];
    const media = tag[0].match(/media="([^"]+)"/)?.[1];

    if (href && media) {
        links.push({ href, media });
    }
}

writeFileSync(
    path.join(pwaDir, 'apple-splash.json'),
    `${JSON.stringify(links, null, 4)}\n`,
);

console.log(
    `Copied apple-touch-icon.png, favicon.ico, and wrote ${links.length} splash link(s).`,
);
