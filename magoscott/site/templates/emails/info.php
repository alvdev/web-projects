<?= $form->site()->title() ?>

<?php if (isset($body) && $body !== null): ?>
<?= $body['text'] ?>
<?php else: ?>
<?= tt('dreamform.actions.email.defaultTemplate.text', null, ['form' => $form->title()]) ?>
<?php endif; ?>

<?php if (!isset($body) || $body === null): ?>
——— Submission Details ———
<?php foreach ($fields = $form->fields()->filterBy(fn ($f) => $f::hasValue() && $f::type() !== 'file-upload') as $field) :
	$value = $submission->valueFor($field->key())?->escape();
	if (str_starts_with($value ?? "", 'page://')) {
		$page = \Kirby\Cms\App::instance()->site()->find($value);
		if ($page) {
			$value = $page->title();
		}
	}
	?>
<?= $field->label() ?>: <?= $value ?? "—" ?>
<?php endforeach; ?>
<?php endif; ?>

——— EL MAGO SCOTT ———
El Mago Scott te presenta sus nuevos espectáculos para esta nueva gira.
Desde hace 25 años el mago Scott lleva trabajando por todos los escenarios de España y del mundo. Ahora te presenta su nueva temporada con unos espectáculos renovados.

ESPECTÁCULOS:

* Espectáculo Las Vegas
Es el espectáculo más grande que jamás hayas visto, donde Scott, con su compañera de escena, hará las grandes ilusiones más espectaculares.
Ver ahora: https://www.youtube.com/watch?v=jmP4YLts-PA

* Espectáculo de magia de cerca con hipnosis
Un show diseñado para sentir la magia a pocos centímetros de tus ojos. Ideal para bodas, ferias, o eventos.
Ver ahora: https://www.youtube.com/watch?v=VzX_e26J3mI

* Show de Magia familiar
Un show donde los más pequeños y los más grandes disfrutarán de una magia dinámica y muy divertida.
Ver ahora: https://www.youtube.com/watch?v=X0YmU3Xf7O0

* Espectáculo para empresas
Adaptado para todo tipo de empresas. Se puede hacer magia corporativa con los productos específicos de la empresa.
Ver ahora: https://www.youtube.com/watch?v=LcCtWiCSRvQ

* Show del Trirelo con regalos
El juego de las tres cartas adaptado para pubs, discotecas o eventos de marca.
Ver ahora: https://www.youtube.com/watch?v=7CCKal-1L20

SHOWMAN PRESENTADOR:
EL MAGO SCOTT, CON SU INCONFUNDIBLE ESTILO DINÁMICO Y DIVERTIDO, LLEVA UN PASO MÁS ALLA LA PRESENTACIÓN DE CUALQUIER EVENTO.

ALGUNOS TRABAJOS DE TELEVISIÓN:
- Viaje mágico en telecinco: https://www.youtube.com/watch?v=1H-LD-iGg-w&t=5s
- Menuda Noche: https://www.youtube.com/watch?v=8b8_vL-iJ2M
- Gente Maravillosa: https://www.youtube.com/watch?v=TgNs5LVrQ3g
- Sálvame: https://www.youtube.com/watch?v=2zGjh6k2cQo&t=43s
- Sábado Deluxe: https://www.youtube.com/watch?v=joJyqAvFdVA

DESCARGA MI DOSSIER: https://drive.google.com/file/d/1_NWTZoDJlkpJbqL3bJlolrEFG-ivXz9n/view?usp=sharing

Sitio web: https://www.magoscott.com/
Facebook: https://www.facebook.com/magoscottoficial/
Instagram: https://www.instagram.com/magoscott/
TikTok: https://www.tiktok.com/@magoscott
