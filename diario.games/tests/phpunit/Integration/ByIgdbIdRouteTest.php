<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class ByIgdbIdRouteTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    public function testByIgdbIdRouteIsRegisteredBeforeCatchAll(): void
    {
        $patterns = [];
        foreach (RouteTestApp::app()->routes() as $route) {
            $patterns[] = $route['pattern'];
        }

        $byIgdbId = array_search('games/by-igdb-id/(:num)', $patterns, true);
        $catchAll = array_search('(:any)', $patterns, true);

        $this->assertNotFalse($byIgdbId, 'by-igdb-id route is not registered');
        $this->assertNotFalse($catchAll, 'catch-all route is not registered');
        $this->assertLessThan($catchAll, $byIgdbId, 'by-igdb-id route must be registered before the catch-all');
    }
}
