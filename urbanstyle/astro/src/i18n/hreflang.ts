import { coverageSlugs, defaultLocale, languages, type Locale } from "./config";

const locales = Object.keys(languages) as Locale[];

export function localeFromPathname(pathname: string): Locale {
    const clean = pathname.endsWith("/") ? pathname : `${pathname}/`;
    return (
        locales.find(l => l !== defaultLocale && (clean === `/${l}/` || clean.startsWith(`/${l}/`))) ??
        defaultLocale
    );
}

/**
 * Rewrite a pathname to its equivalent in `target` locale, translating the
 * localized coverage slug (e.g. /pegada-carteles/ <-> /en/poster-pasting/).
 */
export function localizedPath(pathname: string, target: Locale): string {
    const clean = pathname.endsWith("/") ? pathname : `${pathname}/`;
    const current = localeFromPathname(clean);

    // Strip the current locale prefix to get a locale-neutral path
    let bare = current === defaultLocale ? clean : clean.replace(new RegExp(`^/${current}`), "") || "/";
    if (!bare.startsWith("/")) bare = `/${bare}`;

    // Normalize the localized coverage slug to the default-locale slug
    const currentCoverage = coverageSlugs[current];
    if (currentCoverage !== coverageSlugs[defaultLocale]) {
        bare = bare.replace(`/${currentCoverage}/`, `/${coverageSlugs[defaultLocale]}/`);
    }

    const localized =
        target === defaultLocale
            ? bare
            : bare.replace(`/${coverageSlugs[defaultLocale]}/`, `/${coverageSlugs[target]}/`);

    return target === defaultLocale ? localized : `/${target}${localized}`;
}

export function hreflangAlternates(
    pathname: string,
    site: URL | undefined,
    onlyLocales: Locale[] = locales,
): { lang: string; url: string }[] {
    if (!site) return [];

    const clean = pathname.endsWith("/") ? pathname : `${pathname}/`;
    const bare = localizedPath(clean, defaultLocale);

    // Legal pages only exist in Spanish; do not point hreflang at 404s
    const targetLocales = bare.startsWith("/legal/") ? [defaultLocale] : onlyLocales;
    if (targetLocales.length === 0) return [];

    const items: { lang: string; url: string }[] = targetLocales.map(l => ({
        lang: l,
        url: new URL(localizedPath(clean, l), site).href,
    }));

    items.push({ lang: "x-default", url: new URL(bare, site).href });
    return items;
}
