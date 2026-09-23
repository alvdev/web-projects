<?php

namespace Alv\TwitchStats;

class TwitchStats
{
    private array $settings;
    private ?TwitchClient $client;
    private TwitchTrackerClient $tracker;
    private TwitchStatsDB $db;
    private bool $fixtureLoaded = false;
    private ?array $fixtureData = null;

    public function __construct(
        array $settings = [],
        ?TwitchClient $client = null,
        ?TwitchTrackerClient $tracker = null,
        ?TwitchStatsDB $db = null
    ) {
        $this->settings = array_merge([
            'client_id' => '',
            'client_secret' => '',
            'cache_ttl' => 300,
            'tracker_ttl' => 21600,
            'history_ttl' => 604800,
            'tracker_limit' => 20,
            'fixture_file' => '',
        ], $settings);

        $this->client = $client ?? new TwitchClient(
            (string) $this->settings['client_id'],
            (string) $this->settings['client_secret']
        );
        $this->tracker = $tracker ?? new TwitchTrackerClient();
        $this->db = $db ?? new TwitchStatsDB();
    }

    public function getTopGames(int $limit = 10): array
    {
        $rows = array_slice($this->liveGames(), 0, max(0, $limit));
        if (empty($rows)) {
            return [];
        }

        $viewersByGame = TwitchClient::aggregateByGame($this->liveStreams());
        $history = $this->historySeries('game');

        foreach ($rows as $index => &$row) {
            $id = (string) $row['id'];
            $aggregated = $viewersByGame[$id] ?? ['viewers' => 0, 'channels' => 0];
            $summary = $this->gameSummary($id, $index);

            $row = [
                'rank' => $index + 1,
                'id' => $id,
                'name' => (string) $row['name'],
                'box_art_url' => TwitchClient::boxArt((string) ($row['box_art_url'] ?? '')),
                'igdb_id' => $row['igdb_id'] ?? null,
                'viewers' => (int) $aggregated['viewers'],
                'live_channels' => (int) $aggregated['channels'],
                'avg_viewers' => $summary['avg_viewers'] ?? null,
                'avg_channels' => $summary['avg_channels'] ?? null,
                'twitch_rank' => $summary['rank'] ?? null,
                'hours_watched' => $summary['hours_watched'] ?? null,
                'history' => $history[$id] ?? [],
                'change_pct' => self::changePct($history[$id] ?? []),
            ];
        }
        unset($row);

        return $rows;
    }

    public function getTopStreamers(int $limit = 10): array
    {
        return $this->buildStreamers($this->liveStreams(), 'streamer', $limit);
    }

    public function getTopSpanish(int $limit = 10): array
    {
        return $this->buildStreamers($this->liveSpanish(), 'streamer_es', $limit);
    }

    private function buildStreamers(array $streams, string $entityType, int $limit): array
    {
        $rows = array_slice($streams, 0, max(0, $limit));
        if (empty($rows)) {
            return [];
        }

        $avatars = $this->avatarsFor($rows);
        $history = $this->historySeries($entityType);

        foreach ($rows as $index => &$row) {
            $userId = (string) $row['user_id'];
            $login = (string) $row['user_login'];
            $summary = $this->channelSummary($login, $index);

            $row = [
                'rank' => $index + 1,
                'user_id' => $userId,
                'login' => $login,
                'name' => (string) ($row['user_name'] ?? $login),
                'url' => 'https://www.twitch.tv/' . $login,
                'avatar_url' => (string) ($avatars[$userId] ?? ''),
                'game_id' => (string) ($row['game_id'] ?? ''),
                'game_name' => (string) ($row['game_name'] ?? ''),
                'viewers' => (int) ($row['viewer_count'] ?? 0),
                'avg_viewers' => $summary['avg_viewers'] ?? null,
                'max_viewers' => $summary['max_viewers'] ?? null,
                'followers' => $summary['followers'] ?? null,
                'followers_total' => $summary['followers_total'] ?? null,
                'twitch_rank' => $summary['rank'] ?? null,
                'history' => $history[$userId] ?? [],
                'change_pct' => self::changePct($history[$userId] ?? []),
            ];
        }
        unset($row);

        return $rows;
    }

    private function liveGames(): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $fixture['games'] ?? [];
        }

        $cached = $this->cacheGet('top-games');
        if (is_array($cached)) {
            return $cached;
        }

        $games = $this->client->getTopGames(100);
        if (!empty($games)) {
            $this->cacheSet('top-games', $games, (int) $this->settings['cache_ttl']);
        }

        return $games;
    }

    private function liveStreams(): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $fixture['streamers'] ?? [];
        }

        $cached = $this->cacheGet('top-streams');
        if (is_array($cached)) {
            return $cached;
        }

        $streams = $this->client->getStreams(100);
        if (!empty($streams)) {
            $this->cacheSet('top-streams', $streams, (int) $this->settings['cache_ttl']);
        }

        return $streams;
    }

    private function liveSpanish(): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $fixture['spanish'] ?? [];
        }

        $cached = $this->cacheGet('top-spanish');
        if (is_array($cached)) {
            return $cached;
        }

        $streams = $this->client->getStreams(100, 'es');
        if (!empty($streams)) {
            $this->cacheSet('top-spanish', $streams, (int) $this->settings['cache_ttl']);
        }

        return $streams;
    }

    private function avatarsFor(array $streams): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $fixture['avatars'] ?? [];
        }

        $userIds = array_map(fn (array $stream): string => (string) $stream['user_id'], $streams);
        $cached = $this->cacheGet('avatars');
        $avatars = is_array($cached) ? $cached : [];

        $missing = array_values(array_filter($userIds, fn (string $id): bool => !isset($avatars[$id])));
        if (!empty($missing)) {
            $fetched = $this->client->getAvatars($missing);
            if (!empty($fetched)) {
                $avatars += $fetched;
                $this->cacheSet('avatars', $avatars, 86400);
            }
        }

        return $avatars;
    }

    private function gameSummary(string $twitchId, int $index): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $fixture['tracker_games'][$twitchId] ?? [];
        }

        if ($index >= (int) $this->settings['tracker_limit']) {
            return [];
        }

        $key = 'tracker.game.' . $twitchId;
        $cached = $this->cacheGet($key);
        if (is_array($cached)) {
            return $cached;
        }

        $summary = $this->tracker->getGameSummary($twitchId) ?? [];
        if (!empty($summary)) {
            $this->cacheSet($key, $summary, (int) $this->settings['tracker_ttl']);
        }

        return $summary;
    }

    private function channelSummary(string $login, int $index): array
    {
        $fixture = $this->fixture();
        if ($fixture !== null) {
            return $fixture['tracker_channels'][$login] ?? [];
        }

        if ($index >= (int) $this->settings['tracker_limit']) {
            return [];
        }

        $key = 'tracker.channel.' . $login;
        $cached = $this->cacheGet($key);
        if (is_array($cached)) {
            return $cached;
        }

        $summary = $this->tracker->getChannelSummary($login) ?? [];
        if (!empty($summary)) {
            $this->cacheSet($key, $summary, (int) $this->settings['tracker_ttl']);
        }

        return $summary;
    }

    private function historySeries(string $entityType): array
    {
        try {
            return $this->db->getHistorySeries($entityType, time() - (int) $this->settings['history_ttl']);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function changePct(array $history): ?float
    {
        if (count($history) < 2) {
            return null;
        }

        $first = (int) $history[0]['viewers'];
        $last = (int) $history[count($history) - 1]['viewers'];
        if ($first <= 0) {
            return null;
        }

        return round((($last - $first) / $first) * 100, 1);
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

            return kirby()->cache('alv/twitch-stats.cache');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
