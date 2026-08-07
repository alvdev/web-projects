var RANGE_LABELS = {
    '48h': 'Últimas 48 horas',
    '1w': 'Última semana',
    '1m': 'Último mes',
    '3m': 'Últimos 3 meses',
    '6m': 'Últimos 6 meses',
    '1y': 'Último año',
    'max': 'Máximo histórico'
};

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

        if (capsuleImg) {
            var maxCapsuleW = 440;
            var maxCapsuleH = 200;
            var cw = capsuleImg.width;
            var ch = capsuleImg.height;
            var scale = Math.min(maxCapsuleW / cw, maxCapsuleH / ch, 1);
            var drawW = cw * scale;
            var drawH = ch * scale;
            var cx = 40;
            var cy = (H - drawH) / 2;

            ctx.save();
            roundRect(ctx, cx, cy, drawW, drawH, 8);
            ctx.clip();
            ctx.drawImage(capsuleImg, cx, cy, drawW, drawH);
            ctx.restore();

            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.lineWidth = 1;
            roundRect(ctx, cx, cy, drawW, drawH, 8);
            ctx.stroke();
        }

        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 28px system-ui, -apple-system, sans-serif';
        var titleX = 520;
        ctx.fillText(config.title || '', titleX, 90);

        if (chartImg) {
            var chartX = 510;
            var chartY = 120;
            var chartW = 660;
            var chartH = 280;
            ctx.drawImage(chartImg, chartX, chartY, chartW, chartH);
        }

        var rangeLabel = RANGE_LABELS[config.range] || '';
        if (rangeLabel) {
            ctx.fillStyle = 'rgba(255,255,255,0.5)';
            ctx.font = '14px system-ui, -apple-system, sans-serif';
            ctx.textAlign = 'right';
            ctx.fillText(rangeLabel, 1170, 115);
            ctx.textAlign = 'start';
        }

        var data = config.data || {};
        var statsY = 430;
        var statsX = 520;
        var colW = 310;

        drawStat(ctx, '\u{1F464}', 'Ahora', formatNumber(data.current), statsX, statsY, '#00ffff');
        drawStat(ctx, '\u{1F4C8}', 'Pico 24h', formatNumber(data.peak_24h), statsX + colW, statsY, '#ff00aa');
        drawStat(ctx, '\u{1F4CA}', 'Pico 3m', formatNumber(data.peak_3m), statsX, statsY + 70, '#39ff14');
        drawStat(ctx, '\u{1F3C6}', 'Max. histórico', formatNumber(data.peak_all_time), statsX + colW, statsY + 70, '#facc15');

        ctx.fillStyle = 'rgba(255,255,255,0.3)';
        ctx.font = '14px system-ui, -apple-system, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText('diario.games/' + (config.slug || ''), 1170, H - 30);
        ctx.textAlign = 'start';

        return new Promise(function (resolve) {
            canvas.toBlob(resolve, 'image/png');
        });
    });
}

function drawStat(ctx, icon, label, value, x, y, color) {
    ctx.fillStyle = '#ffffff';
    ctx.font = '14px system-ui, -apple-system, sans-serif';
    ctx.fillText(icon + ' ' + label, x, y);

    ctx.fillStyle = color;
    ctx.font = 'bold 22px system-ui, -apple-system, sans-serif';
    ctx.fillText(value, x, y + 28);
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
