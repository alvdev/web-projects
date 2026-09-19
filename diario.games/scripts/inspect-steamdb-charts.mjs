import { launchOptions } from 'camoufox-js';
import { firefox } from 'playwright-core';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { buildProxyUrl, loadEnv } from './lib/steamdb-parsers.mjs';

const __dirname = dirname(fileURLToPath(import.meta.url));
const CHARTS_URL = process.env.CHARTS_URL || 'https://steamdb.info/charts/?category=1';

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

await page.route('**/*', (route) => {
    const type = route.request().resourceType();
    if (['image', 'font', 'stylesheet', 'media'].includes(type)) return route.abort();
    return route.continue();
});

const xhrResponses = [];

page.on('response', async (response) => {
    const type = response.request().resourceType();
    if (type !== 'xhr' && type !== 'fetch') return;
    const url = response.url();
    const entry = { url, status: response.status(), body: null, json: false };
    try {
        const text = await response.text();
        entry.body = text.length > 4000 ? text.slice(0, 4000) + '…[truncated]' : text;
        try { JSON.parse(text); entry.json = true; } catch {}
    } catch {}
    xhrResponses.push(entry);
});

let cfChallenge = false;
page.on('response', (r) => {
    if (r.headers()['cf-mitigated'] === 'challenge') cfChallenge = true;
});

console.error(`[inspect-charts] Navigating to ${CHARTS_URL} ...`);
await page.goto(CHARTS_URL, { waitUntil: 'networkidle', timeout: 60000 }).catch(e => {
    console.error(`[inspect-charts] Navigation warning: ${e.message}`);
});

const title = await page.title().catch(() => 'unknown');
console.error(`[inspect-charts] Page title: "${title}"`);
if (/just a moment|challenge|attention required/i.test(title)) cfChallenge = true;

const domInfo = await page.evaluate(() => {
    const info = {
        tables: [],
        datatableSettings: null,
        scriptHints: [],
        showingText: null,
        url: location.href,
        paginationLinks: [],
        selects: [],
        windowKeys: [],
    };

    const showingMatch = document.body.innerText.match(/Showing\s+[\d,]+\s+to\s+[\d,]+\s+of\s+[\d,]+\s+entries/i);
    if (showingMatch) info.showingText = showingMatch[0];

    for (const table of document.querySelectorAll('table')) {
        const firstRow = table.querySelector('tbody tr');
        info.tables.push({
            id: table.id || null,
            className: table.className || null,
            headers: [...table.querySelectorAll('thead th')].map(th => th.innerText.trim()).slice(0, 12),
            rowCount: table.querySelectorAll('tbody tr').length,
            firstRowText: firstRow ? firstRow.innerText.replace(/\s+/g, ' ').trim().slice(0, 300) : null,
        });
    }

    try {
        if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.dataTable) {
            const api = jQuery.fn.dataTable;
            let tables = [];
            if (api.tables) tables = api.tables();
            else if (jQuery('table').length) tables = jQuery('table').toArray().filter(t => jQuery.fn.dataTable.isDataTable(t));
            info.datatableSettings = tables.map(t => {
                try {
                    const s = jQuery(t).DataTable().settings()[0];
                    return {
                        id: t.id || null,
                        serverSide: s.oFeatures ? s.oFeatures.bServerSide : null,
                        processing: s.oFeatures ? s.oFeatures.bProcessing : null,
                        pageLength: s._iDisplayLength,
                        lengthMenu: s.aLengthMenu,
                        ajaxUrl: s.sAjaxSource || (s.ajax && (typeof s.ajax === 'string' ? s.ajax : s.ajax.url)) || null,
                        ajaxParams: s.ajax && typeof s.ajax === 'object' ? Object.keys(s.ajax) : null,
                        dataLength: Array.isArray(s.aoData) ? s.aoData.length : null,
                        fnServerData: s.fnServerData ? String(s.fnServerData).slice(0, 500) : null,
                    };
                } catch (e) { return { id: t.id || null, error: String(e) }; }
            });
        }
    } catch (e) { info.datatableSettings = [{ error: String(e) }]; }

    for (const s of document.querySelectorAll('script:not([src])')) {
        const t = s.textContent || '';
        if (/serverSide|ajax|DataTable|pageLength|lengthMenu/.test(t)) {
            info.scriptHints.push({
                id: s.id || null,
                snippet: t.replace(/\s+/g, ' ').trim().slice(0, 800),
            });
        }
    }

    for (const a of document.querySelectorAll('a[href]')) {
        const href = a.getAttribute('href') || '';
        if (/[?&](page|p|start|length|entries)=/i.test(href) || /\/charts\/?\?/i.test(href)) {
            info.paginationLinks.push({ text: a.innerText.replace(/\s+/g, ' ').trim().slice(0, 40), href: a.href });
        }
        if (info.paginationLinks.length >= 30) break;
    }

    for (const s of document.querySelectorAll('select')) {
        info.selects.push({
            name: s.name || null,
            id: s.id || null,
            className: s.className || null,
            onchange: s.getAttribute('onchange'),
            options: [...s.options].slice(0, 15).map(o => ({ value: o.value, text: o.text.trim() })),
        });
    }

    info.windowKeys = Object.keys(window)
        .filter(k => /dataTable|DataTable|steamdb|SteamDB|page/i.test(k))
        .slice(0, 50);

    try {
        info.steamDBKeys = window.SteamDB ? Object.keys(window.SteamDB).slice(0, 60) : null;
    } catch (e) { info.steamDBKeys = String(e); }

    info.scriptSrcs = [...document.querySelectorAll('script[src]')]
        .map(s => s.getAttribute('src'))
        .filter(src => src && /steamdb|chart|app/i.test(src))
        .slice(0, 20);

    return info;
});

console.error('[inspect-charts] DOM info collected. Sample parsed rows:');

const sampleRows = await page.evaluate(() => {
    const rows = [];
    for (const tr of document.querySelectorAll('table tbody tr')) {
        const link = tr.querySelector('a[href*="/app/"]');
        const appidMatch = link ? link.getAttribute('href').match(/\/app\/(\d+)/) : null;
        const cells = [...tr.querySelectorAll('td')].map(td => td.innerText.replace(/\s+/g, ' ').trim());
        if (cells.length >= 4) {
            rows.push({ appid: appidMatch ? Number(appidMatch[1]) : null, cells: cells.slice(0, 8) });
        }
        if (rows.length >= 5) break;
    }
    return rows;
});

console.error(JSON.stringify(sampleRows, null, 2));

async function collectState() {
    return await page.evaluate(() => {
        const showingMatch = document.body.innerText.match(/Showing\s+[\d,]+\s+to\s+[\d,]+\s+of\s+[\d,]+\s+entries/i);
        const rows = [];
        for (const tr of document.querySelectorAll('table tbody tr')) {
            const link = tr.querySelector('a[href*="/app/"]');
            const appidMatch = link ? link.getAttribute('href').match(/\/app\/(\d+)/) : null;
            const cells = [...tr.querySelectorAll('td')].map(td => td.innerText.replace(/\s+/g, ' ').trim());
            if (cells.length >= 4) rows.push({ appid: appidMatch ? Number(appidMatch[1]) : null, cells: cells.slice(0, 8) });
            if (rows.length >= 3) break;
        }
        return {
            showing: showingMatch ? showingMatch[0] : null,
            rowCount: document.querySelectorAll('table tbody tr').length,
            rows,
        };
    });
}

const interaction = { enabled: process.env.INTERACT === '1', steps: [] };
if (interaction.enabled) {
    let xhrMark = xhrResponses.length;
    const nextBtn = await page.evaluate(() => {
        const btn = document.querySelector('.dt-paging-button.next, .paginate_button.next, button.next, a.next');
        if (!btn) return { found: false };
        const info = {
            found: true,
            tag: btn.tagName,
            className: btn.className,
            text: (btn.innerText || '').trim().slice(0, 20),
            disabled: btn.disabled || btn.classList.contains('disabled'),
        };
        btn.click();
        return info;
    });
    interaction.nextButton = nextBtn;
    await page.waitForTimeout(5000);
    interaction.afterNext = await collectState();
    interaction.xhrAfterNext = xhrResponses.slice(xhrMark);

    xhrMark = xhrResponses.length;
    const lengthChange = await page.evaluate(() => {
        const sel = document.querySelector('#dt-length-0, select.dt-input, select[name$="_length"]');
        if (!sel) return { found: false };
        sel.value = '1000';
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        return { found: true, value: sel.value };
    });
    interaction.lengthChange = lengthChange;
    await page.waitForTimeout(7000);
    interaction.afterLength1000 = await collectState();
    interaction.xhrAfterLength = xhrResponses.slice(xhrMark);
}

const out = {
    url: domInfo.url,
    title,
    cfChallenge,
    showingText: domInfo.showingText,
    tables: domInfo.tables,
    datatableSettings: domInfo.datatableSettings,
    scriptHints: domInfo.scriptHints,
    paginationLinks: domInfo.paginationLinks,
    selects: domInfo.selects,
    windowKeys: domInfo.windowKeys,
    steamDBKeys: domInfo.steamDBKeys,
    scriptSrcs: domInfo.scriptSrcs,
    sampleRows,
    interaction,
    xhrResponses,
};

console.log(JSON.stringify(out, null, 2));

await browser.close();
