import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildProxyUrl, loadEnv, mergeGraphPoints, sleep } from './lib/steamdb-parsers.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const appid = process.argv[2];

if (!appid || !/^\d+$/.test(appid)) {
    console.error('Usage: node scrape-steamdb-history.mjs <appid>');
    process.exit(1);
}

const MAX_RETRIES = 3;
const RETRY_DELAY_MS = 3000;

const env = loadEnv(resolve(__dirname, '..', '.env'));
const proxy = buildProxyUrl(env);

if (proxy) {
    console.error(`[scrape-steamdb] Using proxy: ${proxy.server}`);
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

        let dailyData = null;
        let hourlyData = null;

        page.on('response', async (response) => {
            const url = response.url();
            const headers = response.headers();

            if (headers['cf-mitigated'] === 'challenge') {
                gotCfChallenge = true;
                console.error(`[scrape-steamdb] Cloudflare challenge detected (cf-mitigated header)`);
            }

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

        const title = await page.title().catch(() => 'unknown');
        console.error(`[scrape-steamdb] Page title: "${title}"`);

        if (/just a moment|challenge|attention required/i.test(title)) {
            gotCfChallenge = true;
            console.error(`[scrape-steamdb] Cloudflare challenge detected (page title)`);
        }

        const hasCfElement = await page.evaluate(() => {
            return !!(document.querySelector('#challenge-form') ||
                      document.querySelector('#cf-challenge-running') ||
                      document.querySelector('#turnstile-wrapper'));
        });
        if (hasCfElement) {
            gotCfChallenge = true;
            console.error(`[scrape-steamdb] Cloudflare challenge detected (DOM element)`);
        }

        if (gotCfChallenge) {
            return { success: false, reason: 'cloudflare' };
        }

        console.error('[scrape-steamdb] Waiting for chart data...');

        const deadline = Date.now() + 25000;
        while ((!dailyData || !hourlyData) && Date.now() < deadline) {
            await page.waitForTimeout(500);
        }

        if (!dailyData && !hourlyData) {
            console.error(`[scrape-steamdb] No data received. Title: "${title}"`);
            return { success: false, reason: 'no-data' };
        }

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

        const points = mergeGraphPoints(dailyData, hourlyData, highstockRaw);

        console.error(`[scrape-steamdb] Total: ${points.length} data points`);

        const domPeak = await page.evaluate(() => {
            const match = document.body.innerText.match(/([\d,]+)\s*\n?\s*all-time/i);
            return match ? parseInt(match[1].replace(/,/g, ''), 10) : null;
        });
        if (domPeak) {
            console.error(`[scrape-steamdb] DOM all-time peak: ${domPeak}`);
        }

        const output = { points, peak_all_time: domPeak || 0 };
        process.stdout.write(JSON.stringify(output));
        return { success: true };

    } catch (err) {
        console.error(`[scrape-steamdb] Error: ${err.message}`);
        return { success: false, reason: 'error', error: err.message };
    } finally {
        if (browser) {
            try { await browser.close(); } catch {}
        }
    }
}

console.error(`[scrape-steamdb] Launching Camoufox for appid ${appid} (max ${MAX_RETRIES} retries)...`);

for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    if (attempt > 1) {
        const delay = RETRY_DELAY_MS + Math.floor(Math.random() * 3000);
        console.error(`[scrape-steamdb] Retry ${attempt}/${MAX_RETRIES} in ${(delay / 1000).toFixed(1)}s (fresh proxy IP)...`);
        await sleep(delay);
    }

    console.error(`[scrape-steamdb] Attempt ${attempt}/${MAX_RETRIES}`);
    const result = await attemptScrape();

    if (result.success) {
        process.exit(0);
    }

    if (result.reason === 'cloudflare') {
        console.error(`[scrape-steamdb] Cloudflare blocked; will retry with new proxy IP`);
    } else {
        console.error(`[scrape-steamdb] Failed: ${result.reason || 'unknown'}`);
    }
}

console.error(`[scrape-steamdb] All ${MAX_RETRIES} attempts exhausted for appid ${appid}`);
process.exit(1);
