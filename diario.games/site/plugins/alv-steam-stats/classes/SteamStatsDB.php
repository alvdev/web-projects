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
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS player_history (
                appid        INTEGER NOT NULL,
                timestamp    INTEGER NOT NULL,
                player_count INTEGER NOT NULL,
                PRIMARY KEY (appid, timestamp)
            )
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_ph_appid ON player_history(appid)');
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS game_peaks (
                appid         INTEGER PRIMARY KEY,
                peak_all_time INTEGER NOT NULL DEFAULT 0,
                updated_at    INTEGER NOT NULL
            )
        ');
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
            INSERT INTO player_counts (appid, timestamp, player_count)
            VALUES (:appid, :timestamp, :player_count)
            ON CONFLICT(appid, timestamp) DO UPDATE SET player_count = :player_count2
        ');
        $stmt->execute([
            ':appid'          => $appid,
            ':timestamp'      => $timestamp,
            ':player_count'   => $playerCount,
            ':player_count2'  => $playerCount,
        ]);
    }

    public function upsertPlayerHistory(int $appid, int $timestamp, int $playerCount): void
    {
        $stmt = $this->pdo->prepare('
            INSERT OR REPLACE INTO player_history (appid, timestamp, player_count)
            VALUES (:appid, :timestamp, :player_count)
        ');
        $stmt->execute([
            ':appid' => $appid,
            ':timestamp' => $timestamp,
            ':player_count' => $playerCount,
        ]);
    }

    public function deletePlayerHistoryBefore(int $appid, int $timestamp): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM player_history WHERE appid = :appid AND timestamp < :timestamp');
        $stmt->execute([':appid' => $appid, ':timestamp' => $timestamp]);
    }

    public function getRecentPlayerCounts(int $appid, int $limit = 28): array
    {
        // Prefer player_counts — has full historical data for site games (from collector)
        $stmt = $this->pdo->prepare('
            SELECT timestamp, player_count AS players
            FROM player_counts
            WHERE appid = :appid
            ORDER BY timestamp DESC
            LIMIT :limit
        ');
        $stmt->execute([':appid' => $appid, ':limit' => $limit]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!empty($rows)) {
            return array_reverse($rows);
        }

        // Fall back to player_history (for trending-only games not in site DB)
        $stmt = $this->pdo->prepare('
            SELECT timestamp, player_count AS players
            FROM player_history
            WHERE appid = :appid
            ORDER BY timestamp DESC
            LIMIT :limit
        ');
        $stmt->execute([':appid' => $appid, ':limit' => $limit]);
        return array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC));
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

    public function upsertGamePeak(int $appid, int $peak): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO game_peaks (appid, peak_all_time, updated_at)
            VALUES (:appid, :peak, :now)
            ON CONFLICT(appid) DO UPDATE SET peak_all_time = :peak2, updated_at = :now2
        ');
        $stmt->execute([
            ':appid'  => $appid,
            ':peak'   => $peak,
            ':now'    => time(),
            ':peak2'  => $peak,
            ':now2'   => time(),
        ]);
    }

    public function getGamePeak(int $appid): ?int
    {
        $stmt = $this->pdo->prepare('SELECT peak_all_time FROM game_peaks WHERE appid = :appid');
        $stmt->execute([':appid' => $appid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? (int)$row['peak_all_time'] : null;
    }

    public function getAllAppids(): array
    {
        $stmt = $this->pdo->query('SELECT appid FROM steam_games');
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getAllPlayerData(): array
    {
        $now = time();
        $day = 86400;
        $result = [];

        // Current players (latest timestamp per appid)
        $stmt = $this->pdo->query('
            SELECT p1.appid, p1.player_count AS current_players
            FROM player_counts p1
            INNER JOIN (
                SELECT appid, MAX(timestamp) AS max_ts
                FROM player_counts
                GROUP BY appid
            ) p2 ON p1.appid = p2.appid AND p1.timestamp = p2.max_ts
        ');
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['appid']]['current_players'] = (int)$row['current_players'];
        }

        // Peak 24h
        $stmt = $this->pdo->prepare('
            SELECT appid, MAX(player_count) AS peak_24h
            FROM player_counts
            WHERE timestamp >= :since
            GROUP BY appid
        ');
        $stmt->execute([':since' => $now - $day]);
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['appid']]['peak_24h'] = (int)$row['peak_24h'];
        }

        // All-time peak: max of collector data and steamcharts scrape
        $stmt = $this->pdo->query('
            SELECT appid, MAX(player_count) AS peak_all_time
            FROM player_counts
            GROUP BY appid
        ');
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[(int)$row['appid']]['peak_all_time'] = (int)$row['peak_all_time'];
        }
        $stmt = $this->pdo->query('
            SELECT appid, peak_all_time FROM game_peaks
        ');
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $aid = (int)$row['appid'];
            $gp = (int)$row['peak_all_time'];
            if ($gp > ($result[$aid]['peak_all_time'] ?? 0)) {
                $result[$aid]['peak_all_time'] = $gp;
            }
        }

        // Fill defaults for all known games
        $all = $this->getAllGames();
        foreach ($all as $g) {
            $appid = $g['appid'];
            if (!isset($result[$appid])) {
                $result[$appid] = ['current_players' => 0, 'peak_24h' => 0, 'peak_all_time' => 0];
            }
            if (!isset($result[$appid]['current_players'])) $result[$appid]['current_players'] = 0;
            if (!isset($result[$appid]['peak_24h'])) $result[$appid]['peak_24h'] = 0;
            if (!isset($result[$appid]['peak_all_time'])) $result[$appid]['peak_all_time'] = 0;
        }

        return $result;
    }

    public function getAllPlayerDataCached(): array
    {
        try {
            $cache = kirby()->cache('alv/steam-stats.cache');
            $cached = $cache->get('player-data-summary');
            if (is_array($cached) && isset($cached['value'], $cached['timestamp'])) {
                if ((time() - $cached['timestamp']) < 300) {
                    return $cached['value'];
                }
            }
        } catch (\Throwable $e) {}

        $data = $this->getAllPlayerData();

        try {
            $cache = kirby()->cache('alv/steam-stats.cache');
            $cache->set('player-data-summary', ['value' => $data, 'timestamp' => time()]);
        } catch (\Throwable $e) {}

        return $data;
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
