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
            // The same artwork as one URI, for the canvas the favicon is drawn in.
            dataUri: `data:image/png;base64,${data}`,
        };
    }

    const svg = readFileSync(AREEN_MARK, 'utf8')
        .replace(/\swidth="\d+"/, '')
        .replace(/\sheight="\d+"/, '');

    return {
        label: 'public/brand/areen-mark.svg (Areen mark — club logo not present)',
        markup: (size) => `<div style="width:${size}px;height:${size}px">${svg}</div>`,
        /*
         * The mark inherits `currentColor`, which a bare <img> has no value for,
         * so the brand colour is fixed into this copy — and only this copy. The
         * file on disk is left alone.
         */
        dataUri: 'data:image/svg+xml;base64,' + Buffer.from(
            svg.replace('<svg', '<svg fill="none" stroke="#61B5D1" color="#61B5D1"'),
        ).toString('base64'),
    };
}

/**
 * The browser-tab icon, and the one icon that has to be *small*.
 *
 * It cannot be screenshotted like the others: Chromium will not lay out a page
 * below roughly 500px and clips the artwork instead (see the note above the
 * render calls). Shipping the 512 render as the favicon is what happened
 * instead, and it put a 59 KB image on every single page load for a 16px slot.
 *
 * So the page is laid out at 512 — a size Chromium is happy with — and the
 * downscale is done inside it, by drawing the logo into a 48px canvas. The
 * result is read back out through `--dump-dom`, which is how the bytes cross
 * from the browser to here without an image library in between. 48px covers the
 * 16 and 32 the tab actually asks for, and the file lands around 4 KB.
 */
function renderFavicon(chromium, dataUri, size = 48) {
    if (! dataUri) return false;

    writeFileSync(
        tmp,
        `<!doctype html><meta charset="utf-8">` +
        `<body style="margin:0;width:512px;height:512px"><div id="out"></div><script>` +
        `const img = new Image();` +
        `img.onload = () => {` +
        `  const c = document.createElement('canvas');` +
        `  c.width = c.height = ${size};` +
        `  const x = c.getContext('2d');` +
        `  x.fillStyle = ${JSON.stringify(THEME)};` +
        `  x.fillRect(0, 0, ${size}, ${size});` +
        `  const s = Math.round(${size} * 0.8), o = Math.round((${size} - s) / 2);` +
        `  x.drawImage(img, o, o, s, s);` +
        `  document.getElementById('out').textContent = c.toDataURL('image/png');` +
        `};` +
        `img.src = ${JSON.stringify(dataUri)};` +
        `</script></body>`,
    );

    const dom = execFileSync(chromium, [
        '--headless',
        '--disable-gpu',
        '--no-sandbox',
        '--hide-scrollbars',
        '--virtual-time-budget=5000',
        '--dump-dom',
        `file://${tmp}`,
    ], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'], maxBuffer: 64 * 1024 * 1024 });

    const match = dom.match(/data:image\/png;base64,([A-Za-z0-9+/=]+)/);

    if (! match) return false;

    const out = resolve(root, 'public/favicon.png');
    writeFileSync(out, Buffer.from(match[1], 'base64'));

    console.log(`  ${'favicon.png'.padEnd(26)} ${size}×${size}`);

    return true;
}

function render(chromium, source, { file, size, plate, scale, dir = brandDir }) {
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
/*
 * Every icon is rendered at 512.
 *
 * Headless Chromium refuses to lay out a canvas much below 500px: it renders the
 * page at its own minimum and squashes the result into the requested frame, so a
 * 192px icon came out vertically compressed and off-centre — the logo visibly cut
 * through the middle. Rather than ship a distorted small icon, every size is
 * rendered at 512 and declared honestly in the manifest. Chrome only requires an
 * icon of at least 192 to consider a site installable, and downscaling a correct
 * 512 is something every platform already does well.
 */
render(chromium, source, { file: 'icon-512.png', size: 512, plate: THEME, scale: 0.68 });
render(chromium, source, { file: 'icon-maskable-512.png', size: 512, plate: THEME, scale: 0.5 });
// iOS rounds the corners itself and does not composite transparency, so this stays opaque.
render(chromium, source, { file: 'apple-touch-icon.png', size: 512, plate: THEME, scale: 0.66 });
render(chromium, source, { file: 'splash-logo.png', size: 512, plate: BACKGROUND, scale: 0.4 });

if (! renderFavicon(chromium, source.dataUri)) {
    console.warn('  favicon.png                could not be drawn — public/favicon.png left as it was');
}

rmSync(tmp, { force: true });

console.log('[areen] done.');
