var RANGE_LABELS = {
    '48h': 'Gráfico de las últimas 48 horas',
    '1w': 'Gráfico de la última semana',
    '1m': 'Gráfico del último mes',
    '3m': 'Gráfico de los últimos 3 meses',
    '6m': 'Gráfico de los últimos 6 meses',
    '1y': 'Gráfico del último año',
    'max': 'Gráfico histórico completo'
};

var MONTHS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

var PAD = 40;

export function initShare(config) {
    var btn = document.querySelector(config.buttonSelector);
    if (!btn) return;

    btn.addEventListener('click', function () {
        if (btn.disabled) return;
        btn.textContent = 'Generando...';
        btn.disabled = true;

        composeImage(config)
            .then(function (blob) {
                return shareImage(blob, config.title, config.slug);
            })
            .catch(function (err) {
                console.error('Share error:', err);
                showToast('Error al generar la imagen');
            })
            .finally(function () {
                btn.textContent = 'Compartir';
                btn.disabled = false;
            });
    });
}

function composeImage(config) {
    var W = 1200;
    var H = 630;
    var dpr = Math.min(window.devicePixelRatio || 1, 2);

    var canvas = document.createElement('canvas');
    canvas.width = W * dpr;
    canvas.height = H * dpr;
    var ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);

    ctx.fillStyle = '#0a0a12';
    ctx.fillRect(0, 0, W, H);

    var grad = ctx.createRadialGradient(W * 0.7, H * 0.4, 0, W * 0.7, H * 0.4, W * 0.8);
    grad.addColorStop(0, 'rgba(0, 255, 255, 0.06)');
    grad.addColorStop(1, 'rgba(0, 0, 0, 0)');
    ctx.fillStyle = grad;
    ctx.fillRect(0, 0, W, H);

    var capsuleUrl = config.capsuleUrl;
    var chartImgSrc = config.chart.toBase64Image('image/png', 1.0);

    return Promise.all([
        loadImage(capsuleUrl),
        loadImage(chartImgSrc)
    ]).then(function (results) {
        var capsuleImg = results[0];
        var chartImg = results[1];

        // --- Header: capsule (30% left) + title + stats (right) ---

        var capsuleW = 0;
        var capsuleH = 0;
        var headerBottom = PAD;

        if (capsuleImg) {
            var maxCapsuleW = 320;
            var maxCapsuleH = 200;
            var cw = capsuleImg.width;
            var ch = capsuleImg.height;
            var scale = Math.min(maxCapsuleW / cw, maxCapsuleH / ch, 1);
            capsuleW = cw * scale;
            capsuleH = ch * scale;
            var cx = PAD;
            var cy = PAD;

            ctx.save();
            roundRect(ctx, cx, cy, capsuleW, capsuleH, 8);
            ctx.clip();
            ctx.drawImage(capsuleImg, cx, cy, capsuleW, capsuleH);
            ctx.restore();

            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.lineWidth = 1;
            roundRect(ctx, cx, cy, capsuleW, capsuleH, 8);
            ctx.stroke();

            headerBottom = PAD + capsuleH;
        }

        var rightX = PAD + 320 + 20;

        // Title
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 26px system-ui, -apple-system, sans-serif';
        ctx.fillText(config.title || '', rightX, 60);

        // Stats — 4 columns, each: label line1 / label line2 / value
        var data = config.data || {};
        var cols = [rightX, rightX + 210, rightX + 420, rightX + 630];
        var statLabel1 = ['Jugadores', 'Últimas', 'Últimos', 'Máximo'];
        var statLabel2 = ['actuales', '24 horas', '3 meses', 'histórico'];
        var statValues = [
            formatNumber(data.current),
            formatNumber(data.peak_24h),
            formatNumber(data.peak_3m),
            formatNumber(data.peak_all_time)
        ];
        var statColors = ['#00ffff', '#ff00aa', '#39ff14', '#facc15'];

        var statsTop = 95;
        for (var i = 0; i < 4; i++) {
            ctx.fillStyle = 'rgba(255,255,255,0.45)';
            ctx.font = '12px system-ui, -apple-system, sans-serif';
            ctx.fillText(statLabel1[i], cols[i], statsTop);

            ctx.fillText(statLabel2[i], cols[i], statsTop + 18);

            ctx.fillStyle = statColors[i];
            ctx.font = 'bold 20px system-ui, -apple-system, sans-serif';
            ctx.fillText(statValues[i], cols[i], statsTop + 44);
        }

        var headerWithStatsBottom = Math.max(headerBottom, statsTop + 60);
        headerBottom = headerWithStatsBottom;

        // --- Context line ---
        var ctxY = headerBottom + 25;
        var rangeText = RANGE_LABELS[config.range] || '';
        var dateText = formatShareDate(config.timezone);

        ctx.fillStyle = 'rgba(255,255,255,0.4)';
        ctx.font = '13px system-ui, -apple-system, sans-serif';
        ctx.fillText(rangeText + ' — ' + dateText, PAD, ctxY);

        // --- Body: sparkline full width ---
        if (chartImg) {
            var chartY = ctxY + 25;
            var chartX = PAD;
            var chartW = W - PAD * 2;
            var chartH = H - chartY - PAD - 25;
            ctx.drawImage(chartImg, chartX, chartY, chartW, chartH);
        }

        // --- Footer ---
        ctx.fillStyle = 'rgba(255,255,255,0.3)';
        ctx.font = '13px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText('diario.games/' + (config.slug || ''), W - PAD, H - 30);
        ctx.textAlign = 'start';

        return new Promise(function (resolve) {
            canvas.toBlob(resolve, 'image/png');
        });
    });
}

function formatShareDate(tz) {
    var now = new Date();
    var day = now.toLocaleDateString('es-ES', { day: 'numeric', timeZone: tz });
    var monthNum = now.toLocaleDateString('es-ES', { month: 'numeric', timeZone: tz }).split('/')[0];
    var month = MONTHS[parseInt(monthNum, 10) - 1] || '';
    var year = now.toLocaleDateString('es-ES', { year: 'numeric', timeZone: tz });
    var time = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', timeZone: tz, hour12: false });
    var offset = getUtcOffset(tz);
    return 'Compartido el ' + day + ' de ' + month + ' de ' + year + ' a las ' + time + ' UTC' + offset;
}

function getUtcOffset(tz) {
    try {
        var d = new Date();
        var utcParts = d.toLocaleString('en-US', { timeZone: 'UTC', hour: 'numeric', minute: 'numeric', hour12: false }).split(':');
        var tzParts = d.toLocaleString('en-US', { timeZone: tz, hour: 'numeric', minute: 'numeric', hour12: false }).split(':');
        var utcMin = parseInt(utcParts[0], 10) * 60 + parseInt(utcParts[1], 10);
        var tzMin = parseInt(tzParts[0], 10) * 60 + parseInt(tzParts[1], 10);
        var diff = tzMin - utcMin;
        if (diff > 720) diff -= 1440;
        if (diff < -720) diff += 1440;
        var h = Math.floor(Math.abs(diff) / 60);
        var m = Math.abs(diff) % 60;
        var sign = diff >= 0 ? '+' : '-';
        return sign + h + (m > 0 ? ':' + (m < 10 ? '0' : '') + m : '');
    } catch (e) {
        return '';
    }
}

function formatNumber(n) {
    if (n === null || n === undefined) return '-';
    if (n >= 1000000) return (n / 1000000).toFixed(2) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n.toLocaleString();
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y);
    ctx.arcTo(x + w, y, x + w, y + r, r);
    ctx.lineTo(x + w, y + h - r);
    ctx.arcTo(x + w, y + h, x + w - r, y + h, r);
    ctx.lineTo(x + r, y + h);
    ctx.arcTo(x, y + h, x, y + h - r, r);
    ctx.lineTo(x, y + r);
    ctx.arcTo(x, y, x + r, y, r);
    ctx.closePath();
}

function loadImage(src) {
    return new Promise(function (resolve) {
        if (!src) return resolve(null);
        var img = new Image();
        img.onload = function () { resolve(img); };
        img.onerror = function () { resolve(null); };
        img.src = src;
    });
}

function shareImage(blob, title, slug) {
    var file = new File([blob], slug + '-chart.png', { type: 'image/png' });
    var url = window.location.origin + '/' + slug;

    var shareData = {
        files: [file],
        title: title,
        text: title + ' — Jugadores en Steam',
        url: url
    };

    if (navigator.canShare && navigator.canShare(shareData)) {
        return navigator.share(shareData).catch(function (err) {
            if (err.name !== 'AbortError') throw err;
        });
    }

    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = slug + '-chart.png';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(a.href);

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
            showToast('Imagen descargada. Enlace copiado al portapapeles.');
        }).catch(function () {
            showToast('Imagen descargada.');
        });
    } else {
        showToast('Imagen descargada.');
    }

    return Promise.resolve();
}

function showToast(message) {
    var toast = document.createElement('div');
    toast.className = 'fixed bottom-4 left-1/2 -translate-x-1/2 z-[200] px-4 py-2 bg-surface border border-border rounded-lg text-sm text-text shadow-xl animate-pulse';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(function () {
        toast.remove();
    }, 3500);
}
