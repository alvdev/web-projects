<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\FakeIGDBClient;
use Tests\Support\PluginClasses;

final class AllowedPlatformIdsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testFiltersPlatformsByKeyword(): void
    {
        $client = new FakeIGDBClient();
        $client->platforms = [
            ['id' => 6, 'name' => 'PC (Microsoft Windows)'],
            ['id' => 167, 'name' => 'PlayStation 5'],
            ['id' => 169, 'name' => 'Xbox Series X|S'],
            ['id' => 130, 'name' => 'Nintendo Switch'],
            ['id' => 34, 'name' => 'Android'],
            ['id' => 39, 'name' => 'iOS'],
            ['id' => 82, 'name' => 'Google Stadia'],
        ];

        $this->assertSame([6, 167, 169, 130, 34], \DiarioGames\IGDB\allowedPlatformIds($client));
    }

    public function testReturnsEmptyArrayWhenNothingMatches(): void
    {
        $client = new FakeIGDBClient();
        $client->platforms = [['id' => 82, 'name' => 'Google Stadia']];

        $this->assertSame([], \DiarioGames\IGDB\allowedPlatformIds($client));
    }
}
