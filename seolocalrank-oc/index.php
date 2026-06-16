<?php
$countries = require __DIR__ . '/src/countries.php';
$mapsApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: 'YOUR_GOOGLE_MAPS_API_KEY';
?><!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Google SEO Local Checker</title>
  <link rel="stylesheet" href="dist/output.css">
  <script>
    window.countries = <?= json_encode($countries) ?>;
    window.mapsApiKey = <?= json_encode($mapsApiKey) ?>;
  </script>
</head>
<body class="h-full font-sans antialiased">
  <div class="min-h-full flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Google SEO Local Checker</h1>
        <p class="mt-2 text-sm text-gray-500">See how Google ranks your site in any location</p>
      </div>

      <div class="bg-white shadow-md rounded-xl px-6 py-8" x-data="searchForm">
        <div class="space-y-6">
          <div>
            <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">Keyword</label>
            <input type="text" id="keyword" x-model="keyword"
                   placeholder="e.g. plumber, dentist, pizza..."
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
          </div>

          <div class="relative">
            <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country & Language</label>
            <input type="text" id="country" x-model="countryQuery"
                   @focus="showDropdown = true"
                   @input="showDropdown = true"
                   placeholder="Type to search..."
                   class="block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
            <div x-show="showDropdown && filteredCountries.length"
                 @click.outside="closeDropdown()"
                 x-transition
                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
              <template x-for="country in filteredCountries" :key="country.label">
                <button type="button" @click="selectCountry(country)"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer"
                        :class="{'bg-blue-50': selectedCountry?.label === country.label}">
                  <span x-text="country.label"></span>
                </button>
              </template>
            </div>
            <p x-show="selectedCountry" class="mt-1 text-xs text-green-600">
              Selected: <span x-text="selectedCountry.label"></span>
            </p>
          </div>

          <div>
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <div class="flex gap-2">
              <input type="text" id="address" x-model="address"
                     placeholder="123 Main St, New York, NY"
                     class="flex-1 rounded-lg border border-gray-300 px-3 py-2.5 text-sm shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
              <button type="button" @click="geocodeAddress()" :disabled="geocoding || !address.trim()"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                <span x-show="!geocoding">Geocode</span>
                <span x-show="geocoding">...</span>
              </button>
            </div>
            <div class="flex gap-4 mt-1.5">
              <span x-show="lat !== null" class="text-xs text-gray-500">
                Lat: <strong x-text="lat.toFixed(4)"></strong>
              </span>
              <span x-show="lng !== null" class="text-xs text-gray-500">
                Lng: <strong x-text="lng.toFixed(4)"></strong>
              </span>
            </div>
            <p x-show="geoError" x-text="geoError" class="mt-1 text-xs text-red-600"></p>
          </div>

          <button type="button" @click="search()" :disabled="!canSearch"
                  class="w-full rounded-lg bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
            Search Google
          </button>
        </div>
      </div>

      <p class="mt-6 text-center text-xs text-gray-400">
        Opens a Google search in a new tab with the specified location parameters.
      </p>
    </div>
  </div>

  <script src="dist/app.js"></script>
</body>
</html>
