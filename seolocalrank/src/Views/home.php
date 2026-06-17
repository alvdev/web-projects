<?php
/**
 * @var string        $appUrl
 * @var string        $mapsKey
 * @var list<array>   $regions
 */
?>
<section
    x-data="serpChecker({
        regions: window.SEO_LOCAL_RANK_REGIONS,
        config: window.SEO_LOCAL_RANK_CONFIG,
    })"
    x-init="init()"
    class="relative"
>

    <!-- Hero / search card -->
    <div class="relative isolate overflow-hidden bg-gradient-to-br from-brand-700 via-brand-600 to-indigo-700 text-white">
        <div class="absolute inset-0 -z-10 opacity-30"
             style="background-image: radial-gradient(circle at 20% 10%, white 0%, transparent 40%), radial-gradient(circle at 80% 80%, white 0%, transparent 40%);"></div>
        <div class="max-w-6xl mx-auto px-5 py-14 sm:py-20">
            <p class="text-brand-100 text-sm font-medium tracking-widest uppercase">Local &amp; International</p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-semibold tracking-tight leading-tight max-w-3xl">
                Check Google SERPs from any country, language &amp; <span class="underline decoration-brand-300 underline-offset-4">exact location</span>.
            </h1>
            <p class="mt-5 max-w-2xl text-brand-100 text-base sm:text-lg">
                Pick a region &amp; language, geocode any address, and jump straight to a localized Google search result page — with the right <code class="font-mono text-white">gl</code>, <code class="font-mono text-white">hl</code> and <code class="font-mono text-white">uule</code> parameters.
            </p>

            <!-- search form -->
            <form
                @submit.prevent="submit()"
                class="mt-10 bg-white text-slate-900 rounded-2xl shadow-card ring-1 ring-slate-200 p-5 sm:p-7 grid gap-5"
                action="https://www.google.com/search"
                method="get"
                target="_blank"
                rel="noopener"
            >
                <!-- 1. Keyword -->
                <label class="block">
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-brand-100 text-brand-700 text-[10px] font-bold">1</span>
                        Keyword
                    </span>
                    <input
                        x-model="state.q"
                        x-ref="q"
                        name="q"
                        type="text"
                        required
                        autocomplete="off"
                        placeholder="e.g. best sushi near me"
                        class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500/40 text-base px-3.5 py-2.5 shadow-sm"
                    >
                </label>

                <!-- 2. Country + language -->
                <label class="block relative">
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-brand-100 text-brand-700 text-[10px] font-bold">2</span>
                        Country &amp; language
                    </span>
                    <input
                        x-model="regionQuery"
                        @focus="regionOpen = true"
                        @click="regionOpen = true"
                        @input="regionOpen = true; tryAutoSelectRegion()"
                        @keydown.escape="regionOpen = false"
                        @keydown.enter.prevent="if (filteredRegions().length) selectRegion(filteredRegions()[0])"
                        @click.outside="regionOpen = false"
                        type="text"
                        autocomplete="off"
                        placeholder="e.g. Spain — Español (es/es)"
                        class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500/40 text-base px-3.5 py-2.5 shadow-sm"
                    >
                    <ul
                        x-show="regionOpen && filteredRegions().length"
                        x-transition.opacity
                        class="absolute z-50 mt-1 w-full max-h-72 overflow-auto bg-white rounded-lg shadow-card ring-1 ring-slate-200 divide-y divide-slate-100"
                    >
                        <template x-for="r in filteredRegions().slice(0, 12)" :key="r.label">
                            <li
                                @mousedown.prevent="selectRegion(r)"
                                class="px-3.5 py-2 hover:bg-brand-50 cursor-pointer flex items-center justify-between gap-3"
                            >
                                <span class="font-medium text-slate-800" x-text="r.name"></span>
                                <span class="text-xs text-slate-500 truncate" x-text="r.lang + ' (' + r.gl + '/' + r.hl + ')'"></span>
                            </li>
                        </template>
                    </ul>


                </label>

                <!-- 3. Address + geocode button -->
                <div class="block">
                    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-brand-100 text-brand-700 text-[10px] font-bold">3</span>
                        Address to target
                    </span>
                    <div class="mt-1.5 flex gap-2">
                        <!-- OSM-based free autocomplete (no API key).
                             Alpine renders an inline <ul> beneath the input
                             showing up to 5 matched features. -->
                        <div
                            class="relative flex-1"
                            @click.outside="placeOpen = false"
                        >
                            <input
                                x-model="state.placeText"
                                @input="searchPlaces()"
                                @focus="if (placeSuggestions.length) placeOpen = true"
                                @keydown.escape="placeOpen = false"
                                @keydown.enter.prevent="if (placeSuggestions.length) pickPlace(placeSuggestions[0])"
                                id="place-input"
                                name="address"
                                type="text"
                                autocomplete="off"
                                placeholder="e.g. Times Square, New York, USA"
                                aria-label="Address to target"
                                aria-autocomplete="list"
                                aria-controls="place-suggestion-list"
                                :aria-expanded="placeOpen && placeSuggestions.length > 0 ? 'true' : 'false'"
                                class="w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500/40 text-base px-3.5 py-2.5 shadow-sm"
                            >
                            <ul
                                id="place-suggestion-list"
                                role="listbox"
                                x-show="placeOpen && placeSuggestions.length"
                                x-transition.opacity
                                class="absolute z-50 mt-1 w-full max-h-72 overflow-auto bg-white rounded-lg shadow-card ring-1 ring-slate-200 divide-y divide-slate-100"
                            >
                                <template x-for="(s, i) in placeSuggestions" :key="i">
                                    <li
                                        role="option"
                                        @mousedown.prevent="pickPlace(s)"
                                        class="px-3.5 py-2 hover:bg-brand-50 cursor-pointer flex items-center justify-between gap-3"
                                    >
                                        <span class="font-medium text-slate-800 truncate min-w-0" x-text="s.label"></span>
                                        <span class="text-xs text-slate-500 shrink-0" x-text="s.kind"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                        <button
                            type="button"
                            @click="forceGeocode()"
                            :disabled="!state.placeText || geocoding"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 hover:bg-slate-700 text-white text-sm px-4 disabled:opacity-50 transition"
                        >
                            <svg x-show="!geocoding" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 1 1 9.9 9.9L10 18.9l-4.95-4.95a7 7 0 0 1 0-9.9zM10 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" clip-rule="evenodd"/></svg>
                            <svg x-show="geocoding" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" viewBox="0 0 20 20" fill="currentColor"><path d="M10 3a7 7 0 0 1 7 7h-2a5 5 0 0 0-5-5V3z"/></svg>
                            <span x-text="geocoding ? 'Geocoding…' : 'Geocode'"></span>
                        </button>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500" aria-live="polite">
                        <span x-text="placeError || 'Type an address — suggestions will appear below.'"></span>
                    </p>

                    <!-- OSM attribution required by ODbL when serving OSM-derived data. -->
                    <p class="mt-1 text-[11px] text-slate-400">
                        © <a class="underline hover:text-slate-600" href="https://www.openstreetmap.org/copyright" rel="noopener" target="_blank">OpenStreetMap contributors</a>
                    </p>

                    <!-- Submit is intercepted in JS (window.open a localized URL).
                         uule is rendered below for transparency / debugging. -->
                    <p class="sr-only">Opens a localized Google search in a new tab.</p>
                </div>

                <!-- submit -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                    <div class="text-xs text-slate-500 space-y-1">
                        <p class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full" :class="state.uule ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                            <span x-show="!state.uule">No location set — Google will fall back to your IP.</span>
                            <span x-show="state.uule" class="font-mono text-slate-600 truncate max-w-md">UULE: <span x-text="state.uule"></span></span>
                        </p>
                        <p class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full" :class="regionQuery ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                            <span x-show="!regionQuery">No country selected — SERP will be English / US.</span>
                            <span x-show="regionQuery" class="font-mono text-slate-600 truncate max-w-md">hl=<span x-text="state.hl"></span> · gl=<span x-text="state.gl"></span></span>
                        </p>
                    </div>
                    <button
                        type="submit"
                        :disabled="!state.q || geocoding"
                        class="inline-flex items-center gap-2 justify-center rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-medium px-5 py-2.5 disabled:opacity-50 transition"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8 4a4 4 0 1 1 0 8 4 4 0 0 1 0-8zM2 8a6 6 0 1 1 10.37 4.16l4.19 4.18 1.41-1.41-4.18-4.19A6 6 0 0 1 2 8z" clip-rule="evenodd"/></svg>
                        Open localized SERP
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- How-to section -->
    <section id="howto" class="max-w-6xl mx-auto px-5 py-14">
        <div class="grid md:grid-cols-3 gap-6">
            <?php foreach ([
                ['01', 'Pick a country & language', 'Type the region you want to check (e.g. “Canada — English”). Your selection drives the gl and hl params.'],
                ['02', 'Geocode an address', 'Start typing a city or street — Google Places will autocomplete. Click “Geocode” to turn that into a precise location.'],
                ['03', 'Submit & open the SERP', 'Hit the search button. We build a clean Google search URL with uule + gl + hl and open it in a new tab.'],
            ] as $step): ?>
                <article class="rounded-xl bg-white p-6 shadow-card ring-1 ring-slate-200">
                    <p class="text-brand-600 text-xs font-bold tracking-widest">STEP <?= htmlspecialchars($step[0]) ?></p>
                    <h3 class="mt-2 text-lg font-semibold tracking-tight text-slate-900"><?= htmlspecialchars($step[1]) ?></h3>
                    <p class="mt-2 text-sm text-slate-600 leading-relaxed"><?= htmlspecialchars($step[2]) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- About / credits -->
    <section id="about" class="bg-white border-t border-slate-200">
        <div class="max-w-6xl mx-auto px-5 py-14 grid md:grid-cols-3 gap-10 text-sm text-slate-600">
            <div class="md:col-span-2 space-y-3">
                <h2 class="text-xl font-semibold tracking-tight text-slate-900">What this tool does</h2>
                <p>
                    Google has progressively hidden direct control over the user’s perceived location and language. Since late 2017 you can no longer rely on ccTLDs alone.
                </p>
                <p>
                    This page lets you combine <code class="font-mono text-slate-800">gl</code> (geo location), <code class="font-mono text-slate-800">hl</code> (host language) and a precise <code class="font-mono text-slate-800">uule</code> location so you can preview localized SERPs as a real user in that market — without paying for a VPN.
                </p>
                <p class="text-slate-500">Inspired by <a href="https://valentin.app" class="text-brand-700 hover:underline" rel="noopener" target="_blank">valentin.app</a>.</p>
            </div>
            <aside class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-5 space-y-2">
                <h3 class="text-sm font-semibold tracking-tight text-slate-900">Tip</h3>
                <p>
                    To check what Google currently thinks your location is, search <em>“where am I”</em> on Google — the result includes a small map.
                </p>
            </aside>
        </div>
    </section>
</section>
