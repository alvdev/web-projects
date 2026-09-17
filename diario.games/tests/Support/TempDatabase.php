<?php

declare(strict_types=1);

namespace Tests\Support;

trait TempDatabase
{
    protected string $tempDatabaseDir = '';
    protected string $tempDatabasePath = '';
    private string|false $previousDbPathEnv = false;

    protected function setUpTempDatabase(): void
    {
        $this->tempDatabaseDir = sys_get_temp_dir() . '/diario-tests-' . bin2hex(random_bytes(6));
        if (!mkdir($this->tempDatabaseDir, 0775, true) && !is_dir($this->tempDatabaseDir)) {
            throw new \RuntimeException('Could not create temp dir: ' . $this->tempDatabaseDir);
        }

        $this->tempDatabasePath = $this->tempDatabaseDir . '/steam_stats.db';
        $this->previousDbPathEnv = getenv('STEAM_STATS_DB_PATH');
        putenv('STEAM_STATS_DB_PATH=' . $this->tempDatabasePath);

        if (function_exists('DiarioGames\\IGDB\\_db')) {
            \DiarioGames\IGDB\_db(true);
        }
    }

    protected function tearDownTempDatabase(): void
    {
        if ($this->tempDatabaseDir === '') {
            return;
        }

        if ($this->previousDbPathEnv === false) {
            putenv('STEAM_STATS_DB_PATH');
        } else {
            putenv('STEAM_STATS_DB_PATH=' . $this->previousDbPathEnv);
        }

        if (function_exists('DiarioGames\\IGDB\\_db')) {
            \DiarioGames\IGDB\_db(true);
        }

        foreach (glob($this->tempDatabaseDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempDatabaseDir);
        $this->tempDatabaseDir = '';
        $this->tempDatabasePath = '';
    }
}
