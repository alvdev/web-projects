import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildProxyUrl, loadEnv, parseDataTableRows, sleep } from './lib/steamdb-parsers.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CHARTS_URL = process.env.CHARTS_URL || 'https://steamdb.info/charts/';
const limit = Math.max(100, parseInt(process.argv[2] || '1000', 10) || 1000);

const MAX_RETRIES = 3;
const RETRY_DELAY_MS = 3000;

const env = loadEnv(resolve(__dirname, '..', '.env'));
const proxy = buildProxyUrl(env);

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

        await page.route('**/*', (route) => {
            const type = route.request().resourceType();
            if (['image', 'font', 'stylesheet', 'media'].includes(type)) return route.abort();
            return route.continue();
        });

        page.on('response', (r) => {
            if (r.headers()['cf-mitigated'] === 'challenge') {
                gotCfChallenge = true;
                console.error('[scrape-charts] Cloudflare challenge detected (cf-mitigated header)');
            }
        });

        console.error(`[scrape-charts] Navigating to ${CHARTS_URL} (limit ${limit})...`);
        await page.goto(CHARTS_URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(e => {
            console.error(`[scrape-charts] Navigation warning: ${e.message}`);
        });

        const title = await page.title().catch(() => 'unknown');
        console.error(`[scrape-charts] Page title: "${title}"`);

        if (/just a moment|challenge|attention required/i.test(title)) {
            gotCfChallenge = true;
            console.error('[scrape-charts] Cloudflare challenge detected (page title)');
        }

        const hasCfElement = await page.evaluate(() => {
            return !!(document.querySelector('#challenge-form') ||
                      document.querySelector('#cf-challenge-running') ||
                      document.querySelector('#turnstile-wrapper'));
        });
        if (hasCfElement) {
            gotCfChallenge = true;
            console.error('[scrape-charts] Cloudflare challenge detected (DOM element)');
        }

        console.error('[scrape-charts] Waiting for DataTables data...');
        const deadline = Date.now() + 30000;
        let extracted = null;

        while (!extracted && Date.now() < deadline) {
            try {
                extracted = await page.evaluate((maxRows) => {
                try {
                    if (typeof window.$ !== 'function' || !window.$.fn || !window.$.fn.dataTable) return null;
                    const table = window.$('#table-apps');
                    if (!table.length || !window.$.fn.dataTable.isDataTable(table)) return null;

                    const api = table.DataTable();
                    const total = api.rows().count();
                    if (!total) return null;

                    const strip = (html) => {
                        const d = document.createElement('div');
                        d.innerHTML = html || '';
                        return d.textContent.replace(/\s+/g, ' ').trim();
                    };

                    const data = api.rows().data().toArray();
                    const names = data.map((row) => strip(Array.isArray(row) ? row[2] : ''));

                    return { total, data, names };
                } catch (e) {
                    return null;
                }
            }, limit);
            } catch (e) {
                console.error(`[scrape-charts] Extraction retry (${e.message.split('\n')[0]})`);
                extracted = null;
            }

            if (!extracted) await page.waitForTimeout(500);
        }

        if (extracted) {
            const rows = parseDataTableRows(extracted.data, extracted.names, limit);
            extracted = rows.length ? { total: extracted.total, rows } : null;
        }

        if (!extracted) {
            return { success: false, reason: gotCfChallenge ? 'cloudflare' : 'no-data' };
        }

        console.error(`[scrape-charts] Extracted ${extracted.rows.length} rows (${extracted.total} total entries)`);
        if (extracted.rows.length) {
            const first = extracted.rows[0];
            const last = extracted.rows[extracted.rows.length - 1];
            console.error(`[scrape-charts]   first: #${first.rank} ${first.name} (${first.appid}) current=${first.current}`);
            console.error(`[scrape-charts]   last:  #${last.rank} ${last.name} (${last.appid}) current=${last.current}`);
        }

        process.stdout.write(JSON.stringify({ total: extracted.total, rows: extracted.rows }));
        return { success: true };

    } catch (err) {
        console.error(`[scrape-charts] Error: ${err.message}`);
        return { success: false, reason: 'error', error: err.message };
    } finally {
        if (browser) {
            try { await browser.close(); } catch {}
        }
    }
}

console.error(`[scrape-charts] Launching Camoufox for charts (max ${MAX_RETRIES} retries)...`);

for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    if (attempt > 1) {
        const delay = RETRY_DELAY_MS + Math.floor(Math.random() * 3000);
        console.error(`[scrape-charts] Retry ${attempt}/${MAX_RETRIES} in ${(delay / 1000).toFixed(1)}s (fresh proxy IP)...`);
        await sleep(delay);
    }

    console.error(`[scrape-charts] Attempt ${attempt}/${MAX_RETRIES}`);
    const result = await attemptScrape();

    if (result.success) {
        process.exit(0);
    }

    if (result.reason === 'cloudflare') {
        console.error('[scrape-charts] Cloudflare blocked; will retry with new proxy IP');
    } else {
        console.error(`[scrape-charts] Failed: ${result.reason || 'unknown'}`);
    }
}

console.error(`[scrape-charts] All ${MAX_RETRIES} attempts exhausted`);
process.exit(1);
