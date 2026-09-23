<?php

namespace Alv\TwitchStats;

class TwitchStatsDB
{
    public const ENTITY_TYPES = ['game', 'streamer', 'streamer_es'];

    private \PDO $pdo;

    public function __construct(?string $dbPath = null)
    {
        $dbPath ??= (getenv('TWITCH_STATS_DB_PATH') ?: dirname(__DIR__, 4) . '/sqlite/twitch_stats.db');

        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $this->pdo = new \PDO('sqlite:' . $dbPath, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA synchronous=NORMAL');

        $this->createTables();
    }

    private function createTables(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS twitch_games (
                twitch_id   TEXT PRIMARY KEY,
                name        TEXT NOT NULL,
                igdb_id     INTEGER,
                box_art_url TEXT,
                updated_at  INTEGER NOT NULL
            )
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_tg_igdb ON twitch_games(igdb_id)');
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS twitch_streamers (
                user_id      TEXT PRIMARY KEY,
                login        TEXT NOT NULL,
                display_name TEXT NOT NULL,
                avatar_url   TEXT,
                language     TEXT,
                updated_at   INTEGER NOT NULL
            )
        ');
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS viewer_snapshots (
                entity_type TEXT NOT NULL,
                entity_id   TEXT NOT NULL,
                timestamp   INTEGER NOT NULL,
                viewers     INTEGER NOT NULL,
                rank        INTEGER NOT NULL,
                game_name   TEXT,
                PRIMARY KEY (entity_type, entity_id, timestamp)
            )
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_vs_type_ts ON viewer_snapshots(entity_type, timestamp)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_vs_entity ON viewer_snapshots(entity_type, entity_id, timestamp)');
    }

    public function upsertGame(string $twitchId, string $name, ?int $igdbId, string $boxArt): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO twitch_games (twitch_id, name, igdb_id, box_art_url, updated_at)
            VALUES (:id, :name, :igdb, :art, :ts)
            ON CONFLICT(twitch_id) DO UPDATE SET
                name = excluded.name,
                igdb_id = excluded.igdb_id,
                box_art_url = excluded.box_art_url,
                updated_at = excluded.updated_at
        ');
        $stmt->execute([
            ':id' => $twitchId,
            ':name' => $name,
            ':igdb' => $igdbId,
            ':art' => $boxArt,
            ':ts' => time(),
        ]);
    }

    public function upsertStreamer(string $userId, string $login, string $displayName, string $avatarUrl, string $language): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO twitch_streamers (user_id, login, display_name, avatar_url, language, updated_at)
            VALUES (:id, :login, :name, :avatar, :lang, :ts)
            ON CONFLICT(user_id) DO UPDATE SET
                login = excluded.login,
                display_name = excluded.display_name,
                avatar_url = excluded.avatar_url,
                language = excluded.language,
                updated_at = excluded.updated_at
        ');
        $stmt->execute([
            ':id' => $userId,
            ':login' => $login,
            ':name' => $displayName,
            ':avatar' => $avatarUrl,
            ':lang' => $language,
            ':ts' => time(),
        ]);
    }

    public function insertSnapshot(string $entityType, string $entityId, int $timestamp, int $viewers, int $rank, string $gameName = ''): void
    {
        $this->assertEntityType($entityType);

        $stmt = $this->pdo->prepare('
            INSERT OR IGNORE INTO viewer_snapshots (entity_type, entity_id, timestamp, viewers, rank, game_name)
            VALUES (:type, :id, :ts, :viewers, :rank, :game)
        ');
        $stmt->execute([
            ':type' => $entityType,
            ':id' => $entityId,
            ':ts' => $timestamp,
            ':viewers' => $viewers,
            ':rank' => $rank,
            ':game' => $gameName,
        ]);
    }

    public function hasSnapshotAt(string $entityType, int $timestamp): bool
    {
        $this->assertEntityType($entityType);

        $stmt = $this->pdo->prepare('SELECT 1 FROM viewer_snapshots WHERE entity_type = :type AND timestamp = :ts LIMIT 1');
        $stmt->execute([':type' => $entityType, ':ts' => $timestamp]);

        return $stmt->fetchColumn() !== false;
    }

    public function getSnapshots(string $entityType, string $entityId, int $since): array
    {
        $this->assertEntityType($entityType);

        $stmt = $this->pdo->prepare('
            SELECT timestamp, viewers, rank, game_name
            FROM viewer_snapshots
            WHERE entity_type = :type AND entity_id = :id AND timestamp >= :since
            ORDER BY timestamp ASC
        ');
        $stmt->execute([':type' => $entityType, ':id' => $entityId, ':since' => $since]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getHistorySeries(string $entityType, int $since): array
    {
        $this->assertEntityType($entityType);

        $stmt = $this->pdo->prepare('
            SELECT entity_id, timestamp, viewers, rank, game_name
            FROM viewer_snapshots
            WHERE entity_type = :type AND timestamp >= :since
            ORDER BY entity_id ASC, timestamp ASC
        ');
        $stmt->execute([':type' => $entityType, ':since' => $since]);

        $series = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $series[$row['entity_id']][] = [
                'timestamp' => (int) $row['timestamp'],
                'viewers' => (int) $row['viewers'],
                'rank' => (int) $row['rank'],
                'game_name' => $row['game_name'],
            ];
        }

        return $series;
    }

    public function getGameByIgdbId(int $igdbId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM twitch_games WHERE igdb_id = :id LIMIT 1');
        $stmt->execute([':id' => $igdbId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function getStreamer(string $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM twitch_streamers WHERE user_id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    public function pruneBefore(int $timestamp): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM viewer_snapshots WHERE timestamp < :ts');
        $stmt->execute([':ts' => $timestamp]);

        return $stmt->rowCount();
    }

    public function countSnapshots(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM viewer_snapshots')->fetchColumn();
    }

    public function countGames(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM twitch_games')->fetchColumn();
    }

    private function assertEntityType(string $entityType): void
    {
        if (!in_array($entityType, self::ENTITY_TYPES, true)) {
            throw new \InvalidArgumentException('Unknown Twitch entity type: ' . $entityType);
        }
    }
}
