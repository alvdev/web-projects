<?php

declare(strict_types=1);

namespace Tests\Integration;

use Kirby\Http\Response;
use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class ImportRoutesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testImportGameRequiresSlug(): void
    {
        $result = RouteTestApp::call('steam-stats-api/import-game');

        $this->assertSame(['error' => 'slug required'], $result);
    }

    public function testImportGameWithoutIgdbCredentialsFailsBeforeSpawning(): void
    {
        $result = RouteTestApp::call('steam-stats-api/import-game', ['slug' => 'alpha-quest']);

        $this->assertArrayHasKey('id', $result);
        $this->assertSame('igdb_credentials', $result['error']);

        $progress = RouteTestApp::app()->cache('alv/steam-stats.cache')
            ->get('import-progress.' . $result['id']);

        $this->assertTrue($progress['error']);
        $this->assertStringContainsString('IGDB', $progress['text']);
    }

    public function testImportProgressUnknownId(): void
    {
        $result = RouteTestApp::call('steam-stats-api/import-progress/unknown-id');

        $this->assertSame(['phase' => 'unknown', 'text' => 'Esperando...'], $result);
    }

    public function testImportProgressReturnsCachedState(): void
    {
        RouteTestApp::app()->cache('alv/steam-stats.cache')->set('import-progress.test-id', [
            'phase' => 'metadata',
            'text' => 'Obteniendo información...',
        ]);

        $result = RouteTestApp::call('steam-stats-api/import-progress/test-id');

        $this->assertSame('metadata', $result['phase']);
        $this->assertSame('Obteniendo información...', $result['text']);
    }

    public function testSteamCapsuleMediaReturns404WhenMissing(): void
    {
        $result = RouteTestApp::call('media/steam-capsule/unknown-game.jpg');

        $this->assertInstanceOf(Response::class, $result);
        $this->assertSame(404, $result->code());
    }
}
