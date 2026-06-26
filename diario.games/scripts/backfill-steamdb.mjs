import { firefox } from 'playwright';

const appid = process.argv[2] || '730';

const browser = await firefox.launch({
    headless: true,
    args: ['--no-sandbox']
});

const context = await browser.newContext({
    userAgent: 'Mozilla/5.0 (X11; Linux x86_64; rv:131.0) Gecko/20100101 Firefox/131.0',
    viewport: { width: 1280, height: 900 }
});

const page = await context.newPage();

// Intercept the API response
const apiData = new Promise((resolve) => {
    page.on('response', async (response) => {
        const url = response.url();
        if (url.includes('GetPlayerCountHistory')) {
            try {
                const json = await response.json();
                resolve(json);
            } catch {
                resolve(null);
            }
        }
    });
});

console.log(`Navigating to https://steamdb.info/app/${appid}/charts/...`);
await page.goto(`https://steamdb.info/app/${appid}/charts/`, {
    waitUntil: 'networkidle0',
    timeout: 45000
}).catch(e => console.log('Navigation warning:', e.message));

// Wait for the API data
const data = await Promise.race([
    apiData,
    new Promise(r => setTimeout(() => r(null), 20000))
]);

if (data) {
    console.log(`Got ${data.length} data points`);
    // Output as JSON for the PHP collector to consume
    process.stdout.write(JSON.stringify(data));
} else {
    // Check page state
    const title = await page.title();
    const url = page.url();
    const body = await page.evaluate(() => document.body?.innerText?.substring(0, 500) || 'no body');
    console.error(`No API data. Title: "${title}", URL: ${url}`);
    console.error(`Body preview: ${body}`);
    process.exit(1);
}

await browser.close();
