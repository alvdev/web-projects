<?php require_once('config.php') ?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curso de hipnosis terapéutica</title>
    <link rel="shortcut icon" href="favicon.png" type="image/x-icon">
    <meta name="description" content="Curso de Hipnosis Terapeutica para Profesionales | Ayuda a tus pacientes a dejar de fumar, superar fobias, y mucho más. Contáctanos">
    <!-- Partytown Snippet -->
    <script>
      /* Partytown Configuration */
      partytown = {
        forward: ['dataLayer.push'], // Forward specific functions (e.g., Google Analytics)
        lib: '~partytown/', // Path to copied Partytown library files
        debug: false // Enable debug mode (optional)
      };
    </script>
    <!-- Load Partytown's main script -->
    <script src="dist/~partytown/partytown.js" defer></script>
    <link defer rel="stylesheet" href="<?= asset('main.min.css') ?>">
    <script defer src="<?= asset('main.min.js') ?>"></script>
    <script defer type="module" src="dist/liteyt.js"></script>
    <!-- Google tag (gtag.js) -->
    <script type="text/partytown" async src="https://www.googletagmanager.com/gtag/js?id=G-PEVQT77Q1C"></script>
    <script type="text/partytown">
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
    
      gtag('config', 'G-PEVQT77Q1C');
    </script>
</head>
