<script>
	import ToTop from './components/ToTop.svelte';

	import Copyright from './sections/Copyright.svelte';

	import Footer from './sections/Footer.svelte';

	import BlogFaq from './sections/PostsFaqs.svelte';

	import HostingApps from './sections/HostingApps.svelte';

	import MarketingServices from './sections/MarketingServices.svelte';

	import DigitalNeurs from './sections/DigitalNeurs.svelte';

	import EmailMarketing from './sections/EmailMarketing.svelte';

	import BannerWebsiteBuilder from './sections/BannerWebsiteBuilder.svelte';

	import WebsiteBuilders from './sections/WebsiteBuilders.svelte';

	import AlfaFeatures from './sections/AlfaFeatures.svelte';

	import HostingFeatures from './sections/HostingFeatures.svelte';

	import HostingDesc from './sections/HostingDesc.svelte';

	import BannerEntrepreneur from './sections/BannerEntrepreneur.svelte';

	import WhatDoYouNeed from './sections/WhatDoYouNeed.svelte';

	import DomainSearchBox from './sections/DomainSearchBox.svelte';

	import HomeHeader from './sections/HomeHeader.svelte';
</script>

<svelte:head>
	<title>Alfa Host Home</title>
</svelte:head>

<HomeHeader />

<main>
	<DomainSearchBox />

	<WhatDoYouNeed />

	<BannerEntrepreneur />

	<HostingDesc />

	<HostingFeatures />

	<AlfaFeatures />

	<WebsiteBuilders />

	<BannerWebsiteBuilder />

	<EmailMarketing />

	<DigitalNeurs />

	<MarketingServices />

	<HostingApps />

	<BlogFaq />
</main>

<Footer />
<Copyright />

<ToTop />
