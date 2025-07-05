<?php require_once('config.php') ?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Curso de hipnosis terapéutica</title>
  <link rel="shortcut icon" href="favicon.svg" type="image/x-icon">
  <meta name="description" content="Curso de Hipnosis Terapeutica para Profesionales | Ayuda a tus pacientes a dejar de fumar, superar fobias, y mucho más. Contáctanos">

  <!-- Consent Mode defaults & load stored choice -->
  <script>
    window.dataLayer = window.dataLayer || [];

    // 1. Read stored consent (null / "granted" / "denied")
    const stored = localStorage.getItem('gtm_consent');

    if (stored) {
      // 2a. If we have a stored choice, push it right away
      window.dataLayer.push({
        event: 'consent_update',
        ad_storage: stored,
        analytics_storage: stored,
        functionality_storage: stored,
        personalization_storage: stored,
        security_storage: 'granted'
      });
    } else {
      // 2b. No stored choice → default all non‑essential to denied
      window.dataLayer.push({
        event: 'consent_default',
        ad_storage: 'denied',
        analytics_storage: 'denied',
        functionality_storage: 'denied',
        personalization_storage: 'denied',
        security_storage: 'granted'
      });
    }
  </script>

  <!-- Google Tag Manager -->
  <script>
    (function(w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({
        'gtm.start': new Date().getTime(),
        event: 'gtm.js'
      });
      var f = d.getElementsByTagName(s)[0],
        j = d.createElement(s),
        dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-PZMN6DP3');
  </script>
  <!-- End Google Tag Manager -->

  <link defer rel="stylesheet" href="<?= asset('main.min.css') ?>">
  <script defer src="<?= asset('main.min.js') ?>"></script>
  <script defer type="module" src="dist/liteyt.js"></script>
</head>
