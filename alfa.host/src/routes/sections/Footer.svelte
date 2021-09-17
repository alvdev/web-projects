<script>
  import BlogFaq from './PostsFaqs.svelte';

  import HostingApps from './HostingApps.svelte';

  import MarketingServices from './MarketingServices.svelte';

	import DigitalNeurs from './DigitalNeurs.svelte';

	import EmailMarketing from './EmailMarketing.svelte';

	import BannerWebsiteBuilder from './BannerWebsiteBuilder.svelte';

	import WebsiteBuilders from './WebsiteBuilders.svelte';

	import AlfaFeatures from './AlfaFeatures.svelte';

	import HostingFeatures from './HostingFeatures.svelte';

	import HostingDesc from './HostingDesc.svelte';

	import BannerEntrepreneur from './BannerEntrepreneur.svelte';

	import WhatDoYouNeed from './components/WhatDoYouNeed.svelte';

	import DomainSearchBox from './components/DomainSearchBox.svelte';

	import HomeHeader from './components/HomeHeader.svelte';
</script>

<footer class="bg-blue-600 bg-bottom bg-cover md:bg-auto shadow-2xl">
	<div class="items-center justify-center gap-8 p-8 news md:flex">
		<h2 class="text-6xl font-normal text-white uppercase">
			Alfa News
			<span class="hidden md:inline">&rsaquo;&rsaquo;</span>
		</h2>
		<div class="">
			<p class="mb-4 text-center text-white">
				Recibe gratis por email trucos y consejos para hacer crecer tu negocio digital
			</p>
			<form class="inline-flex justify-around w-full gap-2" action="./">
				<input class="w-full p-3 rounded" type="text" placeholder="Introduce tu email" />
				<button class="w-full p-3 text-white uppercase bg-black rounded "> suscribirse </button>
			</form>
		</div>
	</div>

	<div class="px-8 py-12 md:flex">
		<div class="grid grid-cols-1 gap-8 text-white md:grid-cols-3 md:w-5/6">
			<section class="tab" id="hosting">
				<h3 class="mb-2 text-lg font-semibold">
					<a href="#hosting">Alojamiento web</a>
				</h3>
				<ul class="dropdown">
					<li>
						<a class="text-sm leading-8" href="#hosting">Hosting Alfa 1</a>
					</li>
					<li>
						<a class="text-sm leading-8" href="#hosting">Hosting Alfa 2</a>
					</li>
					<li>
						<a class="text-sm leading-8" href="#hosting">Hosting Alfa 3</a>
					</li>
					<li>
						<a class="text-sm leading-8" href="#hosting">Hosting Alfa 1 Pro</a>
					</li>
					<li>
						<a class="text-sm leading-8" href="#hosting">Hosting Alfa 2 Pro</a>
					</li>
					<li>
						<a class="text-sm leading-8" href="#hosting">Hosting Alfa 3 Pro</a>
					</li>
					<li>
						<a class="text-sm leading-8" href="#hosting">Tiendas eCommerce</a>
					</li>
				</ul>
			</section>
			<section class="tab" id="marketing">
				<h3 class="mb-2 text-lg font-semibold">
					<a href="#marketing">Marketing y desarrollo</a>
				</h3>
				<ul class="dropdown">
					<li class="text-sm leading-8">
						<a href="./">Diseño web</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Email marketing</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Integraciones web</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Mantenimiento web</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Posicionamiento web</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Creación de contenidos</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Social Media Marketing</a>
					</li>
				</ul>
			</section>
			<section class="tab" id="company">
				<h3 class="mb-2 text-lg font-semibold">
					<a href="#company">Nuestra empresa</a>
				</h3>
				<ul class="dropdown">
					<li class="text-sm leading-8">
						<a href="./">Sobre nosotros</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Prensa y medios</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Trabaja con nosotros</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Alianzas estratégicas</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Programa de afiliados</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Testimonios de clientes</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Noticias, consejos y blogs</a>
					</li>
				</ul>
			</section>
			<section id="social">
				<h3 class="mb-2 text-lg font-semibold">Sociales y sociables</h3>
				<p class="mb-6 text-sm text-white">
					Mantente al día de la actualidad de iWolf Host siguiéndonos en las redes sociales y
					uniéndote a nuestra comunidad.
				</p>
				<div class="flex items-center gap-4">
					<div class="p-3 bg-black rounded-full">
						<svg
							class="w-4 h-4"
							aria-hidden="true"
							focusable="false"
							data-prefix="fab"
							data-icon="facebook-f"
							role="img"
							xmlns="http://www.w3.org/2000/svg"
							viewbox="0 0 320 512"
						>
							<path
								fill="currentColor"
								d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"
							/>
						</svg>
					</div>

					<div class="p-3 bg-black rounded-full">
						<svg
							class="w-4 h-4"
							aria-hidden="true"
							focusable="false"
							data-prefix="fab"
							data-icon="twitter"
							role="img"
							xmlns="http://www.w3.org/2000/svg"
							viewbox="0 0 512 512"
						>
							<path
								fill="currentColor"
								d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 20.791-32.161 39.308-52.628 54.253z"
							/>
						</svg>
					</div>

					<div class="p-3 bg-black rounded-full">
						<svg
							class="w-4 h-4"
							aria-hidden="true"
							focusable="false"
							data-prefix="fab"
							data-icon="youtube"
							role="img"
							xmlns="http://www.w3.org/2000/svg"
							viewbox="0 0 576 512"
						>
							<path
								fill="currentColor"
								d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.5 48.284 47.821C117.22 448 288 448 288 448s170.78 0 213.371-11.486c23.497-6.321 42.003-24.171 48.284-47.821 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.205-142.739 81.201z"
							/>
						</svg>
					</div>

					<div class="p-3 bg-black rounded-full">
						<svg
							class="w-4 h-4"
							aria-hidden="true"
							focusable="false"
							data-prefix="fab"
							data-icon="linkedin-in"
							role="img"
							xmlns="http://www.w3.org/2000/svg"
							viewbox="0 0 448 512"
						>
							<path
								fill="currentColor"
								d="M100.28 448H7.4V148.9h92.88zM53.79 108.1C24.09 108.1 0 83.5 0 53.8a53.79 53.79 0 0 1 107.58 0c0 29.7-24.1 54.3-53.79 54.3zM447.9 448h-92.68V302.4c0-34.7-.7-79.2-48.29-79.2-48.29 0-55.69 37.7-55.69 76.7V448h-92.78V148.9h89.08v40.8h1.3c12.4-23.5 42.69-48.3 87.88-48.3 94 0 111.28 61.9 111.28 142.3V448z"
							/>
						</svg>
					</div>
				</div>
			</section>
			<section class="tab" id="resources">
				<h3 class="mb-2 text-lg font-semibold">
					<a href="#resources">Recursos recomendados</a>
				</h3>
				<ul class="dropdown">
					<li class="text-sm leading-8">
						<a href="./">Cómo crear un sitio web</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Cómo transferir un sitio web</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Comienza tu propio negocio online</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">De emprendedor a empresario digital</a>
					</li>
				</ul>
			</section>
			<section class="tab" id="support">
				<h3 class="mb-2 text-lg font-semibold">
					<a href="#support">Soporte y ayuda</a>
				</h3>
				<ul class="dropdown">
					<li class="text-sm leading-8">
						<a href="./">Asistencia técnica</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Videos y tutoriales</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Base de conocimientos</a>
					</li>
					<li class="text-sm leading-8">
						<a href="./">Contacta con nosotros</a>
					</li>
				</ul>
			</section>
		</div>
		<div class="flex flex-col items-center justify-between md:w-1/6">
			<div>LOGO</div>
			<div>Cards</div>
		</div>
	</div>
</footer>
