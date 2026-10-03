<?php

namespace Alv\Releases;

use DiarioGames\IGDB\GameImporter;
use DiarioGames\IGDB\IGDBClient;

class Releases
{
    private const MONTHS = [
        1 => 'Enero',
        2 => 'Febrero',
        3 => 'Marzo',
        4 => 'Abril',
        5 => 'Mayo',
        6 => 'Junio',
        7 => 'Julio',
        8 => 'Agosto',
        9 => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];

    private const FIELDS = [
        'name', 'slug', 'first_release_date', 'hypes', 'cover.image_id',
        'platforms.abbreviation', 'genres.name', 'category', 'version_parent',
        'release_dates.human', 'release_dates.date', 'release_dates.platform',
    ];

    private const CACHE_KEY = 'dataset-v3';

    private const RECENT_DAYS = 30;
    private const RECENT_LIMIT = 100;
    private const UPCOMING_LIMIT = 200;
    private const ANTICIPATED_LIMIT = 50;

    private array $settings;
    private ?IGDBClient $client;
    private bool $fixtureLoaded = false;
    private ?array $fixtureData = null;

    public function __construct(array $settings = [], ?IGDBClient $client = null)
    {
        $this->settings = array_merge([
            'client_id' => '',
            'client_secret' => '',
            'cache_ttl' => 21600,
            'notable_hypes' => 20,
            'fixture_file' => '',
        ], $settings);

        if ($client !== null) {
            $this->client = $client;
            return;
        }

        if ($this->fixture() !== null) {
            $this->client = null;
            return;
        }

        $id = (string) $this->settings['client_id'];
        $secret = (string) $this->settings['client_secret'];
        $this->client = ($id !== '' && $secret !== '') ? new IGDBClient($id, $secret) : null;
    }

    public function getRecentlyReleased(int $limit = 8): array
    {
        return array_slice($this->dataset()['recentlyReleased'], 0, max(0, $limit));
    }

    public function getNotableUpcoming(int $limit = 8): array
    {
        $dataset = $this->dataset();
        $anticipatedIds = array_flip(array_map(fn (array $game): int => (int) $game['igdb_id'], $dataset['anticipated']));
        $threshold = max(0, (int) $this->settings['notable_hypes']);

        $notable = array_values(array_filter(
            $dataset['upcoming'],
            function (array $game) use ($anticipatedIds, $threshold): bool {
                if ((int) $game['hypes'] < $threshold) {
                    return false;
                }
                return !isset($anticipatedIds[(int) $game['igdb_id']]);
            }
        ));

        return array_slice($notable, 0, max(0, $limit));
    }

    public function getAnticipated(int $limit = 8): array
    {
        return array_slice($this->dataset()['anticipated'], 0, max(0, $limit));
    }

    public function warm(): array
    {
        $dataset = $this->fixture() !== null ? $this->dataset() : $this->fetch();
        $this->cacheSet(self::CACHE_KEY, $dataset, (int) $this->settings['cache_ttl']);

        return [
            'recentlyReleased' => count($dataset['recentlyReleased']),
            'upcoming' => count($dataset['upcoming']),
            'anticipated' => count($dataset['anticipated']),
        ];
    }

    private function dataset(): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $this->normalizeDatasetShape($fixture);
        }

        $cached = $this->cacheGet(self::CACHE_KEY);
        if (is_array($cached)) {
            return $this->normalizeDatasetShape($cached);
        }

        $dataset = $this->fetch();
        if (!empty($dataset['recentlyReleased']) || !empty($dataset['upcoming']) || !empty($dataset['anticipated'])) {
            $this->cacheSet(self::CACHE_KEY, $dataset, (int) $this->settings['cache_ttl']);
        }

        return $dataset;
    }

    private function normalizeDatasetShape(array $dataset): array
    {
        return [
            'recentlyReleased' => is_array($dataset['recentlyReleased'] ?? null) ? $dataset['recentlyReleased'] : [],
            'upcoming' => is_array($dataset['upcoming'] ?? null) ? $dataset['upcoming'] : [],
            'anticipated' => is_array($dataset['anticipated'] ?? null) ? $dataset['anticipated'] : [],
        ];
    }

    private function fetch(): array
    {
        $empty = ['recentlyReleased' => [], 'upcoming' => [], 'anticipated' => []];

        if ($this->client === null) {
            return $empty;
        }

        try {
            $allowed = \DiarioGames\IGDB\allowedPlatformIds($this->client);
            if (empty($allowed)) {
                return $empty;
            }

            $now = time();
            $platforms = 'platforms = (' . implode(',', $allowed) . ')';
            $base = "version_parent = null & (category = 0 | category = null) & {$platforms}";
            $recentFrom = $now - self::RECENT_DAYS * 86400;
            $threshold = max(0, (int) $this->settings['notable_hypes']);

            $recentlyReleased = $this->client->fetchGames(
                self::FIELDS,
                self::RECENT_LIMIT,
                0,
                "first_release_date > {$recentFrom} & first_release_date <= {$now} & {$base}",
                'hypes desc'
            );

            $upcoming = $this->client->fetchGames(
                self::FIELDS,
                self::UPCOMING_LIMIT,
                0,
                "first_release_date > {$now} & hypes >= {$threshold} & {$base}",
                'first_release_date asc'
            );

            $anticipated = $this->client->fetchGames(
                self::FIELDS,
                self::ANTICIPATED_LIMIT,
                0,
                "first_release_date > {$now} & hypes > 0 & {$base}",
                'hypes desc'
            );

            return [
                'recentlyReleased' => $this->normalizeMany($recentlyReleased),
                'upcoming' => $this->normalizeMany($upcoming),
                'anticipated' => $this->normalizeMany($anticipated),
            ];
        } catch (\Throwable $e) {
            error_log('Releases fetch failed: ' . $e->getMessage());
            return $empty;
        }
    }

    private function normalizeMany(array $games): array
    {
        $result = [];

        foreach ($games as $game) {
            if (!is_array($game) || empty($game['name'])) {
                continue;
            }
            if (GameImporter::isExcluded($game)) {
                continue;
            }
            $entry = $this->normalize($game);
            if ($entry !== null) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    private function normalize(array $game): ?array
    {
        $display = $this->displayDate($game);
        if ($display === null) {
            return null;
        }

        $slug = (string) ($game['slug'] ?? '');
        $localSlug = !empty($game['id']) ? \DiarioGames\IGDB\resolveGameByIgdbId((int) $game['id']) : null;

        return [
            'igdb_id' => (int) ($game['id'] ?? 0),
            'slug' => $slug,
            'name' => (string) $game['name'],
            'release_date' => $display['date'],
            'display_date' => $display['label'],
            'month_key' => $display['month_key'],
            'month_label' => $display['month_label'],
            'hypes' => (int) ($game['hypes'] ?? 0),
            'cover_url' => $this->coverUrl($game),
            'platforms' => $this->platforms($game),
            'genres' => $this->genres($game),
            'local_url' => $localSlug ? '/' . $localSlug : null,
        ];
    }

    private function displayDate(array $game): ?array
    {
        $human = '';
        foreach (($game['release_dates'] ?? []) as $releaseDate) {
            if (!is_array($releaseDate)) {
                continue;
            }
            $candidate = trim((string) ($releaseDate['human'] ?? ''));
            if ($candidate !== '') {
                $human = $candidate;
                break;
            }
        }

        if (preg_match('/^(TBA|TBD)$/i', $human)) {
            return ['date' => null, 'label' => 'Fecha por confirmar', 'month_key' => 'sin-fecha', 'month_label' => 'Fecha por confirmar'];
        }

        if (preg_match('/^Q([1-4])\s*(\d{4})?$/i', $human, $m)) {
            $year = ($m[2] ?? '') !== '' ? $m[2] : gmdate('Y');
            $label = 'Q' . $m[1] . ' ' . $year;
            return ['date' => null, 'label' => $label, 'month_key' => $year . '-q' . $m[1], 'month_label' => $label];
        }

        if (preg_match('/^\d{4}$/', $human)) {
            return ['date' => null, 'label' => $human, 'month_key' => $human, 'month_label' => $human];
        }

        $timestamp = isset($game['first_release_date']) ? (int) $game['first_release_date'] : 0;
        if ($timestamp <= 0) {
            if ($human !== '') {
                return ['date' => null, 'label' => $human, 'month_key' => 'sin-fecha', 'month_label' => 'Fecha por confirmar'];
            }
            return null;
        }

        $month = (int) gmdate('n', $timestamp);
        $year = gmdate('Y', $timestamp);

        return [
            'date' => gmdate('Y-m-d', $timestamp),
            'label' => (int) gmdate('j', $timestamp) . ' de ' . mb_strtolower(self::MONTHS[$month]) . ' de ' . $year,
            'month_key' => gmdate('Y-m', $timestamp),
            'month_label' => self::MONTHS[$month] . ' ' . $year,
        ];
    }

    private function coverUrl(array $game): ?string
    {
        $cover = $game['cover'] ?? null;

        if (is_array($cover) && !empty($cover['image_id'])) {
            return \DiarioGames\IGDB\igdbImageUrl((string) $cover['image_id'], 'cover_big');
        }

        if (is_string($cover) && $cover !== '' && !ctype_digit($cover)) {
            return \DiarioGames\IGDB\igdbImageUrl($cover, 'cover_big');
        }

        return null;
    }

    private function platforms(array $game): array
    {
        $names = [];
        foreach (($game['platforms'] ?? []) as $platform) {
            if (!is_array($platform)) {
                continue;
            }
            $abbr = trim((string) ($platform['abbreviation'] ?? ''));
            if ($abbr !== '') {
                $names[$abbr] = true;
            }
        }

        return array_slice(array_keys($names), 0, 4);
    }

    private function genres(array $game): array
    {
        $names = [];
        foreach (($game['genres'] ?? []) as $genre) {
            if (!is_array($genre)) {
                continue;
            }
            $name = trim((string) ($genre['name'] ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
        }

        return array_slice(array_keys($names), 0, 2);
    }

    private function fixture(): ?array
    {
        if ($this->fixtureLoaded) {
            return $this->fixtureData;
        }

        $this->fixtureLoaded = true;
        $file = (string) ($this->settings['fixture_file'] ?? '');
        if ($file !== '' && is_file($file)) {
            $data = json_decode((string) file_get_contents($file), true);
            $this->fixtureData = is_array($data) ? $data : null;
        }

        return $this->fixtureData;
    }

    private function cacheGet(string $key)
    {
        $cache = $this->cache();
        if ($cache === null) {
            return null;
        }

        try {
            return $cache->get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cacheSet(string $key, $value, int $ttlSeconds): void
    {
        $cache = $this->cache();
        if ($cache === null) {
            return;
        }

        try {
            $cache->set($key, $value, max(1, (int) ceil($ttlSeconds / 60)));
        } catch (\Throwable $e) {
        }
    }

    private function cache(): ?\Kirby\Cache\Cache
    {
        try {
            if (!function_exists('kirby') || \Kirby\Cms\App::instance(null, true) === null) {
                return null;
            }

            return kirby()->cache('alv/releases.cache');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
