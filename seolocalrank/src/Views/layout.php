<?php
/**
 * @var string        $appUrl
 * @var string        $mapsKey
 * @var list<array>   $regions
 */
$appName = 'SEO Local Rank';
$description = 'Free local & international Google SERP checker. Pick a country + language, geocode any address, then jump straight to a localized Google search.';
?>
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= htmlspecialchars($appName) ?> · Local &amp; International Google SERP Checker</title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <meta name="theme-color" content="#1d4ed8">
    <meta property="og:title" content="<?= htmlspecialchars($appName) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($appUrl) ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='14' fill='%231d4ed8'/%3E%3Ctext x='16' y='22' text-anchor='middle' font-family='system-ui' font-size='18' fill='white' font-weight='700'%3ES%3C/text%3E%3C/svg%3E">

    <link rel="preconnect" href="https://rsms.me">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

    <link rel="stylesheet" href="/assets/css/app.css">

    <script>
        // Bump BUILD on every JS/CSS change so the browser reloads fresh
        // assets regardless of any intermediate cache layer.
        window.SEO_LOCAL_RANK_BUILD = 'v7-2026-06-16-address';
        window.SEO_LOCAL_RANK_CONFIG = {
            build: window.SEO_LOCAL_RANK_BUILD,
            appUrl: <?= json_encode($appUrl) ?>,
            defaultEndpoint: 'https://www.google.com/search',
        };
    </script>
</head>
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased">

<div class="min-h-full flex flex-col">

    <header class="border-b border-slate-200 bg-white/80 backdrop-blur sticky top-0 z-30">
        <div class="max-w-6xl mx-auto px-5 h-14 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2 font-semibold tracking-tight text-slate-900">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-brand-700 text-white text-xs font-bold">S</span>
                <span>SEO Local Rank</span>
            </a>
            <nav class="flex items-center gap-5 text-sm text-slate-600">
                <a href="https://github.com/" class="hover:text-slate-900" rel="noopener">GitHub</a>
                <a href="#howto" class="hover:text-slate-900">How to</a>
                <a href="#about"  class="hover:text-slate-900">About</a>
            </nav>
        </div>
    </header>

    <main class="flex-1">
        <?php require __DIR__ . '/home.php'; ?>
    </main>

    <!-- Visible build badge so the user can verify the latest JS is loaded. -->
    <div id="slr-build" style="position:fixed;bottom:8px;right:8px;background:#0f172a;color:#fff;font:11px system-ui;padding:4px 8px;border-radius:6px;opacity:.85;z-index:99999"></div>
    <script>
        (function () {
            var tag = document.getElementById('slr-build');
            // FILES_VERSION is bumped on every script/css change.
            var v = (window.SEO_LOCAL_RANK_BUILD || 'v?');
            tag.textContent = 'build ' + v;
            tag.title = 'If you still see the old behaviour, do a hard refresh (Ctrl+Shift+R).';
        })();
    </script>

    <footer class="border-t border-slate-200 bg-white">
        <div class="max-w-6xl mx-auto px-5 py-6 text-xs text-slate-500 flex flex-col sm:flex-row gap-2 justify-between">
            <p>SEO Local Rank · open-source SERP previewer</p>
            <p>Not affiliated with Google. Use responsibly.</p>
        </div>
    </footer>

</div>

<!-- Script order is critical: alpine MUST boot after serpChecker & the
     regions bundle have both been registered, otherwise Alpine walks the
     DOM, evaluates `x-data="serpChecker(...)"`, and gets
     "serpChecker is not defined".

     Defer queue runs in document order → alpine has to come LAST.

     Every asset URL carries a ?v= cache buster so the browser always
     fetches the fresh copy after a server-side change. -->
<script src="/assets/js/regions.generated.js?v=7" defer></script>
<script src="/assets/js/app.js?v=7" defer></script>
<script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js?v=7"></script>

<!-- No pre-loaded geocoding SDK — the JS hits the OSM endpoint
     directly from the browser. -->

</body>
</html>
