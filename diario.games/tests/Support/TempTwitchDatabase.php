<?php

declare(strict_types=1);

namespace Tests\Support;

trait TempTwitchDatabase
{
    protected string $tempTwitchDir = '';
    protected string $tempTwitchPath = '';
    private string|false $previousTwitchDbPathEnv = false;

    protected function setUpTempTwitchDatabase(): void
    {
        $this->tempTwitchDir = sys_get_temp_dir() . '/diario-twitch-tests-' . bin2hex(random_bytes(6));
        if (!mkdir($this->tempTwitchDir, 0775, true) && !is_dir($this->tempTwitchDir)) {
            throw new \RuntimeException('Could not create temp dir: ' . $this->tempTwitchDir);
        }

        $this->tempTwitchPath = $this->tempTwitchDir . '/twitch_stats.db';
        $this->previousTwitchDbPathEnv = getenv('TWITCH_STATS_DB_PATH');
        putenv('TWITCH_STATS_DB_PATH=' . $this->tempTwitchPath);
    }

    protected function tearDownTempTwitchDatabase(): void
    {
        if ($this->tempTwitchDir === '') {
            return;
        }

        if ($this->previousTwitchDbPathEnv === false) {
            putenv('TWITCH_STATS_DB_PATH');
        } else {
            putenv('TWITCH_STATS_DB_PATH=' . $this->previousTwitchDbPathEnv);
        }

        foreach (glob($this->tempTwitchDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->tempTwitchDir);
        $this->tempTwitchDir = '';
        $this->tempTwitchPath = '';
    }
}
