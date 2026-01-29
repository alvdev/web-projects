<!doctype html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
  <title>Mago Scott - Información</title>
  <!--[if !mso]><!-->
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <!--<![endif]-->
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style type="text/css">
    #outlook a {
      padding: 0;
    }

    body {
      margin: 0;
      padding: 0;
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      background-color: #ffffff;
    }

    table,
    td {
      border-collapse: collapse;
      mso-table-lspace: 0pt;
      mso-table-rspace: 0pt;
      box-sizing: border-box;
    }

    img {
      border: 0;
      height: auto;
      line-height: 100%;
      outline: none;
      text-decoration: none;
      -ms-interpolation-mode: bicubic;
      display: block;
      max-width: 100%;
    }

    p {
      display: block;
      margin: 0;
    }

    .container {
      max-width: 660px;
      margin: 0 auto;
      width: 100%;
    }

    .purple-bg {
      background-color: #982abc;
      color: #ffffff;
    }

    .light-purple-bg {
      background-color: #efcaff;
    }

    .btn {
      background-color: #000000;
      color: #ffffff !important;
      padding: 16px 28px;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      border-radius: 0px;
    }

    .btn-dossier {
      background-color: #61187c;
      color: #ffffff !important;
      padding: 16px 28px;
      text-decoration: none;
      display: inline-block;
      font-size: 16px;
      border-radius: 50px;
    }

    .subtitle {
      font-size: 28px;
      color: #2e063d;
      font-weight: bold;
      margin-bottom: 10px;
    }

    .text-center {
      text-align: center;
    }

    .padding-24 {
      padding: 24px;
    }

    .padding-12 {
      padding: 12px;
    }

    .col-stack {
      word-break: break-word;
    }

    @media only screen and (max-width:480px) {
      .col-stack {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
      }

      .image-stack {
        width: 100% !important;
        max-width: 187px !important;
        margin: 10px auto !important;
      }
    }
  </style>
  <!--[if mso]>
  <xml>
    <o:OfficeDocumentSettings>
      <o:AllowPNG/>
      <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
  </xml>
  <![endif]-->
</head>

<body>
  <div style="background-color:#ffffff;">

    <!-- Header Logo -->
    <table class="container light-purple-bg" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 12px 48px;">
          <a href="https://www.magoscott.com/" style="text-decoration:none;">
            <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/55fc6ee7-e15f-894d-a8dd-e162549b802e.png" width="131" alt="Mago Scott" style="width:131px;" />
          </a>
        </td>
      </tr>
    </table>

    <!-- Submission Details -->
    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td style="padding: 20px 24px 0; text-align: left;">
          <div style="font-size: 16px; line-height: 24px; color: #737373;">
            <?php if (isset($body) && $body !== null): ?>
              <?= $body['html'] ?>
            <?php else: ?>
              <?= tt('dreamform.actions.email.defaultTemplate.text', null, ['form' => "<strong>{$form->title()}</strong>"]) ?>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    </table>

    <?php if (!isset($body) || $body === null): ?>
      <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="margin-top: 20px;">
        <tr>
          <td style="padding: 24px; background-color: #f9f9f9; border-radius: 4px;">
            <?php foreach ($fields = $form->fields()->filterBy(fn($f) => $f::hasValue() && $f::type() !== 'file-upload') as $field) :
              $value = $submission->valueFor($field->key())?->escape();
              if (str_starts_with($value ?? "", 'page://')) {
                $page = \Kirby\Cms\App::instance()->site()->find($value);
                if ($page) {
                  $value = $page->title();
                }
              }
            ?>
              <div style="font-weight: 700; margin-bottom: 4px; color: #000000;"><?= $field->label() ?></div>
              <div style="color: #737373; margin-bottom: 12px;"><?= $value ?? "—" ?></div>
              <?php if ($fields->last() !== $field) : ?>
                <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 8px 0;" />
              <?php endif; ?>
            <?php endforeach ?>
          </td>
        </tr>
      </table>
    <?php endif; ?>

    <div style="height: 20px;">&nbsp;</div>

    <!-- Intro Section -->
    <table class="container purple-bg" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 12px 24px;">
          <p style="font-size: 18px; line-height: 24px; margin-bottom: 10px;">El Mago Scott te presenta sus nuevos espectáculos para esta nueva gira.</p>
          <p style="font-size: 18px; line-height: 24px; margin-bottom: 10px;">Te dejo todos los links y la información para que puedas ver el espectáculo que más se adapta para tu evento.</p>
          <p style="font-size: 18px; line-height: 24px; margin-bottom: 10px;">Los espectáculos están adaptados para todo tipo de eventos: Ayuntamientos, pub, discotecas, cóctel, eventos corporativos y de empresa.</p>
          <p style="font-size: 18px; line-height: 24px;">Si lo deseas déjame una dirección y te mando todo en papel.</p>
        </td>
      </tr>
    </table>

    <!-- Title: ESPECTÁCULOS -->
    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 24px;">
          <h2 style="font-size: 31px; margin: 0; font-weight: bold;">ESPECTÁCULOS</h2>
        </td>
      </tr>
    </table>

    <!-- Espectáculo Las Vegas -->
    <table class="container light-purple-bg" width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="66%" style="padding: 24px; vertical-align: top;">
          <div class="subtitle text-center">Espectáculo Las Vegas</div>
          <p class="text-center" style="font-size: 16px; line-height: 24px; margin-bottom: 10px;">Prepárate para vivir una experiencia única: magia de alto impacto, humor inteligente y un viaje directo al corazón de Las Vegas.</p>
          <p class="text-center" style="font-size: 16px; line-height: 24px; margin-bottom: 10px;">Un recorrido mágico por los casinos más emblemáticos, mesas de Blackjack llenas de misterio y momentos impredecibles donde la magia ocurre a centímetros del público.</p>
          <p class="text-center" style="font-size: 16px; line-height: 24px; margin-bottom: 10px;">En este espectáculo exclusivo de El Mago Scott, tú no solo verás magia… serás parte de ella. Cada persona se convierte en protagonista de una historia llena de sorpresas, ritmo y pura energía escénica.</p>
          <p class="text-center" style="font-size: 16px; line-height: 24px; margin-bottom: 10px;">¿Quieres viajar a Las Vegas sin salir del teatro? Este es tu billete.</p>
          <p class="text-center" style="font-size: 16px; line-height: 24px;">Y recuerda…<br>
            lo que pasa en Las Vegas… se queda en Las Vegas.</p>
        </td>
        <td class="col-stack" width="34%" style="padding: 24px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/dcd6abb0-7146-7227-e41a-525a35ad0a9a.jpg" width="187" class="image-stack" style="margin: 0 auto 15px;" />
          <a href="https://www.youtube.com/watch?v=UC5ihUVtuEE" class="btn">Ver ahora</a>
        </td>
      </tr>
    </table>

    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td style="padding: 20px 24px;">
          <div style="border-top: 2px solid #000000;"></div>
        </td>
      </tr>
    </table>

    <!-- Espectáculo de magia de cerca -->
    <table class="container" width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="34%" style="padding: 24px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/ecedc57e-baf2-79d9-73db-16ca6b1e20aa.jpg" width="187" class="image-stack" style="margin: 0 auto 15px;" />
          <a href="https://www.youtube.com/watch?v=grF3E1nV2bM" class="btn">Ver ahora</a>
        </td>
        <td class="col-stack" width="66%" style="padding: 24px; vertical-align: top;">
          <div class="subtitle text-center">Espectáculo de magia de cerca con hipnosis</div>
          <p class="text-center" style="font-size: 16px; line-height: 24px;">
            Este show está diseñado para que la gente sienta la magia a escasos centímetros de sus ojos, es un espectáculo donde se combina la magia con la hipnosis y se realiza en la misma mesa donde están los comensales. Este espectáculo es ideal para bodas, ferias o para cualquier evento donde quieras impresionar a tus clientes de una forma directa y personal.
          </p>
        </td>
      </tr>
    </table>

    <!-- Show de Magia familiar -->
    <table class="container light-purple-bg" width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="66%" style="padding: 24px; vertical-align: top;">
          <div class="subtitle text-center">Show de Magia familiar</div>
          <p class="text-center" style="font-size: 16px; line-height: 24px;">
            Espectáculo diseñado para el público familiar, donde los más pequeños y los más grandes disfrutarán de una magia dinámica y muy divertida con música y con muchas sorpresas, donde el público participa mucho con el mago Scott.
          </p>
        </td>
        <td class="col-stack" width="34%" style="padding: 24px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/568efc9a-7c4d-aa75-0186-9c53febb6bec.jpg" width="187" class="image-stack" style="margin: 0 auto 15px;" />
          <a href="https://www.youtube.com/watch?v=jmP4YLts-PA" class="btn">Ver ahora</a>
        </td>
      </tr>
    </table>

    <!-- Espectáculo para de empresa -->
    <table class="container" width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="34%" style="padding: 24px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/4106d6ba-cd54-7d01-4bfd-2c60641f610c.jpg" width="187" class="image-stack" style="margin: 0 auto 15px; max-width: 187px;" />
          <a href="https://www.youtube.com/watch?v=LcCtWiCSRvQ" class="btn">Ver ahora</a>
        </td>
        <td class="col-stack" width="66%" style="padding: 24px; vertical-align: top;">
          <div class="subtitle text-center">Espectáculo para empresas</div>
          <p class="text-center" style="font-size: 16px; line-height: 24px;">
            Este espectáculo está diseñado para todo tipo de eventos de empresas. También se puede hacer magia corporativa con los productos específicos de la empresa. Si quieres ser el número uno contrata al número uno en la magia, ya que el mago Scott hará tu fiesta un día especial.
          </p>
        </td>
      </tr>
    </table>

    <!-- Show del Trirelo -->
    <table class="container light-purple-bg" width="100%" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="66%" style="padding: 24px; vertical-align: top;">
          <div class="subtitle text-center">Show del Trirelo con regalos</div>
          <p class="text-center" style="font-size: 16px; line-height: 24px;">
            El espectáculo del trailero es el juego de las tres cartas que tiene que adivinar dónde está la de diferente color, es el juego prohibido con el que en la calle se juega con dinero, pero este espectáculo lo cree para que sea con regalos para pubs, discotecas o un evento con tu propia marca con tus propios regalos. Si necesitas más información manda un WhatsApp y te resuelvo todas tus dudas. 630818123
          </p>
        </td>
        <td class="col-stack" width="34%" style="padding: 24px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/ab2a9db4-7f62-06b9-bd4e-950842d48c82.jpg" width="187" class="image-stack" style="margin: 0 auto 15px; max-width: 187px;" />
          <a href="https://www.youtube.com/watch?v=7CCKal-1L20" class="btn">Ver ahora</a>
        </td>
      </tr>
    </table>

    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td style="padding: 20px 24px;">
          <div style="border-top: 2px solid #000000;"></div>
        </td>
      </tr>
    </table>

    <!-- Showman Section -->
    <table class="container purple-bg" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 12px 24px;">
          <h2 style="font-size: 30px; margin: 0 0 10px; font-weight: bold;">SHOWMAN PRESENTADOR</h2>
          <p style="font-size: 16px; line-height: 24px;">EL MAGO SCOTT, CON SU INCONFUNDIBLE ESTILO DINÁMICO Y DIVERTIDO, LLEVA UN PASO MÁS ALLÁ DE LA PRESENTACIÓN DE CUALQUIER EVENTO, DE CUALQUIER PRODUCTO Y DE CUALQUIER MARCA.</p>
        </td>
      </tr>
    </table>

    <!-- TV Section Title -->
    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 12px 24px;">
          <h2 style="font-size: 31px; margin: 0; font-weight: bold;">ALGUNOS TRABAJOS DE TELEVISIÓN</h2>
        </td>
      </tr>
    </table>

    <!-- TV Grid 1 -->
    <table class="container light-purple-bg" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="33%" style="padding: 12px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/00e3f58f-7441-818b-fe30-3571cd4d1f71.jpg" width="187" style="margin: 0 auto 10px; width: 100%; max-width: 187px;" />
          <p style="padding-bottom: 10px;">Viaje mágico en telecinco</p>
          <a href="https://www.youtube.com/watch?v=1H-LD-iGg-w&t=5s" class="btn" style="padding: 10px 15px; font-size: 14px;">Ver ahora</a>
        </td>
        <td class="col-stack" width="33%" style="padding: 12px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/ed21b53b-aba3-f182-2457-95fd40434d35.jpg" width="187" style="margin: 0 auto 10px; width: 100%; max-width: 187px;" />
          <p style="padding-bottom: 10px;">Programa 25 palabras</p>
          <a href="https://www.youtube.com/watch?v=167ePGzP2wg&t=1s" class="btn" style="padding: 10px 15px; font-size: 14px;">Ver ahora</a>
        </td>
        <td class="col-stack" width="33%" style="padding: 12px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/04ab566d-06d2-0e84-d064-dc53e5d78626.jpg" width="187" style="margin: 0 auto 10px; width: 100%; max-width: 187px;" />
          <p style="padding-bottom: 10px;">Gente Maravillosa</p>
          <a href="https://www.youtube.com/watch?v=TgNs5LVrQ3g" class="btn" style="padding: 10px 15px; font-size: 14px;">Ver ahora</a>
        </td>
      </tr>
    </table>

    <!-- TV Grid 2 -->
    <table class="container light-purple-bg" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="33%" style="padding: 12px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/c58cd328-8b9d-b076-cb7f-ded61a1be1af.jpg" width="181" style="margin: 0 auto 10px;" />
          <p style="padding-bottom: 10px;">Sálvame</p>
          <a href="https://www.youtube.com/watch?v=2zGjh6k2cQo&t=43s" class="btn" style="padding: 10px 15px; font-size: 14px;">Ver ahora</a>
        </td>
        <td class="col-stack" width="33%" style="padding: 12px; text-align: center; vertical-align: top;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/fce04ab5-bace-3769-b9b3-4f76a8bc1026.jpg" width="181" style="margin: 0 auto 10px;" />
          <p style="padding-bottom: 10px;">Sábado Deluxe</p>
          <a href="https://www.youtube.com/watch?v=joJyqAvFdVA" class="btn" style="padding: 10px 15px; font-size: 14px;">Ver ahora</a>
        </td>
        <td class="col-stack" width="33%" style="padding: 12px; text-align: center; vertical-align: top;">
          <img src="https://img.youtube.com/vi/gnzVQjFufj8/hqdefault.jpg" width="181" style="margin: 0 auto 10px;" />
          <p style="padding-bottom: 10px;">El Tiempo Justo</p>
          <a href="https://www.youtube.com/watch?v=gnzVQjFufj8" class="btn" style="padding: 10px 15px; font-size: 14px;">Ver ahora</a>
        </td>
      </tr>
    </table>

    <!-- Dossier -->
    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td class="col-stack" width="34%" style="padding: 24px; text-align: center; vertical-align: middle;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/3b06ec8f-e202-1977-f5c9-1ed21f85732d.jpeg" width="187" class="image-stack" style="margin: 0 auto;" />
        </td>
        <td class="col-stack" width="66%" style="padding: 24px; text-align: center; vertical-align: middle;">
          <div class="subtitle">DESCARGA MI DOSSIER</div>
          <a href="https://drive.google.com/file/d/1_NWTZoDJlkpJbqL3bJlolrEFG-ivXz9n/view?usp=sharing" class="btn-dossier">Link de visualización</a>
        </td>
      </tr>
    </table>

    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td style="padding: 20px 24px;">
          <div style="border-top: 2px solid #000000;"></div>
        </td>
      </tr>
    </table>

    <!-- Website Link -->
    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 12px 48px;">
          <a href="https://www.magoscott.com/" class="btn" style="width: 100%; box-sizing: border-box; text-align: center;">www.magoscott.com</a>
        </td>
      </tr>
    </table>

    <div style="height: 20px;">&nbsp;</div>

    <!-- Socials & Footer Logo -->
    <table class="container" align="center" border="0" cellpadding="0" cellspacing="0" role="presentation">
      <tr>
        <td align="center" style="padding: 12px;">
          <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width: auto;">
            <tr>
              <td style="padding: 0 10px;">
                <a href="https://www.facebook.com/magoscottoficial/?locale=es_ES"><img src="https://cdn-images.mailchimp.com/icons/social-block-v3/block-icons-v3/facebook-filled-dark-40.png" width="40" alt="Facebook" /></a>
              </td>
              <td style="padding: 0 10px;">
                <a href="https://www.instagram.com/magoscott/"><img src="https://cdn-images.mailchimp.com/icons/social-block-v3/block-icons-v3/instagram-filled-dark-40.png" width="40" alt="Instagram" /></a>
              </td>
              <td style="padding: 0 10px;">
                <a href="https://www.tiktok.com/@magoscott"><img src="https://cdn-images.mailchimp.com/icons/social-block-v3/block-icons-v3/tiktok-filled-dark-40.png" width="40" alt="TikTok" /></a>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td align="center" style="padding: 24px;">
          <img src="https://mcusercontent.com/780971879069aaa0159ad8608/images/55fc6ee7-e15f-894d-a8dd-e162549b802e.png" width="131" alt="Mago Scott Logo" />
        </td>
      </tr>
    </table>

  </div>
</body>

</html>
