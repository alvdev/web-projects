<script>
	import AlfaFeatures from './AlfaFeatures.svelte';

	import HostingFeatures from './HostingFeatures.svelte';

	import HostingDesc from './HostingDesc.svelte';

	import BannerEntrepreneur from './BannerEntrepreneur.svelte';

	import WhatDoYouNeed from './components/WhatDoYouNeed.svelte';

	import DomainSearchBox from './components/DomainSearchBox.svelte';
	import HomeHeader from './components/HomeHeader.svelte';
</script>

<section>
		<div class="px-8 text-center">
			<h2 class="mb-12 title">
				El siguiente paso para emprender<br />en Internet es un sitio web
			</h2>
			<p class="mb-20 text-2xl leading-relaxed">
				<span class="block mb-2">
					<b>Tienes tres opciones para crear tus sitios web</b>: Con nuestro constructor web
					profesional (Alfa Builder).
				</span>
				<span class="block mb-2">
					Con el sitio web cuidadosamente diseñado para generar múltiples fuentes de ingresos (Alfa
					Drupal).
				</span>
				Con uno de los scripts de terceros que podrás instalar con un clic (Wordpress, Joomla, Moodle,
				etc)
			</p>
		</div>
		<div class="flex flex-col justify-between text-white bg-blue-500 md:flex-row">
			<div class="bg-blue-600 md:flex-1">
				<div class="px-8 py-16">
					<div class="items-center gap-4 mb-8 text-center md:flex">
						<div class="flex justify-center mb-4 fill-current md:mb-0">
							<svg class="h-28" role="img" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
								<title>HTML5 icon</title>
								<path
									d="M1.5 0h21l-1.91 21.563L11.977 24l-8.564-2.438L1.5 0zm7.031 9.75l-.232-2.718 10.059.003.23-2.622L5.412 4.41l.698 8.01h9.126l-.326 3.426-2.91.804-2.955-.81-.188-2.11H6.248l.33 4.171L12 19.351l5.379-1.443.744-8.157H8.531z"
								/>
							</svg>
						</div>
						<div class="uppercase">
							<h3 class="text-2xl font-extralight md:text-3xl">
								Webs ultrarápidas y
								<span class="md:block">páginas de aterrizaje</span>
							</h3>
							<b class="text-5xl">Alfa builder</b>
						</div>
					</div>
					<ul class="arrow-right">
						<li class="mb-6">
							Para principiantes sin ningún tipo de experiencia. Tu web en cuestión de minutos o
							pocas horas, no en semanas o meses.
						</li>
						<li class="mb-6">
							Para profesionales que buscan crear webs con rapidez y elegancia para presentar sus
							proyectos o para vender a sus propios clientes.
						</li>
						<li class="mb-6">
							Para todos. Desde crear efectivas páginas de captura para autoresponders o atractivas
							páginas de presentación de un producto, hasta sencillas tiendas online o webs para
							eventos.
						</li>
					</ul>
					<div class="mb-12 text-sm text-center md:text-left">
						* Incluido en todos los planes de alojamiento. Plantillas limitadas en el hosting
						gratuito.
					</div>
					<div class="flex flex-col justify-between gap-6 lg:flex-row">
						<button class="flex-1 p-4 text-sm font-semibold uppercase bg-black rounded ">
							Más información
						</button>
						<button
							class="flex-1 p-4 text-sm font-semibold text-blue-600 uppercase bg-white rounded "
						>
							Probar demostración
						</button>
					</div>
				</div>
			</div>
			<div class="md:flex-1">
				<div class="px-8 py-16">
					<div class="items-center gap-4 mb-8 text-center md:flex">
						<div class="flex justify-center mb-4 fill-current md:mb-0">
							<svg class="h-28" role="img" xmlns="http://www.w3.org/2000/svg" viewbox="0 0 24 24">
								<title>Drupal icon</title>
								<path
									d="M15.78 5.113C14.09 3.425 12.48 1.815 11.998 0c-.48 1.815-2.09 3.425-3.778 5.113-2.534 2.53-5.405 5.4-5.405 9.702a9.184 9.185 0 1018.368 0c0-4.303-2.871-7.171-5.405-9.702M6.72 16.954c-.563-.019-2.64-3.6 1.215-7.416l2.55 2.788a.218.218 0 01-.016.325c-.61.625-3.204 3.227-3.527 4.126-.066.186-.164.18-.222.177M12 21.677a3.158 3.158 0 01-3.158-3.159 3.291 3.291 0 01.787-2.087c.57-.696 2.37-2.655 2.37-2.655s1.774 1.988 2.367 2.649a3.09 3.09 0 01.792 2.093A3.158 3.158 0 0112 21.677m6.046-5.123c-.068.15-.223.398-.431.405-.371.014-.411-.177-.686-.583-.604-.892-5.864-6.39-6.848-7.455-.866-.935-.122-1.595.223-1.94C10.736 6.547 12 5.285 12 5.285s3.766 3.574 5.336 6.016c1.57 2.443 1.029 4.556.71 5.253"
								/>
							</svg>
						</div>
						<div class="uppercase">
							<h3 class="text-2xl font-extralight md:text-3xl">
								Marketing de nichos<span class="block">e ingresos pasivos</span>
							</h3>
							<b class="text-5xl">Alfa Drupal</b>
						</div>
					</div>
					<ul class="arrow-right">
						<li class="mb-6">
							Un portal completo para trabajar a diario en él y generar varias fuentes de ingresos
							sin limitaciones. Foros, blogs, encuestas...
						</li>
						<li class="mb-6">
							Optimizado milimétricamente para el posicionamiento web y la velocidad. No encontrarás
							un sitio web más rápido y efectivo.
						</li>
						<li class="mb-6">
							Ideal para el marketing de nichos y contenidos. Perfecto para Adsense y programas de
							afiliados. Aprende a configurar uno y sabrás crear cientos de fuentes de ingresos
							estables sin vender.
						</li>
					</ul>
					<div class="mb-12 text-sm text-center md:text-left">
						* Incluido gratis en los planes de hosting Alfa y Alfa Pro. PVP por separado: $297 USD
					</div>
					<div class="flex flex-col justify-between gap-6 lg:flex-row">
						<button class="flex-1 p-4 text-sm font-semibold uppercase bg-black rounded ">
							Más información
						</button>
						<button
							class="flex-1 p-4 text-sm font-semibold text-blue-600 uppercase bg-white rounded "
						>
							Ver demostración
						</button>
					</div>
				</div>
			</div>
		</div>
	</section>
