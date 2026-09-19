<?php

declare(strict_types=1);

namespace Tests\Support;

final class CliRunner
{
    public static function run(array $args, array $env = []): array
    {
        $root = dirname(__DIR__, 2);
        $script = $root . '/scripts/collect-steam-stats.php';

        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
        foreach ($args as $arg) {
            $cmd .= ' ' . escapeshellarg((string) $arg);
        }

        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process = proc_open($cmd, $descriptors, $pipes, $root, array_merge(getenv(), $env));
        if (!is_resource($process)) {
            throw new \RuntimeException('Failed to start CLI');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [
            'exit' => proc_close($process),
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
