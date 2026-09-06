/**
 * Membuat ulang seluruh aset logo MAXPORT dari SVG master.
 *
 *   npm i -D puppeteer-core        # sekali saja
 *   node tools/build-logo.mjs      # butuh Chrome/Edge terpasang
 *
 * Path browser bisa dioverride: CHROME_PATH=/path/to/chrome node tools/build-logo.mjs
 *
 * Sumber  : public/logo/maxport-mark.svg       (garis normal, dipakai ≥48px)
 *           public/logo/maxport-mark-bold.svg  (garis tebal, dipakai ≤48px)
 * Keluaran: PNG mark, lockup, favicon set, dan favicon.ico multi-ukuran.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import puppeteer from 'puppeteer-core';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PUBLIC = path.join(ROOT, 'public');

const CHROME_CANDIDATES = [
    process.env.CHROME_PATH,
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
].filter(Boolean);

const executablePath = CHROME_CANDIDATES.find((p) => fs.existsSync(p));

if (!executablePath) {
    console.error('Chrome tidak ditemukan. Set CHROME_PATH ke lokasi browser Anda.');
    process.exit(1);
}

const read = (file) => fs.readFileSync(path.join(PUBLIC, 'logo', file), 'utf8');
const MARK = read('maxport-mark.svg');
const MARK_BOLD = read('maxport-mark-bold.svg');

const browser = await puppeteer.launch({ executablePath, headless: 'new' });
const page = await browser.newPage();

/** Render SVG pada ukuran tertentu menjadi PNG dengan latar transparan. */
async function renderSvg(svg, size, out) {
    const html = `<!doctype html><meta charset="utf-8">
        <style>html,body{margin:0;background:transparent}
        svg{display:block;width:${size}px;height:${size}px}</style>${svg}`;

    await page.setViewport({ width: size, height: size, deviceScaleFactor: 1 });
    await page.setContent(html, { waitUntil: 'load' });

    const buf = await page.screenshot({ omitBackground: true, type: 'png' });
    fs.mkdirSync(path.dirname(out), { recursive: true });
    fs.writeFileSync(out, buf);
    console.log(`  ${path.relative(ROOT, out).padEnd(40)} ${size}x${size}`);

    return buf;
}

/** Lockup horizontal: mark + wordmark, memakai font yang sama dengan situs. */
async function renderLockup(out, { height, tagline }) {
    const html = `<!doctype html><meta charset="utf-8">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:500,800&display=block" rel="stylesheet">
        <style>
            html,body{margin:0;background:transparent}
            .lockup{display:inline-flex;width:max-content;align-items:center;gap:${Math.round(height * 0.2)}px}
            .mark{width:${height}px;height:${height}px;flex:none}
            .word{font-family:'Plus Jakarta Sans',sans-serif;line-height:1.05}
            .name{font-weight:800;font-size:${Math.round(height * 0.56)}px;letter-spacing:-0.02em;color:#1B1B1D}
            .tag{font-weight:500;font-size:${Math.round(height * 0.2)}px;color:#45464D;margin-top:${Math.round(height * 0.05)}px}
        </style>
        <div class="lockup">
            <div class="mark">${MARK.replace('width="512" height="512"', 'width="100%" height="100%"')}</div>
            <div class="word">
                <div class="name">MAXPORT</div>
                ${tagline ? `<div class="tag">${tagline}</div>` : ''}
            </div>
        </div>`;

    await page.setViewport({ width: 1400, height: 400, deviceScaleFactor: 3 });
    await page.setContent(html, { waitUntil: 'domcontentloaded' });
    await page.evaluate(() =>
        document.fonts.load("800 40px 'Plus Jakarta Sans'").then(() => document.fonts.ready),
    );

    const buf = await (await page.$('.lockup')).screenshot({ omitBackground: true, type: 'png' });
    fs.writeFileSync(out, buf);
    console.log(`  ${path.relative(ROOT, out).padEnd(40)} lockup @3x`);
}

/** ICO cukup membungkus payload PNG apa adanya — hanya headernya yang disusun. */
function buildIco(images) {
    const header = Buffer.alloc(6);
    header.writeUInt16LE(0, 0);              // reserved
    header.writeUInt16LE(1, 2);              // type: icon
    header.writeUInt16LE(images.length, 4);

    let offset = 6 + images.length * 16;
    const entries = images.map(({ size, buf }) => {
        const e = Buffer.alloc(16);
        e.writeUInt8(size >= 256 ? 0 : size, 0);   // width (0 berarti 256)
        e.writeUInt8(size >= 256 ? 0 : size, 1);   // height
        e.writeUInt8(0, 2);                        // jumlah warna palet
        e.writeUInt8(0, 3);                        // reserved
        e.writeUInt16LE(1, 4);                     // color planes
        e.writeUInt16LE(32, 6);                    // bit depth
        e.writeUInt32LE(buf.length, 8);
        e.writeUInt32LE(offset, 12);
        offset += buf.length;

        return e;
    });

    return Buffer.concat([header, ...entries, ...images.map((i) => i.buf)]);
}

console.log('Mark:');
await renderSvg(MARK, 512, path.join(PUBLIC, 'logo/maxport-mark-512.png'));
await renderSvg(MARK, 192, path.join(PUBLIC, 'logo/maxport-mark-192.png'));
await renderSvg(MARK, 80, path.join(PUBLIC, 'logo/maxport-mark-80.png'));

console.log('Favicon:');
const ico48 = await renderSvg(MARK_BOLD, 48, path.join(PUBLIC, 'favicon-48x48.png'));
const ico32 = await renderSvg(MARK_BOLD, 32, path.join(PUBLIC, 'favicon-32x32.png'));
const ico16 = await renderSvg(MARK_BOLD, 16, path.join(PUBLIC, 'favicon-16x16.png'));
await renderSvg(MARK_BOLD, 180, path.join(PUBLIC, 'apple-touch-icon.png'));

fs.writeFileSync(
    path.join(PUBLIC, 'favicon.ico'),
    buildIco([
        { size: 16, buf: ico16 },
        { size: 32, buf: ico32 },
        { size: 48, buf: ico48 },
    ]),
);
console.log('  public/favicon.ico                       16+32+48');

console.log('Lockup:');
await renderLockup(path.join(PUBLIC, 'logo/maxport-lockup.png'), { height: 96, tagline: null });
await renderLockup(path.join(PUBLIC, 'logo/maxport-lockup-tagline.png'), { height: 96, tagline: 'Export Compliance' });

await browser.close();
console.log('Selesai.');
