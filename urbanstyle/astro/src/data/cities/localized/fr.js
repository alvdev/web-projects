import { provincesMetadata as esProvinces, allSpainCities as esCities } from "../allSpainCities.js";
import fr from "./fr.json";

const translations = fr;

export const provincesMetadata = Object.fromEntries(
    Object.entries(esProvinces).map(([slug, meta]) => [
        slug,
        { ...meta, ...(translations[slug]?.province ?? {}) },
    ]),
);

export const allSpainCities = Object.fromEntries(
    Object.entries(esCities).map(([slug, cities]) => [
        slug,
        cities.map(city => {
            const localized = translations[slug]?.cities?.[city.slug];
            return localized ? { ...city, ...localized } : city;
        }),
    ]),
);