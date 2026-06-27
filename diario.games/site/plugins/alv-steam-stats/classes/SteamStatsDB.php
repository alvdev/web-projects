<?php

namespace Alv\SteamStats;

class SteamStatsDB
{
    private \PDO $pdo;

    public function __construct()
    {
        $dbDir = dirname(__DIR__, 4) . '/sqlite';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dbPath = $dbDir . '/steam_stats.db';
        $this->pdo = new \PDO('sqlite:' . $dbPath, options: [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA synchronous=NORMAL');

        $this->createTables();
    }

    private function createTables(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS steam_games (
                appid   INTEGER PRIMARY KEY,
                slug    TEXT UNIQUE NOT NULL,
                name    TEXT NOT NULL
            )
        ');
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS player_counts (
                appid        INTEGER NOT NULL,
                timestamp    INTEGER NOT NULL,
                player_count INTEGER NOT NULL,
                PRIMARY KEY (appid, timestamp)
            )
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_pc_appid ON player_counts(appid)');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_pc_ts ON player_counts(timestamp)');
    }

    public function upsertGame(int $appid, string $slug, string $name): void
    {
        $normalized = self::normalizeSlug($slug);
        $stmt = $this->pdo->prepare('
            INSERT INTO steam_games (appid, slug, name)
            VALUES (:appid, :slug, :name)
            ON CONFLICT(appid) DO UPDATE SET slug = :slug2, name = :name2
        ');
        $stmt->execute([
            ':appid' => $appid,
            ':slug'  => $normalized,
            ':name'  => $name,
            ':slug2' => $normalized,
            ':name2' => $name,
        ]);
    }

    public function getGameBySlug(string $slug): ?array
    {
        $normalized = self::normalizeSlug($slug);
        $stmt = $this->pdo->prepare('SELECT appid, slug, name FROM steam_games WHERE slug = :slug');
        $stmt->execute([':slug' => $normalized]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private const MAX_SEARCH_RESULTS = 15;

    public function searchGames(string $query): array
    {
        $stmt = $this->pdo->prepare('SELECT appid, slug, name FROM steam_games WHERE name LIKE :query LIMIT ' . self::MAX_SEARCH_RESULTS);
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function insertPlayerCount(int $appid, int $timestamp, int $playerCount): void
    {
        $stmt = $this->pdo->prepare('
            INSERT OR IGNORE INTO player_counts (appid, timestamp, player_count)
            VALUES (:appid, :timestamp, :player_count)
        ');
        $stmt->execute([
            ':appid'        => $appid,
            ':timestamp'    => $timestamp,
            ':player_count' => $playerCount,
        ]);
    }

    public function getPlayerCounts(int $appid, int $since): array
    {
        $stmt = $this->pdo->prepare('
            SELECT timestamp, player_count AS p FROM player_counts
            WHERE appid = :appid AND timestamp >= :since
            ORDER BY timestamp ASC
        ');
        $stmt->execute([':appid' => $appid, ':since' => $since]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLatestTimestamp(int $appid): ?int
    {
        $stmt = $this->pdo->prepare('SELECT MAX(timestamp) FROM player_counts WHERE appid = :appid');
        $stmt->execute([':appid' => $appid]);
        $row = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $row !== false && $row !== null ? (int) $row : null;
    }

    public function getCurrentPlayers(int $appid): ?int
    {
        $stmt = $this->pdo->prepare('
            SELECT player_count FROM player_counts
            WHERE appid = :appid
            ORDER BY timestamp DESC
            LIMIT 1
        ');
        $stmt->execute([':appid' => $appid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['player_count'] : null;
    }

    public function getPeakPlayers(int $appid, int $since): ?int
    {
        $stmt = $this->pdo->prepare('
            SELECT MAX(player_count) AS peak FROM player_counts
            WHERE appid = :appid AND timestamp >= :since
        ');
        $stmt->execute([':appid' => $appid, ':since' => $since]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['peak'] !== null ? (int)$row['peak'] : null;
    }

    public function getAllAppids(): array
    {
        $stmt = $this->pdo->query('SELECT appid FROM steam_games');
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public static function normalizeSlug(string $slug): string
    {
        $replacements = [
            '/\bxvi\b/i' => '16',
            '/\bxv\b/i'  => '15',
            '/\bxiv\b/i' => '14',
            '/\bxiii\b/i'=> '13',
            '/\bxii\b/i' => '12',
            '/\bxi\b/i'  => '11',
            '/\bix\b/i'  => '9',
            '/\bviii\b/i'=> '8',
            '/\bvii\b/i' => '7',
            '/\bvi\b/i'  => '6',
            '/\biv\b/i'  => '4',
            '/\biii\b/i' => '3',
            '/\bii\b/i'  => '2',
            '/\bx\b/i'   => '10',
            '/\bv\b/i'   => '5',
            '/\bi\b/i'   => '1',
        ];

        return preg_replace(array_keys($replacements), array_values($replacements), $slug);
    }
}
