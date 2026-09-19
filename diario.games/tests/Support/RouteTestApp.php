<?php

declare(strict_types=1);

namespace Tests\Support;

use Kirby\Cms\App;
use Kirby\Http\Request;

final class RouteTestApp
{
    public static array $spawns = [];
    private static ?App $app = null;
    private static string $tmp = '';

    public static function boot(array $options = []): void
    {
        PluginClasses::load();

        self::$tmp = sys_get_temp_dir() . '/diario-routes-' . bin2hex(random_bytes(6));
        foreach (['cache', 'media', 'sessions', 'accounts', 'content'] as $dir) {
            mkdir(self::$tmp . '/' . $dir, 0775, true);
        }
        file_put_contents(self::$tmp . '/content/site.txt', "Title: Test Site\n");

        putenv('STEAM_STATS_DB_PATH=' . self::$tmp . '/steam_stats.db');

        self::$app = new App([
            'roots' => [
                'index' => dirname(__DIR__, 2),
                'content' => self::$tmp . '/content',
                'cache' => self::$tmp . '/cache',
                'media' => self::$tmp . '/media',
                'sessions' => self::$tmp . '/sessions',
                'accounts' => self::$tmp . '/accounts',
            ],
            'options' => array_replace_recursive([
                'debug' => false,
                'igdb' => ['client_id' => '', 'client_secret' => ''],
                'alv.steam-stats.charts-ttl' => 900,
                'alv.steam-stats.charts-spawner' => function (int $chunk): void {
                    self::$spawns[] = $chunk;
                },
            ], $options),
        ]);
    }

    public static function call(string $path, array $query = [], string $method = 'GET'): mixed
    {
        $request = new Request(['query' => $query]);
        $property = new \ReflectionProperty(App::class, 'request');
        $property->setAccessible(true);
        $property->setValue(self::$app, $request);

        return self::$app->call($path, $method);
    }

    public static function app(): App
    {
        return self::$app;
    }

    public static function tmp(): string
    {
        return self::$tmp;
    }

    public static function content(string $path = ''): string
    {
        return self::$tmp . '/content' . ($path !== '' ? '/' . $path : '');
    }

    public static function shutdown(): void
    {
        putenv('STEAM_STATS_DB_PATH');
        Files::removeDir(self::$tmp);
        self::$app = null;
        self::$spawns = [];
    }
}
