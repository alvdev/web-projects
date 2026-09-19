import { readFileSync } from 'node:fs';

export function loadEnv(envPath) {
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

export function buildProxyUrl(env) {
    const host = env.PROXY_HOST, port = env.PROXY_PORT, user = env.PROXY_USER, pass = env.PROXY_PASS;
    if (!host || !port) return null;
    const p = { server: `http://${host}:${port}` };
    if (user && pass) {
        p.username = user;
        p.password = pass;
    }
    return p;
}

export function sleep(ms) {
    return new Promise((r) => setTimeout(r, ms));
}

function addPoints(points, seen, data) {
    if (!data) return;
    for (let i = 0; i < data.values.length; i++) {
        const secondTs = data.start + i * data.step;
        if (seen.has(secondTs)) continue;
        seen.add(secondTs);
        points.push([secondTs * 1000, data.values[i]]);
    }
}

export function mergeGraphPoints(dailyData, hourlyData, highstockRaw) {
    const points = [];
    const seen = new Set();
    addPoints(points, seen, hourlyData);
    addPoints(points, seen, dailyData);
    points.sort((a, b) => a[0] - b[0]);

    if (highstockRaw && highstockRaw.length > points.length) {
        const hs = [];
        for (const pt of highstockRaw) {
            if (!pt || pt.length < 2 || pt[1] <= 0) continue;
            hs.push([pt[0], pt[1]]);
        }
        hs.sort((a, b) => a[0] - b[0]);
        return hs;
    }

    return points;
}

export function mergeDailyHourlyPoints(dailyData, hourlyData) {
    const points = [];
    const seen = new Set();
    addPoints(points, seen, dailyData);
    addPoints(points, seen, hourlyData);
    points.sort((a, b) => a[0] - b[0]);
    return points;
}

export function computePeak(values, start, step) {
    let peak = 0;
    let maxIdx = 0;
    const length = values?.length ?? 0;
    for (let i = 0; i < length; i++) {
        if (values[i] > peak) {
            peak = values[i];
            maxIdx = i;
        }
    }
    if (peak === 0) return null;
    return { peak, timestamp: start + maxIdx * step };
}

export function parseDataTableRows(data, names, maxRows) {
    const num = (cell) => {
        if (cell && typeof cell === 'object') {
            const raw = cell['@data-sort'] !== undefined ? cell['@data-sort'] : cell.display;
            return parseInt(String(raw).replace(/[^\d]/g, ''), 10) || 0;
        }
        return parseInt(String(cell ?? '0').replace(/[^\d]/g, ''), 10) || 0;
    };

    const rows = [];
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
            name: names?.[i] ?? '',
            current: num(row[3]),
            peak_24h: num(row[4]),
            peak_all_time: num(row[5]),
        });
    }

    return rows;
}

export function parseDomPeak(text) {
    const match = String(text ?? '').match(/([\d,]+)\s*\n?\s*all-time/i);
    return match ? parseInt(match[1].replace(/,/g, ''), 10) : null;
}
