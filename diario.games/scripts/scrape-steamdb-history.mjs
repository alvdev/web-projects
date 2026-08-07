import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const appid = process.argv[2];

if (!appid || !/^\d+$/.test(appid)) {
    console.error('Usage: node scrape-steamdb-history.mjs <appid>');
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

if (proxy) {
    console.error(`[scrape-steamdb] Using proxy: ${proxy.server}`);
}

console.error(`[scrape-steamdb] Launching Camoufox for appid ${appid}...`);

let browser;

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

    let dailyData = null;
    let hourlyData = null;

    page.on('response', async (response) => {
        const url = response.url();
        if (!url.includes('/api/')) return;

        try {
            const json = await response.json();
            if (!json?.success || !json?.data) return;

            if (url.includes('GetGraphMax')) {
                console.error(`[scrape-steamdb] Got daily data: ${json.data.values.length} points, step=${json.data.step}s`);
                dailyData = json.data;
            } else if (url.includes('GetGraphWeek')) {
                console.error(`[scrape-steamdb] Got hourly data: ${json.data.values.length} points, step=${json.data.step}s`);
                hourlyData = json.data;
            }
        } catch {}
    });

    console.error(`[scrape-steamdb] Navigating to https://steamdb.info/app/${appid}/charts/...`);
    await page.goto(`https://steamdb.info/app/${appid}/charts/`, {
        waitUntil: 'networkidle',
        timeout: 45000,
    }).catch(e => {
        console.error(`[scrape-steamdb] Navigation warning: ${e.message}`);
    });

    console.error('[scrape-steamdb] Waiting for chart data...');

    const deadline = Date.now() + 25000;
    while ((!dailyData || !hourlyData) && Date.now() < deadline) {
        await page.waitForTimeout(500);
    }

    if (!dailyData && !hourlyData) {
        const title = await page.title().catch(() => 'unknown');
        console.error(`[scrape-steamdb] No data received. Title: "${title}"`);
        process.exit(1);
    }

    // Try to extract the full historical dataset from Highstock's chart state.
    // GetGraphMax only provides ~1 year; the chart itself holds all years of data.
    let highstockRaw = null;
    const htsDeadline = Date.now() + 15000;
    while (Date.now() < htsDeadline) {
        highstockRaw = await page.evaluate(() => {
            try {
                if (typeof Highcharts !== 'undefined' && Highcharts.charts[0]) {
                    const s = Highcharts.charts[0].series[0];
                    if (s && s.options && s.options.data && s.options.data.length > 0) {
                        return s.options.data;
                    }
                }
            } catch {}
            return null;
        });
        if (highstockRaw && highstockRaw.length > 0) break;
        await page.waitForTimeout(1000);
    }

    if (highstockRaw) {
        console.error(`[scrape-steamdb] Highstock data: ${highstockRaw.length} points`);
        console.error(`[scrape-steamdb]   first: ${new Date(highstockRaw[0][0]).toISOString().slice(0, 10)}`);
        console.error(`[scrape-steamdb]   last:  ${new Date(highstockRaw[highstockRaw.length - 1][0]).toISOString().slice(0, 10)}`);
    }

    // Expand data into [[timestamp_ms, count], ...] pairs.
    // Merge hourly FIRST so real hourly values take priority over daily peaks
    // at shared timestamps (e.g. midnight). PHP uses INSERT OR IGNORE so first
    // writer wins — hourly collector values must not be overwritten by daily peaks.
    const points = [];
    const seenTs = new Set();

    if (hourlyData) {
        for (let i = 0; i < hourlyData.values.length; i++) {
            const secondTs = hourlyData.start + i * hourlyData.step;
            points.push([secondTs * 1000, hourlyData.values[i]]);
            seenTs.add(secondTs);
        }
    }

    if (dailyData) {
        for (let i = 0; i < dailyData.values.length; i++) {
            const secondTs = dailyData.start + i * dailyData.step;
            if (!seenTs.has(secondTs)) {
                points.push([secondTs * 1000, dailyData.values[i]]);
                seenTs.add(secondTs);
            }
        }
    }

    // Sort by timestamp ascending
    points.sort((a, b) => a[0] - b[0]);

    // Prefer Highstock data if it covers more history than the API merge
    if (highstockRaw && highstockRaw.length > points.length) {
        points.length = 0;
        for (const pt of highstockRaw) {
            if (!pt || pt.length < 2 || pt[1] <= 0) continue;
            points.push([pt[0], pt[1]]);
        }
        points.sort((a, b) => a[0] - b[0]);
        console.error(`[scrape-steamdb] Using Highstock data: ${points.length} data points`);
    }

    console.error(`[scrape-steamdb] Total: ${points.length} data points`);

    // Scrape the true all-time peak from the DOM (server-rendered, not in any API response)
    const domPeak = await page.evaluate(() => {
        const match = document.body.innerText.match(/([\d,]+)\s*\n?\s*all-time/i);
        return match ? parseInt(match[1].replace(/,/g, ''), 10) : null;
    });
    if (domPeak) {
        console.error(`[scrape-steamdb] DOM all-time peak: ${domPeak}`);
    }

    const output = { points, peak_all_time: domPeak || 0 };
    process.stdout.write(JSON.stringify(output));
    process.exit(0);
} catch (err) {
    console.error(`[scrape-steamdb] Fatal error: ${err.message}`);
    process.exit(1);
} finally {
    if (browser) {
        try { await browser.close(); } catch {}
    }
}
