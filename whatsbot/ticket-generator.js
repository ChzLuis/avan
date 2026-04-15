/**
 * ticket-generator.js
 * Uso: node ticket-generator.js --url=URL --output=/path/ticket.png
 */
const args = Object.fromEntries(
    process.argv.slice(2).map(a => { const [k,v]=a.replace('--','').split('='); return [k,v]; })
);

const puppeteer = require('puppeteer-core') || require('puppeteer');
const path      = require('path');
const fs        = require('fs');

const CHROMIUM_CANDIDATES = [
    '/usr/bin/chromium-browser',
    '/usr/bin/chromium',
    '/usr/bin/google-chrome',
    process.env.CHROMIUM_PATH,
].filter(Boolean);

async function generate() {
    const url    = args.url;
    const output = args.output || path.join(__dirname, 'ticket-out.png');

    if (!url) { console.error('--url requerido'); process.exit(1); }

    const executablePath = CHROMIUM_CANDIDATES.find(p => p && fs.existsSync(p));

    const browser = await (executablePath
        ? require('puppeteer-core').launch({ executablePath, args:['--no-sandbox','--disable-setuid-sandbox'], headless:true })
        : require('puppeteer').launch({ args:['--no-sandbox','--disable-setuid-sandbox'], headless:true })
    );

    const page = await browser.newPage();
    await page.setViewport({ width: 1200, height: 480, deviceScaleFactor: 2 });
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 15000 });
    await page.screenshot({ path: output, fullPage: false });
    await browser.close();

    console.log(output);
}

generate().catch(e => { console.error(e.message); process.exit(1); });
