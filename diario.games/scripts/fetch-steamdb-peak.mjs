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

const MAX_RETRIES = 3;
const RETRY_DELAY_MS = 3000;

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

async function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function attemptScrape() {
    let browser;
    let gotCfChallenge = false;

    try {
        const options = await launchOptions({
            headless: true,
            os: 'linux',
            humanize: true,
            geoip: !!proxy,
            proxy: proxy || undefined,
            enable_cache: false,
        });

        browser = await firefox.launch(options);
        const context = await browser.newContext({ viewport: null });
        const page = await context.newPage();

        let apiJson = null;

        page.on('response', async (r) => {
            const headers = r.headers();

            if (headers['cf-mitigated'] === 'challenge') {
                gotCfChallenge = true;
                console.error(`[fetch-peak] Cloudflare challenge detected (cf-mitigated header)`);
            }

            if (r.url().includes('/api/GetGraphMax/')) {
                try { apiJson = await r.json(); } catch {}
            }
        });

        await page.goto(`https://steamdb.info/app/${appid}/charts/`, {
            waitUntil: 'networkidle',
            timeout: 45000,
        }).catch(() => {});

        const title = await page.title().catch(() => 'unknown');
        console.error(`[fetch-peak] Page title: "${title}"`);

        if (/just a moment|challenge|attention required/i.test(title)) {
            gotCfChallenge = true;
            console.error(`[fetch-peak] Cloudflare challenge detected (page title)`);
        }

        const hasCfElement = await page.evaluate(() => {
            return !!(document.querySelector('#challenge-form') ||
                      document.querySelector('#cf-challenge-running') ||
                      document.querySelector('#turnstile-wrapper'));
        });
        if (hasCfElement) {
            gotCfChallenge = true;
            console.error(`[fetch-peak] Cloudflare challenge detected (DOM element)`);
        }

        if (gotCfChallenge) {
            return { success: false, reason: 'cloudflare' };
        }

        const waitStart = Date.now();
        while (!apiJson && Date.now() - waitStart < 10000) {
            await sleep(500);
        }

        if (!apiJson || !apiJson.success || !apiJson.data || !apiJson.data.values) {
            return { success: false, reason: 'no-data' };
        }

        const { start, step, values } = apiJson.data;
        let maxPeak = 0, maxIdx = 0;
        for (let i = 0; i < values.length; i++) {
            if (values[i] > maxPeak) { maxPeak = values[i]; maxIdx = i; }
        }

        if (maxPeak === 0) {
            return { success: false, reason: 'zero-peak' };
        }

        console.log(JSON.stringify({ peak: maxPeak, timestamp: start + maxIdx * step }));
        return { success: true };

    } catch (err) {
        console.error(`[fetch-peak] Error: ${err.message}`);
        return { success: false, reason: 'error', error: err.message };
    } finally {
        if (browser) {
            try { await browser.close(); } catch {}
        }
    }
}

console.error(`[fetch-peak] Launching Camoufox for appid ${appid} (max ${MAX_RETRIES} retries)...`);

for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    if (attempt > 1) {
        const delay = RETRY_DELAY_MS + Math.floor(Math.random() * 3000);
        console.error(`[fetch-peak] Retry ${attempt}/${MAX_RETRIES} in ${(delay / 1000).toFixed(1)}s (fresh proxy IP)...`);
        await sleep(delay);
    }

    console.error(`[fetch-peak] Attempt ${attempt}/${MAX_RETRIES}`);
    const result = await attemptScrape();

    if (result.success) {
        process.exit(0);
    }

    if (result.reason === 'cloudflare') {
        console.error(`[fetch-peak] Cloudflare blocked; will retry with new proxy IP`);
    } else {
        console.error(`[fetch-peak] Failed: ${result.reason || 'unknown'}`);
    }
}

console.error(`[fetch-peak] All ${MAX_RETRIES} attempts exhausted for appid ${appid}`);
process.exit(1);
