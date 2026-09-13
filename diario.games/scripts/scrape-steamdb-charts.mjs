import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CHARTS_URL = process.env.CHARTS_URL || 'https://steamdb.info/charts/';
const limit = Math.max(100, parseInt(process.argv[2] || '1000', 10) || 1000);

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
                    const num = (cell) => {
                        if (cell && typeof cell === 'object') {
                            const raw = cell['@data-sort'] !== undefined ? cell['@data-sort'] : cell.display;
                            return parseInt(String(raw).replace(/[^\d]/g, ''), 10) || 0;
                        }
                        return parseInt(String(cell ?? '0').replace(/[^\d]/g, ''), 10) || 0;
                    };

                    const rows = [];
                    const data = api.rows().data().toArray();
                    for (let i = 0; i < data.length && rows.length < maxRows; i++) {
                        const row = data[i];
                        if (!Array.isArray(row) || row.length < 6) continue;
                        const logoHtml = String(row[1] ?? '');
                        const nameHtml = String(row[2] ?? '');
                        const match = (logoHtml + ' ' + nameHtml).match(/\/app\/(\d+)\//);
                        if (!match) continue;

                        rows.push({
                            rank: rows.length + 1,
                            appid: parseInt(match[1], 10),
                            name: strip(nameHtml),
                            current: num(row[3]),
                            peak_24h: num(row[4]),
                            peak_all_time: num(row[5]),
                        });
                    }

                    if (!rows.length) return null;
                    return { total, rows };
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
