import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const appid = process.argv[2];

if (!appid) {
    console.error('Usage: node fetch-steamdb-peak.mjs <appid>');
    process.exit(1);
}

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
    } catch {
        return {};
    }
}

function buildProxyUrl(env) {
    const host = env.PROXY_HOST;
    const port = env.PROXY_PORT;
    const user = env.PROXY_USER;
    const pass = env.PROXY_PASS;
    if (!host || !port) return null;
    if (user && pass) {
        return {
            server: `http://${host}:${port}`,
            username: user,
            password: pass,
        };
    }
    return { server: `http://${host}:${port}` };
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

let apiJson = null;
page.on('response', async (r) => {
    if (r.url().includes('/api/GetGraphMax/')) {
        try { apiJson = await r.json(); } catch {}
    }
});

await page.goto(`https://steamdb.info/app/${appid}/charts/`, {
    waitUntil: 'networkidle',
    timeout: 45000,
}).catch(() => {});

const waitStart = Date.now();
while (!apiJson && Date.now() - waitStart < 10000) {
    await new Promise(r => setTimeout(r, 500));
}

await browser.close();

if (!apiJson || !apiJson.success || !apiJson.data || !apiJson.data.values) {
    process.exit(1);
}

const { start, step, values } = apiJson.data;
let maxPeak = 0, maxIdx = 0;
for (let i = 0; i < values.length; i++) {
    if (values[i] > maxPeak) { maxPeak = values[i]; maxIdx = i; }
}

if (maxPeak === 0) process.exit(1);

console.log(JSON.stringify({ peak: maxPeak, timestamp: start + maxIdx * step }));
