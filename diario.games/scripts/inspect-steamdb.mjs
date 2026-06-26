import puppeteer from 'puppeteer-extra';
import StealthPlugin from 'puppeteer-extra-plugin-stealth';

puppeteer.use(StealthPlugin());

const appid = process.argv[2] || '730';
const url = `https://steamdb.info/app/${appid}/charts/`;

const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-blink-features=AutomationControlled']
});

const page = await browser.newPage();
await page.setViewport({ width: 1280, height: 900 });
await page.setUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36');

// Intercept XHRs
const xhrData = [];
page.on('response', async (response) => {
    const reqUrl = response.url();
    if (reqUrl.includes('GetPlayerCountHistory')) {
        try {
            const text = await response.text();
            console.log('FOUND DATA at', reqUrl);
            // Check if it's valid JSON
            try {
                const json = JSON.parse(text);
                console.log('Data points:', json.length);
                if (json.length > 0) {
                    console.log('First:', json[0]);
                    console.log('Last:', json[json.length - 1]);
                }
                process.stdout.write(JSON.stringify(json));
                browser.close();
                process.exit(0);
            } catch(e) {
                console.log('Response not JSON:', text.substring(0, 200));
            }
        } catch(e) {
            console.log('Error reading response:', e.message);
        }
    }
});

console.log('Navigating to', url);
try {
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 30000 });
} catch(e) {
    console.log('Navigation timeout/error:', e.message);
}

await new Promise(r => setTimeout(r, 5000));

// Check page title
const title = await page.title();
console.log('Page title:', title);

// Try to extract data from page variables
const data = await page.evaluate(() => {
    // Check various possible locations
    const checks = {};
    if (typeof App !== 'undefined') checks.App = typeof App;
    if (typeof app !== 'undefined') checks.app = typeof app;
    if (typeof ChartComponent !== 'undefined') checks.ChartComponent = typeof ChartComponent;
    // Check for JSON-LD
    const ld = document.querySelector('script[type="application/ld+json"]');
    if (ld) checks.jsonld = ld.textContent.substring(0, 200);
    // Check all script tags for chart data
    const scripts = document.querySelectorAll('script:not([src])');
    for (const s of scripts) {
        const t = (s.textContent || '');
        if (t.includes('data') && t.includes('[') && t.length > 1000) {
            checks['script_' + (s.id || 'anon')] = t.substring(0, 300);
        }
    }
    return checks;
});
console.log('Page data:', JSON.stringify(data, null, 2));

// Check for chart-canvas or similar
const hasCanvas = await page.evaluate(() => !!document.querySelector('canvas'));
console.log('Has canvas:', hasCanvas);

await browser.close();
if (!xhrData.length) {
    console.log('NO DATA FOUND');
}
