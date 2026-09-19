import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildProxyUrl, loadEnv, mergeDailyHourlyPoints } from './lib/steamdb-parsers.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const appid = process.argv[2] || '730';

const env = loadEnv(resolve(__dirname, '..', '.env'));
const proxy = buildProxyUrl(env);

const options = await launchOptions({
    headless: true,
    os: 'linux',
    humanize: true,
    geoip: !!proxy,
    proxy: proxy || undefined,
    enable_cache: false,
});

const browser = await firefox.launch(options);
const context = await browser.newContext({ viewport: null });
const page = await context.newPage();

let dailyData = null;
let hourlyData = null;

page.on('response', async (response) => {
    const url = response.url();
    if (!url.includes('/api/')) return;
    try {
        const json = await response.json();
        if (!json?.success || !json?.data) return;
        if (url.includes('GetGraphMax')) {
            dailyData = json.data;
        } else if (url.includes('GetGraphWeek')) {
            hourlyData = json.data;
        }
    } catch {}
});

console.error(`Navigating to https://steamdb.info/app/${appid}/charts/...`);
await page.goto(`https://steamdb.info/app/${appid}/charts/`, {
    waitUntil: 'networkidle',
    timeout: 45000,
}).catch(e => console.error('Navigation warning:', e.message));

const deadline = Date.now() + 20000;
while ((!dailyData || !hourlyData) && Date.now() < deadline) {
    await new Promise(r => setTimeout(r, 500));
}

await browser.close();

if (!dailyData && !hourlyData) {
    console.error('No API data received');
    process.exit(1);
}

const points = mergeDailyHourlyPoints(dailyData, hourlyData);

console.error(`Got ${points.length} data points`);
process.stdout.write(JSON.stringify(points));
