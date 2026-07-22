<?php

namespace Alv\Prices;

class StorePriceDB
{
    private \PDO $pdo;

    public function __construct()
    {
        $dbDir = dirname(__DIR__, 4) . '/sqlite';
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0755, true);
        }

        $dbPath = $dbDir . '/steam_stats.db';
        $this->pdo = new \PDO('sqlite:' . $dbPath, options: [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec('PRAGMA journal_mode=WAL');
        $this->pdo->exec('PRAGMA synchronous=NORMAL');

        $this->createTable();
    }

    private function createTable(): void
    {
        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS store_prices (
                slug             TEXT NOT NULL,
                store            TEXT NOT NULL,
                url              TEXT NOT NULL,
                price            REAL,
                initial_price    REAL,
                discount_percent INTEGER DEFAULT 0,
                currency         TEXT DEFAULT \'EUR\',
                platforms        TEXT DEFAULT \'\',
                scraped_at       INTEGER NOT NULL,
                PRIMARY KEY (slug, store)
            )
        ');

        try {
            $this->pdo->exec('ALTER TABLE store_prices ADD COLUMN platforms TEXT DEFAULT \'\'');
        } catch (\PDOException $e) {
            // column already exists
        }

        $this->pdo->exec('
            CREATE TABLE IF NOT EXISTS g2a_catalog (
                product_id TEXT PRIMARY KEY,
                name       TEXT NOT NULL,
                updated_at INTEGER NOT NULL
            )
        ');
        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_g2a_name ON g2a_catalog(name)');
    }

    public function getPrice(string $slug, string $store): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT slug, store, url, price, initial_price, discount_percent, currency, platforms, scraped_at
             FROM store_prices WHERE slug = :slug AND store = :store'
        );
        $stmt->execute([':slug' => $slug, ':store' => $store]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAllPrices(string $slug): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT slug, store, url, price, initial_price, discount_percent, currency, platforms, scraped_at
             FROM store_prices WHERE slug = :slug ORDER BY price ASC'
        );
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function upsertUrl(string $slug, string $store, string $url): void
    {
        $now = time();
        $stmt = $this->pdo->prepare('
            INSERT INTO store_prices (slug, store, url, scraped_at)
            VALUES (:slug, :store, :url, :now)
            ON CONFLICT(slug, store) DO UPDATE SET url = :url2, scraped_at = :now2
            WHERE price IS NULL
        ');
        $stmt->execute([
            ':slug'  => $slug,
            ':store' => $store,
            ':url'   => $url,
            ':url2'  => $url,
            ':now'   => $now,
            ':now2'  => $now,
        ]);
    }

    public function upsertPrice(string $slug, string $store, array $priceData): void
    {
        $now = time();
        $stmt = $this->pdo->prepare('
            INSERT INTO store_prices (slug, store, url, price, initial_price, discount_percent, currency, platforms, scraped_at)
            VALUES (:slug, :store, :url, :price, :initial, :discount, :currency, :platforms, :now)
            ON CONFLICT(slug, store) DO UPDATE SET
                price = :price2,
                initial_price = :initial2,
                discount_percent = :discount2,
                currency = :currency2,
                platforms = :platforms2,
                scraped_at = :now2
        ');
        $stmt->execute([
            ':slug'       => $slug,
            ':store'      => $store,
            ':url'        => $priceData['url'] ?? '',
            ':price'      => $priceData['price'] ?? null,
            ':initial'    => $priceData['initialPrice'] ?? null,
            ':discount'   => $priceData['discount'] ?? 0,
            ':currency'   => $priceData['currency'] ?? 'EUR',
            ':platforms'  => $priceData['platforms'] ?? '',
            ':price2'     => $priceData['price'] ?? null,
            ':initial2'   => $priceData['initialPrice'] ?? null,
            ':discount2'  => $priceData['discount'] ?? 0,
            ':currency2'  => $priceData['currency'] ?? 'EUR',
            ':platforms2' => $priceData['platforms'] ?? '',
            ':now'        => $now,
            ':now2'       => $now,
        ]);
    }

    public function isExpired(int $scrapedAt, int $ttl = 86400): bool
    {
        return (time() - $scrapedAt) >= $ttl;
    }

    public function upsertG2aProduct(string $productId, string $name): void
    {
        $now = time();
        $stmt = $this->pdo->prepare('
            INSERT INTO g2a_catalog (product_id, name, updated_at)
            VALUES (:pid, :name, :now)
            ON CONFLICT(product_id) DO UPDATE SET name = :name2, updated_at = :now2
        ');
        $stmt->execute([
            ':pid'   => $productId,
            ':name'  => $name,
            ':name2' => $name,
            ':now'   => $now,
            ':now2'  => $now,
        ]);
    }

    public function findG2aProductId(string $gameName): ?string
    {
        $normalized = mb_strtolower(trim($gameName));

        $stmt = $this->pdo->prepare(
            'SELECT product_id, name FROM g2a_catalog ORDER BY updated_at DESC LIMIT 10000'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $bestScore = 0;
        $bestId = null;

        foreach ($rows as $row) {
            $catName = mb_strtolower(trim($row['name']));

            if ($catName === $normalized) {
                return $row['product_id'];
            }

            if (str_contains($catName, $normalized) || str_contains($normalized, $catName)) {
                $score = strlen($catName);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestId = $row['product_id'];
                }
            }
        }

        return $bestId;
    }

    public function deleteStaleG2aProducts(int $olderThanHours = 48): void
    {
        $cutoff = time() - ($olderThanHours * 3600);
        $stmt = $this->pdo->prepare('DELETE FROM g2a_catalog WHERE updated_at < :cutoff');
        $stmt->execute([':cutoff' => $cutoff]);
    }
}
