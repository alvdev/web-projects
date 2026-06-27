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
                name    TEXT NOT NULL,
                igdb_id INTEGER
            )
        ');
        // Add igdb_id column if upgrading an existing DB (ignore if already exists)
        try {
            $this->pdo->exec('ALTER TABLE steam_games ADD COLUMN igdb_id INTEGER');
        } catch (\PDOException $e) {
            // Column already exists — ignore
        }
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

    public function upsertGame(int $appid, string $slug, string $name, ?int $igdbId = null): void
    {
        $normalized = self::normalizeSlug($slug);

        $existing = $this->pdo->prepare('SELECT slug, igdb_id FROM steam_games WHERE appid = :appid');
        $existing->execute([':appid' => $appid]);
        $row = $existing->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            $existingSlug = $row['slug'];
            $isNewDup = preg_match('/--\d+$/', $normalized);
            $isOldDup = preg_match('/--\d+$/', $existingSlug);
            if ($isNewDup && !$isOldDup) {
                $stmt = $this->pdo->prepare('UPDATE steam_games SET name = :name, igdb_id = COALESCE(:igdb_id, igdb_id) WHERE appid = :appid');
                $stmt->execute([':name' => $name, ':igdb_id' => $igdbId, ':appid' => $appid]);
                return;
            }
            // If existing slug is also a dup, a non-dup is always better
            // But at this point we're updating, so just merge igdb_id if missing
            if ($igdbId !== null && $row['igdb_id'] === null) {
                $stmt = $this->pdo->prepare('UPDATE steam_games SET slug = :slug, name = :name, igdb_id = :igdb_id WHERE appid = :appid');
                $stmt->execute([':slug' => $normalized, ':name' => $name, ':igdb_id' => $igdbId, ':appid' => $appid]);
                return;
            }
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO steam_games (appid, slug, name, igdb_id)
            VALUES (:appid, :slug, :name, :igdb_id)
            ON CONFLICT(appid) DO UPDATE SET slug = :slug2, name = :name2, igdb_id = COALESCE(:igdb_id2, igdb_id)
        ');
        $stmt->execute([
            ':appid'    => $appid,
            ':slug'     => $normalized,
            ':name'     => $name,
            ':igdb_id'  => $igdbId,
            ':slug2'    => $normalized,
            ':name2'    => $name,
            ':igdb_id2' => $igdbId,
        ]);
    }

    public function getGameBySlug(string $slug): ?array
    {
        $normalized = self::normalizeSlug($slug);
        $stmt = $this->pdo->prepare('SELECT appid, slug, name, igdb_id FROM steam_games WHERE slug = :slug');
        $stmt->execute([':slug' => $normalized]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getGameByAppId(int $appid): ?array
    {
        $stmt = $this->pdo->prepare('SELECT appid, slug, name, igdb_id FROM steam_games WHERE appid = :appid');
        $stmt->execute([':appid' => $appid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getGameByIgdbId(int $igdbId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT appid, slug, name, igdb_id FROM steam_games WHERE igdb_id = :igdb_id');
        $stmt->execute([':igdb_id' => $igdbId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAllGames(): array
    {
        $stmt = $this->pdo->query('SELECT appid, slug, name, igdb_id FROM steam_games');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private const MAX_SEARCH_RESULTS = 15;

    public function searchGames(string $query): array
    {
        $stmt = $this->pdo->prepare('SELECT appid, slug, name, igdb_id FROM steam_games WHERE name LIKE :query LIMIT ' . self::MAX_SEARCH_RESULTS);
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
