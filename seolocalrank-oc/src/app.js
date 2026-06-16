import Alpine from 'alpinejs'

function uuleEncode(lat, lng) {
  const buf = new ArrayBuffer(12)
  const view = new DataView(buf)
  view.setUint32(0, 0x0B000800, true)
  view.setInt32(4, Math.round(lat * 1e6), true)
  view.setInt32(8, Math.round(lng * 1e6), true)
  const bytes = new Uint8Array(buf)
  let binary = ''
  for (let i = 0; i < bytes.length; i++) {
    binary += String.fromCharCode(bytes[i])
  }
  return 'w+CAIQICIN' + btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '')
}

document.addEventListener('alpine:init', () => {
  Alpine.data('searchForm', () => ({
    keyword: '',
    countryQuery: '',
    selectedCountry: null,
    showDropdown: false,
    address: '',
    lat: null,
    lng: null,
    geocoding: false,
    geoError: '',

    get filteredCountries() {
      if (!this.countryQuery) return window.countries
      const q = this.countryQuery.toLowerCase()
      return window.countries.filter(c => c.label.toLowerCase().includes(q))
    },

    selectCountry(country) {
      this.selectedCountry = country
      this.countryQuery = country.label
      this.showDropdown = false
    },

    closeDropdown() {
      this.showDropdown = false
    },

    async geocodeAddress() {
      if (!this.address.trim()) return
      this.geocoding = true
      this.geoError = ''
      try {
        const res = await fetch(
          `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(this.address)}&key=${window.mapsApiKey}`
        )
        const data = await res.json()
        if (data.status !== 'OK' || !data.results.length) {
          this.geoError = 'Could not geocode this address. Try being more specific.'
          return
        }
        const loc = data.results[0].geometry.location
        this.lat = loc.lat
        this.lng = loc.lng
      } catch {
        this.geoError = 'Geocoding request failed. Check your internet connection.'
      } finally {
        this.geocoding = false
      }
    },

    get canSearch() {
      return this.keyword.trim() && this.selectedCountry && this.lat !== null && this.lng !== null
    },

    search() {
      if (!this.canSearch) return
      const uule = uuleEncode(this.lat, this.lng)
      const url = `https://google.com/search?q=${encodeURIComponent(this.keyword)}&hl=${this.selectedCountry.hl}&gl=${this.selectedCountry.gl}&uule=${uule}`
      window.open(url, '_blank')
    }
  }))
})

Alpine.start()
