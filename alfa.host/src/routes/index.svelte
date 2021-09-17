<script>
	import ToTop from './components/ToTop.svelte';

	import Copyright from './Copyright.svelte';

	import Footer from './Footer.svelte';

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
