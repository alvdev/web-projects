import { firefox } from 'playwright';

const appid = process.argv[2];
if (!appid) {
    console.error('Usage: node fetch-steamdb-peak.mjs <appid>');
    process.exit(1);
}

const URL = `https://steamdb.info/app/${appid}/charts/`;

const browser = await firefox.launch({ headless: true, args: ['--no-sandbox'] });
const page = await browser.newPage();

let apiJson = null;
page.on('response', async (r) => {
    if (r.url().includes('/api/GetGraphMax/')) {
        try { apiJson = await r.json(); } catch {}
    }
});

await page.goto(URL, { waitUntil: 'networkidle0', timeout: 45000 }).catch(() => {});

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
