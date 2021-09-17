<script>
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

<section class="grid gap-12 px-8 pt-20 pb-24 bg-gray-200 faqs md:grid-cols-2">
		<article>
			<h2 class="mb-8 text-3xl font-semibold">Últimos artículos del blog</h2>
			<div class="overflow-hidden bg-white rounded shadow-sm card p-2">
				<img
					src="https://picsum.photos/id/1/600/400"
					class="rounded-sm"
					alt=""
					width="600"
					height="400"
				/>
				<div class="py-8 px-7 card-body">
					<div class="gap-8 mb-4">
						<div>30 Mar</div>
						<h3>
							<a class="block text-blue-600 hover:text-black" href="./"
								>Ea deserunt tempor magna cupidatat eiusmod et officia.</a
							>
						</h3>
					</div>
					<div>
						Pariatur mollit dolor ut deserunt qui ex mollit esse officia. Sunt ut cupidatat ad ea.
						Nostrud quis id ut laborum excepteur est anim. Do dolor laborum aliqua laborum. Sunt
						exercitation consequat ut consectetur ut sunt duis qui culpa occaecat dolore magna.
					</div>
				</div>
			</div>
		</article>
		<article>
			<h2 class="mb-8 text-3xl font-semibold">Preguntas frecuentes</h2>
			<ul class="flex flex-col gap-8">
				<li>
					<h4 class="text-xl font-medium">
						<a
							class="flex items-center justify-between gap-4 text-gray-800 hover:text-black"
							href="./"
							><span class="w-full"
								>Culpa dolor magna reprehenderit consectetur duis occaecat cillum</span
							><span class="flex items-center justify-center w-6 h-6 text-white bg-black rounded"
								>-</span
							></a
						>
					</h4>
					<div class="visible mt-2 font-normal answer">
						Voluptate dolor proident consectetur aliqua proident qui. Dolore ad esse ex consequat
						laborum sint. Nostrud ut nulla enim officia occaecat ex labore. Cupidatat duis amet nisi
						proident elit. Reprehenderit cillum voluptate proident reprehenderit proident amet
						exercitation consequat eu. Anim ut irure et quis laboris laboris Lorem culpa et
						adipisicing amet.
					</div>
				</li>
				<li>
					<h4 class="text-xl font-medium">
						<a
							class="flex items-center justify-between gap-4 text-blue-600 hover:text-black"
							href="./"
							><span class="w-full"
								>Incididunt incididunt et in reprehenderit quis occaecat velit proident.</span
							><span
								class="flex items-center justify-center w-6 h-6 font-light text-white bg-black rounded"
								>+</span
							></a
						>
					</h4>
					<div class="invisible h-0 font-normal answer">
						Aliquip Lorem quis dolor et ullamco aute cupidatat ullamco quis eu deserunt amet dolor.
						Veniam ex nisi ad cillum dolor qui. Amet anim cupidatat aliquip ex. Duis esse voluptate
						qui voluptate minim est culpa. Cillum fugiat et duis velit. Ipsum consequat occaecat
						culpa mollit magna minim labore velit.
					</div>
				</li>
				<li>
					<h4 class="text-xl font-medium">
						<a
							class="flex items-center justify-between gap-4 text-blue-600 hover:text-black"
							href="./"
							><span class="w-full">Sint anim ullamco aliqua commodo.</span><span
								class="flex items-center justify-center w-6 h-6 font-light text-white bg-black rounded"
								>+</span
							></a
						>
					</h4>
					<div class="invisible h-0 font-normal answer">
						Veniam reprehenderit consequat ea incididunt elit irure cupidatat excepteur sint labore
						enim. Pariatur do fugiat elit ullamco occaecat do. Eiusmod adipisicing exercitation
						commodo do. Cillum aliqua cupidatat reprehenderit duis elit.
					</div>
				</li>
				<li>
					<h4 class="text-xl font-medium">
						<a
							class="flex items-center justify-between gap-4 text-blue-600 hover:text-black"
							href="./"
							><span class="w-full"
								>Culpa magna tempor cillum dolor amet id duis esse pariatur reprehenderit ex.</span
							><span
								class="flex items-center justify-center w-6 h-6 font-light text-white bg-black rounded"
								>+</span
							></a
						>
					</h4>
					<div class="invisible h-0 font-normal answer">
						Id mollit in irure ex esse ipsum incididunt elit id. Non est dolore quis Lorem ipsum
						aute adipisicing esse nulla sint duis consequat enim Lorem. Ut consectetur elit sint
						laboris laborum officia ea quis amet. Incididunt reprehenderit et ullamco anim duis sit
						tempor commodo dolore deserunt ad exercitation cillum. Deserunt sit minim fugiat labore
						reprehenderit amet eiusmod aliqua nisi amet. Incididunt proident labore elit dolore
						nulla est ut eiusmod.
					</div>
				</li>
			</ul>
		</article>
	</section>
