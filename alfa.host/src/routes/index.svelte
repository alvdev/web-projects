<script>
	import ToTop from '../components/ToTop.svelte';

	import Copyright from '../components/sections/Copyright.svelte';

	import Footer from '../components/sections/Footer.svelte';

	import BlogFaq from '../components/sections/PostsFaqs.svelte';

	import HostingApps from '../components/sections/HostingApps.svelte';

	import MarketingServices from '../components/sections/MarketingServices.svelte';

	import DigitalNeurs from '../components/sections/DigitalNeurs.svelte';

	import EmailMarketing from '../components/sections/EmailMarketing.svelte';

	import BannerWebsiteBuilder from '../components/sections/BannerWebsiteBuilder.svelte';

	import WebsiteBuilders from '../components/sections/WebsiteBuilders.svelte';

	import AlfaFeatures from '../components/sections/AlfaFeatures.svelte';

	import HostingFeatures from '../components/sections/HostingFeatures.svelte';

	import HostingDesc from '../components/sections/HostingDesc.svelte';

	import BannerEntrepreneur from '../components/sections/BannerEntrepreneur.svelte';

	import WhatDoYouNeed from '../components/sections/WhatDoYouNeed.svelte';

	import DomainSearchBox from '../components/sections/DomainSearchBox.svelte';

	import HomeHeader from '../components/sections/HomeHeader.svelte';
</script>

<svelte:head>
	<title>Alfa Host Home</title>
	<script src="../../static/js/script.js" defer></script>
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
