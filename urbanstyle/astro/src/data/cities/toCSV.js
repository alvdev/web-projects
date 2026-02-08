import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { provincesMetadata, allSpainCities } from './allSpainCities.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL_ES = 'https://www.urbanstylepublicity.com/pegada-carteles';
const BOM = '\uFEFF';

/* -------------------------------------------------
   🔧 ALIAS DE CIUDADES (EVITAR EXCESO DE CARACTERES)
------------------------------------------------- */

const ADGROUP_NAME_OVERRIDES = {
  'san-sebastian-de-los-reyes': 'San Sebastián',
  'villanueva-de-la-canada': 'Villanueva',
  'san-martin-de-la-vega': 'San Martín',
  'alcala-de-henares': 'Alcalá',
  'torrejon-de-ardoz': 'Torrejón',
  'pozuelo-de-alarcon': 'Pozuelo',
  'hospitalet': 'Hospitalet',
  'mejorada-del-campo': 'Mejorada',
  'humanes-de-madrid': 'Humanes',
  'san-sebastian-donostia': 'San Sebastián',
  'la-linea-de-la-concepcion': 'La Línea',
  'puerto-santa-maria': 'Puerto S. María',
  'jerez-de-la-frontera': 'Jerez',
  'santa-coloma-de-gramenet': 'Santa Coloma',
  'cornellà-de-llobregat': 'Cornellà',
  'sant-boi-de-llobregat': 'Sant Boi',
  'sant-cugat-del-valles': 'Sant Cugat',
  'san-vicente-del-raspeig': 'S. Vicente Raspeig',
  'las-palmas-de-gran-canaria': 'Las Palmas',
  'santa-lucia-de-tirajana': 'Santa Lucía',
  'san-bartolome-de-tirajana': 'San Bartolomé',
  'alcala-de-guadaira': 'Alcalá Guadaíra',
  'talavera-de-la-reina': 'Talavera',
  'san-cristobal-de-la-laguna': 'La Laguna'
};

function getAdGroupCityName(city) {
  return ADGROUP_NAME_OVERRIDES[city.slug] || city.name;
}

const kwRows = [];
const rsaRows = [];
const negCampaignRows = [];
const negAdGroupRows = [];

const negativeKeywordsCampaign = [
  'cómo hacer', 'como hacer', 'tutorial', 'ejemplo', 'plantilla',
  'canva', 'psd', 'gratis', 'trabajo', 'empleo', 'curriculum', 'cv',
  'facebook', 'instagram', 'marketing digital',
];

const kwHeader = 'Campaign,Budget,Campaign type,Bid strategy type,CPC bid limit,Ad Group,Keyword,Final URL\n';
const rsaHeader = [
  'Campaign', 'Budget', 'Campaign type', 'Bid strategy type', 'CPC bid limit', 'Ad Group', 'Ad type', 'Final URL',
  'Headline 1', 'Headline 2', 'Headline 3', 'Headline 4', 'Headline 5',
  'Headline 6', 'Headline 7', 'Headline 8', 'Headline 9', 'Headline 10',
  'Description 1', 'Description 2', 'Description 3', 'Description 4'
].join(',');

/* -------------------------------------------------
   1️⃣ PROCESAR TODAS LAS PROVINCIAS Y CIUDADES
------------------------------------------------- */

Object.entries(allSpainCities).forEach(([provinceSlug, cities]) => {
  const meta = provincesMetadata[provinceSlug];
  const provinceName = meta ? meta.name : provinceSlug;
  const campaignName = `Pegada Carteles ${provinceName}`;
  const provinceUrl = `${BASE_URL_ES}/${provinceSlug}`;
  const budget = '30';
  const campaignType = 'Search';
  const bidStrategy = 'Maximize clicks';
  const cpcLimit = '0.5';

  // Negativas a nivel de campaña
  negativeKeywordsCampaign.forEach(kw => {
    negCampaignRows.push(`"${campaignName}","${kw}"`);
  });

  cities.forEach(city => {
    const cityAdName = getAdGroupCityName(city);
    const adGroup = `Pegada Carteles ${cityAdName}`;
    
    // Si la ciudad es la capital (mismo slug que la provincia), usamos la URL de la provincia
    const isCapital = city.slug === provinceSlug;
    const url = isCapital ? provinceUrl : `${provinceUrl}/${city.slug}`;
    const keywordBase = `pegada de carteles ${city.name.toLowerCase()}`;

    // Keywords
    kwRows.push(
      `"${campaignName}","${budget}","${campaignType}","${bidStrategy}","${cpcLimit}","${adGroup}","[${keywordBase}]","${url}"`,
      `"${campaignName}","${budget}","${campaignType}","${bidStrategy}","${cpcLimit}","${adGroup}","\"${keywordBase}\"","${url}"`,
    );

    // RSA
    const headlines = [
      `Pegada carteles ${cityAdName}`.substring(0, 30),
      'Impresión y pegada profesional',
      `Carteles en ${cityAdName}`.substring(0, 30),
      'Publicidad local efectiva',
      'Expertos en pegada profesional',
      'Tu marca en toda la calle',
      'Ideal para eventos y marcas',
      'Campañas urbanas reales',
      'Servicio completo incluido',
      'Toda la ciudad te verá',
    ];

    const descriptions = [
      `Pegada de carteles en ${city.name} en zonas estratégicas.`,
      'Nos encargamos de imprimir y pegar tus carteles profesionalmente.',
      'Publicidad exterior ideal para eventos, conciertos y marcas.',
      'Haz que tu campaña destaque donde de verdad está la gente.',
    ];

    rsaRows.push(
      [
        campaignName, budget, campaignType, bidStrategy, cpcLimit, adGroup, 'Responsive search ad', url,
        ...headlines, ...descriptions
      ]
        .map(v => `"${v}"`)
        .join(',')
    );
  });
});

/* -------------------------------------------------
   2️⃣ CAMPAÑA GENÉRICA ESPAÑA
------------------------------------------------- */

const campaignES = 'Pegada Carteles España';
const adGroupES = 'Pegada Carteles España';
const budgetES = '30';
const campaignTypeES = 'Search';
const bidStrategyES = 'Maximize clicks';
const cpcLimitES = '0.5';

const esKeywordsExact = [
  '[pegada de carteles]',
  '[servicio de pegada de carteles]',
  '[empresa de pegada de carteles]',
  '[pegada de carteles profesional]',
  '[publicidad con carteles]',
];
const esKeywordsPhrase = [
  '"pegada de carteles"',
  '"servicio de pegada de carteles"',
  '"empresa de pegada de carteles"',
  '"pegada de carteles publicitarios"',
  '"publicidad con carteles"',
];

[...esKeywordsExact, ...esKeywordsPhrase].forEach(kw => {
  kwRows.push(`"${campaignES}","${budgetES}","${campaignTypeES}","${bidStrategyES}","${cpcLimitES}","${adGroupES}","${kw}","${BASE_URL_ES}"`);
});

negativeKeywordsCampaign.forEach(kw => {
  negCampaignRows.push(`"${campaignES}","${kw}"`);
});

// Negativas de ciudades para el grupo genérico España
const allCitiesList = Object.values(allSpainCities).flat();
allCitiesList.forEach(city => {
  negAdGroupRows.push(`"${campaignES}","${adGroupES}","${city.name.toLowerCase()}"`);
});

/* RSA ESPAÑA */
const esHeadlines = [
  'Pegada de carteles en España',
  'Pegada profesional carteles',
  'Publicidad exterior efectiva',
  'Empresa de pegada de carteles',
  'Impresión y pegada incluida',
  'Carteles visibles en la calle',
  'Ideal para eventos y marcas',
  'Campañas urbanas efectivas',
  'Publicidad que sí se ve',
  'Expertos en pegada exterior',
];

const esDescriptions = [
  'Pegada de carteles profesional en toda España.',
  'Nos encargamos de imprimir y pegar tus carteles.',
  'Publicidad exterior para marcas, eventos y negocios.',
  'Haz que tu marca destaque.',
];

rsaRows.push(
  [
    campaignES, budgetES, campaignTypeES, bidStrategyES, cpcLimitES, adGroupES, 'Responsive search ad', BASE_URL_ES,
    ...esHeadlines, ...esDescriptions
  ]
    .map(v => `"${v}"`)
    .join(',')
);

/* -------------------------------------------------
   3️⃣ ESCRIBIR ARCHIVOS
------------------------------------------------- */

fs.writeFileSync(
  path.join(__dirname, 'google-ads-keywords.csv'),
  BOM + kwHeader + kwRows.join('\n'),
  'utf8',
);

fs.writeFileSync(
  path.join(__dirname, 'google-ads-rsa.csv'),
  BOM + rsaHeader + '\n' + rsaRows.join('\n'),
  'utf8',
);

fs.writeFileSync(
  path.join(__dirname, 'google-ads-negatives-campaign.csv'),
  BOM + 'Campaign,Negative keyword\n' + negCampaignRows.join('\n'),
  'utf8',
);

fs.writeFileSync(
  path.join(__dirname, 'google-ads-negatives-espana-ciudades.csv'),
  BOM + 'Campaign,Ad Group,Negative keyword\n' + negAdGroupRows.join('\n'),
  'utf8',
);

console.log('✅ CSVs GENERADOS CORRECTAMENTE');
console.log('- google-ads-keywords.csv');
console.log('- google-ads-rsa.csv');
console.log('- google-ads-negatives-campaign.csv');
console.log('- google-ads-negatives-espana-ciudades.csv');
