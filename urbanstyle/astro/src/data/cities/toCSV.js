import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { provincesMetadata, allSpainCities } from './allSpainCities.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const BASE_URL_ES = 'https://www.urbanstylepublicity.com/pegada-carteles';

/**
 * Escribe un archivo en formato UTF-16LE con saltos de línea Windows (\r\n) y comas.
 */
const writeCSV = (filePath, content) => {
  const BOM = Buffer.from([0xFF, 0xFE]);
  const windowsContent = content.replace(/\r?\n/g, '\r\n');
  const utf16Content = Buffer.from(windowsContent, 'utf16le');
  fs.writeFileSync(filePath, Buffer.concat([BOM, utf16Content]));
};

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
const locationRows = [];

const negativeKeywordsCampaign = [
  'cómo hacer', 'como hacer', 'tutorial', 'ejemplo', 'plantilla',
  'canva', 'psd', 'gratis', 'trabajo', 'empleo', 'curriculum', 'cv',
  'facebook', 'instagram', 'marketing digital',
];

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

  // Negativas
  negativeKeywordsCampaign.forEach(kw => {
    negCampaignRows.push(`"${campaignName}","${kw}"`);
  });

  // LOCALIZACIÓN: Toda la campaña apunta a ESPAÑA (ID 2724)
  locationRows.push(`"${campaignName}","2724"`);

  cities.forEach(city => {
    const cityAdName = getAdGroupCityName(city);
    const adGroup = `Pegada Carteles ${cityAdName}`;

    const isCapital = city.slug === provinceSlug;
    const url = isCapital ? provinceUrl : `${provinceUrl}/${city.slug}`;
    const keywordBase = `pegada de carteles ${city.name.toLowerCase()}`;

    // Keywords
    kwRows.push(
      `"${campaignName}","${budget}","${campaignType}","${bidStrategy}","${cpcLimit}","No","No","Kilometers","${adGroup}","${cpcLimit}","[${keywordBase}]","${url}"`,
      `"${campaignName}","${budget}","${campaignType}","${bidStrategy}","${cpcLimit}","No","No","Kilometers","${adGroup}","${cpcLimit}","\"${keywordBase}\"","${url}"`,
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
        campaignName,
        budget,
        campaignType,
        bidStrategy,
        cpcLimit,
        'No',
        'No',
        'Kilometers',
        adGroup,
        cpcLimit,
        'Responsive search ad',
        url,
        ...headlines,
        ...descriptions,
      ]
        .map(v => `"${v}"`)
        .join(','),
    );
  });
});

/* -------------------------------------------------
   2️⃣ CAMPAÑA GENÉRICA ESPAÑA
------------------------------------------------- */

const campaignES = 'Pegada Carteles España';
const adGroupES = 'Pegada Carteles España';

const esKeywordsBroad = [
  'pegada de carteles',
  'servicio de pegada de carteles',
  'empresa de pegada de carteles',
  'pegada de carteles profesional',
  'publicidad con carteles',
  'pegada de carteles publicitarios',
  'distribucion de carteles',
];

esKeywordsBroad.forEach(kw => {
  kwRows.push(
    `"${campaignES}","30","Search","Maximize clicks","0.5","No","No","Kilometers","${adGroupES}","0.5","[${kw}]","${BASE_URL_ES}"`,
    `"${campaignES}","30","Search","Maximize clicks","0.5","No","No","Kilometers","${adGroupES}","0.5","\"${kw}\"","${BASE_URL_ES}"`,
  );
});

// ID de España: 2724
locationRows.push(`"${campaignES}","2724"`);

negativeKeywordsCampaign.forEach(kw => {
  negCampaignRows.push(`"${campaignES}","${kw}"`);
});

const allCitiesList = Object.values(allSpainCities).flat();
allCitiesList.forEach(city => {
  negAdGroupRows.push(`"${campaignES}","${adGroupES}","${city.name.toLowerCase()}"`);
});

rsaRows.push(
  [
    campaignES,
    '30',
    'Search',
    'Maximize clicks',
    '0.5',
    'No',
    'No',
    'Kilometers',
    adGroupES,
    '0.5',
    'Responsive search ad',
    BASE_URL_ES,
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
    'Pegada de carteles profesional en toda España.',
    'Nos encargamos de imprimir y pegar tus carteles.',
    'Publicidad exterior para marcas, eventos y negocios.',
    'Haz que tu marca destaque.',
  ]
    .map(v => `"${v}"`)
    .join(','),
);

/* -------------------------------------------------
   3️⃣ ESCRIBIR ARCHIVOS
------------------------------------------------- */

writeCSV(
  path.join(__dirname, 'google-ads-keywords.csv'),
  'Campaign,Budget,Campaign type,Bid strategy type,CPC bid limit,Political content,Campaign EU political ads,Distance unit,Ad Group,Max CPC,Keyword,Final URL\n' +
    kwRows.join('\n'),
);

const rsaHeaderLines = [
  'Campaign',
  'Budget',
  'Campaign type',
  'Bid strategy type',
  'CPC bid limit',
  'Political content',
  'Campaign EU political ads',
  'Distance unit',
  'Ad Group',
  'Max CPC',
  'Ad type',
  'Final URL',
  'Headline 1',
  'Headline 2',
  'Headline 3',
  'Headline 4',
  'Headline 5',
  'Headline 6',
  'Headline 7',
  'Headline 8',
  'Headline 9',
  'Headline 10',
  'Description 1',
  'Description 2',
  'Description 3',
  'Description 4',
];

writeCSV(
  path.join(__dirname, 'google-ads-rsa.csv'),
  rsaHeaderLines.join(',') + '\n' + rsaRows.join('\n'),
);

writeCSV(
  path.join(__dirname, 'google-ads-negatives-campaign.csv'),
  'Campaign,Negative keyword\n' + negCampaignRows.join('\n'),
);

writeCSV(
  path.join(__dirname, 'google-ads-negatives-espana-ciudades.csv'),
  'Campaign,Ad Group,Negative keyword\n' + negAdGroupRows.join('\n'),
);

writeCSV(
  path.join(__dirname, 'google-ads-locations.csv'),
  'Campaign,ID\n' + locationRows.join('\n'),
);

console.log('✅ CSVs GENERADOS CORRECTAMENTE (TODA ESPAÑA)');
