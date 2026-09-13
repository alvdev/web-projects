export const languages = {
    es: "Español",
    en: "English",
    it: "Italiano",
    fr: "Français",
    pt: "Português",
} as const;

export type Locale = keyof typeof languages;

export const defaultLocale: Locale = "es";

export const localePrefixes = ["en", "it", "fr", "pt"] as const;

export const coverageSlugs: Record<Locale, string> = {
    es: "pegada-carteles",
    en: "poster-pasting",
    it: "affissione-manifesti",
    fr: "collage-affiches",
    pt: "colagem-cartazes",
};

export const ogLocales: Record<Locale, string> = {
    es: "es_ES",
    en: "en_US",
    it: "it_IT",
    fr: "fr_FR",
    pt: "pt_PT",
};
