/**
 * Generates every PWA icon size from one source logo.
 *
 * The source is the club logo when it is present, and the Areen mark otherwise,
 * so an instance always ships a usable icon set and swapping in the club's own
 * logo is a matter of dropping the file in and re-running this script — no code
 * change, and no icon left behind at the wrong size.
 *
 *   npm run icons
 *
 * Rendering goes through the Chromium that Playwright already installs, so this
 * adds no image-processing dependency to the project.
 */
import { existsSync, mkdirSync, readFileSync, writeFileSync, rmSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const brandDir = resolve(root, 'public/brand');
const tmp = resolve(root, 'storage/framework/icon-build.html');

const THEME = '#1A2E34';       // manifest theme_color — the icon plate
const BACKGROUND = '#101F24';  // manifest background_color — the splash plate

const CLUB_LOGO = resolve(brandDir, 'logo-qaswarah.png');
const AREEN_MARK = resolve(brandDir, 'areen-mark.svg');

/**
 * Chromium ships in a couple of layouts depending on the image. Try each rather
 * than hard-coding one and failing on a machine where it moved.
 */
function findChromium() {
    const candidates = [
        '/opt/pw-browsers/chromium-1194/chrome-linux/chrome',
        '/opt/pw-browsers/chromium/chrome-linux/chrome',
        process.env.CHROMIUM_PATH,
    ].filter(Boolean);

    const found = candidates.find((path) => existsSync(path));

    if (! found) {
        console.error(
            '[areen] no Chromium found for icon rendering.\n' +
            '        Set CHROMIUM_PATH to a Chrome/Chromium binary and re-run.',
        );
        process.exit(1);
    }

    return found;
}

function resolveSource() {
    if (existsSync(CLUB_LOGO)) {
        const data = readFileSync(CLUB_LOGO).toString('base64');

        return {
            label: 'public/brand/logo-qaswarah.png (club logo)',
            // A raster logo is placed as an image so its own artwork is untouched.
            markup: (size) =>
                `<img src="data:image/png;base64,${data}" style="width:${size}px;height:${size}px;object-fit:contain">`,
        };
    }

    const svg = readFileSync(AREEN_MARK, 'utf8')
        .replace(/\swidth="\d+"/, '')
        .replace(/\sheight="\d+"/, '');

    return {
        label: 'public/brand/areen-mark.svg (Areen mark — club logo not present)',
        markup: (size) => `<div style="width:${size}px;height:${size}px">${svg}</div>`,
    };
}

function render(chromium, source, { file, size, plate, scale }) {
    const inner = Math.round(size * scale);

    writeFileSync(
        tmp,
        `<!doctype html><meta charset="utf-8">` +
        `<style>html,body{margin:0;padding:0}` +
        `body{width:${size}px;height:${size}px;background:${plate};display:grid;place-items:center}` +
        `svg,img,div{display:block}</style>` +
        source.markup(inner),
    );

    execFileSync(chromium, [
        '--headless',
        '--disable-gpu',
        '--no-sandbox',
        '--hide-scrollbars',
        '--default-background-color=00000000',
        `--window-size=${size},${size}`,
        `--screenshot=${resolve(brandDir, file)}`,
        `file://${tmp}`,
    ], { stdio: 'ignore' });

    console.log(`  ${file.padEnd(26)} ${size}×${size}`);
}

const chromium = findChromium();
const source = resolveSource();

mkdirSync(brandDir, { recursive: true });
mkdirSync(dirname(tmp), { recursive: true });

console.log(`[areen] generating icons from ${source.label}`);

/*
 * `scale` is the share of the canvas the artwork occupies. The maskable icon is
 * deliberately the smallest: Android crops maskable icons to whatever shape the
 * launcher uses, and only the middle 80% is guaranteed to survive.
 */
render(chromium, source, { file: 'icon-192.png', size: 192, plate: THEME, scale: 0.68 });
render(chromium, source, { file: 'icon-512.png', size: 512, plate: THEME, scale: 0.68 });
render(chromium, source, { file: 'icon-maskable-512.png', size: 512, plate: THEME, scale: 0.5 });
// iOS rounds the corners itself and does not composite transparency, so this one stays opaque.
render(chromium, source, { file: 'apple-touch-icon.png', size: 180, plate: THEME, scale: 0.66 });
render(chromium, source, { file: 'splash-logo.png', size: 512, plate: BACKGROUND, scale: 0.4 });

rmSync(tmp, { force: true });

console.log('[areen] done.');
