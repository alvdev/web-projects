<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class LanzamientosWarmRouteTest extends TestCase
{
    private static string $fixtureFile;

    public static function setUpBeforeClass(): void
    {
        self::$fixtureFile = sys_get_temp_dir() . '/releases-warm-fixture-' . bin2hex(random_bytes(4)) . '.json';
        file_put_contents(self::$fixtureFile, json_encode([
            'recentlyReleased' => [[
                'igdb_id' => 2,
                'slug' => 'warm-recent',
                'name' => 'Warm Recent',
                'release_date' => '2026-09-20',
                'display_date' => '20 de septiembre de 2026',
                'month_key' => '2026-09',
                'month_label' => 'Septiembre 2026',
                'hypes' => 30,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'upcoming' => [[
                'igdb_id' => 1,
                'slug' => 'warm-upcoming',
                'name' => 'Warm Upcoming',
                'release_date' => '2026-11-01',
                'display_date' => '1 de noviembre de 2026',
                'month_key' => '2026-11',
                'month_label' => 'Noviembre 2026',
                'hypes' => 50,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'anticipated' => [],
        ]));

        RouteTestApp::boot([
            'alv.releases.warm-key' => 'releases-test-key',
            'alv.releases.fixture-file' => self::$fixtureFile,
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        @unlink(self::$fixtureFile);
        RouteTestApp::shutdown();
    }

    public function testWarmRejectsWrongKey(): void
    {
        $result = RouteTestApp::call('lanzamientos-warm', ['key' => 'nope'], 'POST');

        $this->assertSame(['error' => 'unauthorized'], $result);
    }

    public function testWarmReturnsCountsWithCorrectKey(): void
    {
        $result = RouteTestApp::call('lanzamientos-warm', ['key' => 'releases-test-key'], 'POST');

        $this->assertSame('ok', $result['status']);
        $this->assertSame(1, $result['counts']['recentlyReleased']);
        $this->assertSame(1, $result['counts']['upcoming']);
    }

    public function testReleasesSiteMethodUsesFixture(): void
    {
        $games = RouteTestApp::app()->site()->releases()->getNotableUpcoming(10);

        $this->assertCount(1, $games);
        $this->assertSame('Warm Upcoming', $games[0]['name']);
    }
}
