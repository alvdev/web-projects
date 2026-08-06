import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const appid = process.argv[2] || '730';

function loadEnv() {
    const envPath = resolve(__dirname, '..', '.env');
    try {
        const content = readFileSync(envPath, 'utf-8');
        const env = {};
        for (const line of content.split('\n')) {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('#')) continue;
            const eqIdx = trimmed.indexOf('=');
            if (eqIdx === -1) continue;
            env[trimmed.slice(0, eqIdx)] = trimmed.slice(eqIdx + 1);
        }
        return env;
    } catch { return {}; }
}

function buildProxyUrl(env) {
    const host = env.PROXY_HOST, port = env.PROXY_PORT, user = env.PROXY_USER, pass = env.PROXY_PASS;
    if (!host || !port) return null;
    const p = { server: `http://${host}:${port}` };
    if (user && pass) { p.username = user; p.password = pass; }
    return p;
}

const env = loadEnv();
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

const points = [];
const seenTs = new Set();

if (dailyData) {
    for (let i = 0; i < dailyData.values.length; i++) {
        const ts = (dailyData.start + i * dailyData.step) * 1000;
        points.push([ts, dailyData.values[i]]);
        seenTs.add(dailyData.start + i * dailyData.step);
    }
}

if (hourlyData) {
    for (let i = 0; i < hourlyData.values.length; i++) {
        const secondTs = hourlyData.start + i * hourlyData.step;
        if (!seenTs.has(secondTs)) {
            points.push([secondTs * 1000, hourlyData.values[i]]);
            seenTs.add(secondTs);
        }
    }
}

points.sort((a, b) => a[0] - b[0]);

console.error(`Got ${points.length} data points`);
process.stdout.write(JSON.stringify(points));
