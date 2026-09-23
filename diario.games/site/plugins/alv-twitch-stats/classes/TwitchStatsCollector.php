<?php

namespace Alv\TwitchStats;

class TwitchStatsCollector
{
    private TwitchClient $client;
    private TwitchStatsDB $db;
    private int $retentionSeconds;

    public function __construct(TwitchClient $client, ?TwitchStatsDB $db = null, int $retentionSeconds = 7776000)
    {
        $this->client = $client;
        $this->db = $db ?? new TwitchStatsDB();
        $this->retentionSeconds = $retentionSeconds;
    }

    public function snapshot(?int $now = null): array
    {
        $now ??= time();
        $hour = $now - ($now % 3600);

        $stats = ['games' => 0, 'streamers' => 0, 'spanish' => 0, 'skipped' => false];

        if (!$this->client->isConfigured()) {
            $stats['skipped'] = true;

            return $stats;
        }

        $alreadySnapped = $this->db->hasSnapshotAt('game', $hour)
            && $this->db->hasSnapshotAt('streamer', $hour)
            && $this->db->hasSnapshotAt('streamer_es', $hour);

        if ($alreadySnapped) {
            $stats['skipped'] = true;

            return $stats;
        }

        $streams = $this->client->getStreams(100);
        $viewersByGame = TwitchClient::aggregateByGame($streams);

        foreach ($this->client->getTopGames(100) as $index => $game) {
            $id = (string) $game['id'];
            $aggregated = $viewersByGame[$id] ?? ['viewers' => 0];

            $this->db->upsertGame($id, (string) $game['name'], $game['igdb_id'] ?? null, (string) ($game['box_art_url'] ?? ''));
            $this->db->insertSnapshot('game', $id, $hour, (int) $aggregated['viewers'], $index + 1);
            $stats['games']++;
        }

        $stats['streamers'] = $this->snapshotStreamers($streams, 'streamer', '', $hour, 0);
        $stats['spanish'] = $this->snapshotStreamers($this->client->getStreams(100, 'es'), 'streamer_es', 'es', $hour, 0);

        return $stats;
    }

    public function prune(?int $now = null): int
    {
        $now ??= time();

        return $this->db->pruneBefore($now - $this->retentionSeconds);
    }

    private function snapshotStreamers(array $streams, string $entityType, string $language, int $hour, int $offset): int
    {
        if (empty($streams)) {
            return 0;
        }

        $userIds = array_map(fn (array $stream): string => (string) $stream['user_id'], $streams);
        $avatars = $this->client->getAvatars($userIds);

        $count = 0;
        foreach ($streams as $index => $stream) {
            $userId = (string) $stream['user_id'];
            $this->db->upsertStreamer(
                $userId,
                (string) $stream['user_login'],
                (string) ($stream['user_name'] ?? $stream['user_login']),
                (string) ($avatars[$userId] ?? ''),
                $language
            );
            $this->db->insertSnapshot(
                $entityType,
                $userId,
                $hour,
                (int) ($stream['viewer_count'] ?? 0),
                $offset + $index + 1,
                (string) ($stream['game_name'] ?? '')
            );
            $count++;
        }

        return $count;
    }
}
