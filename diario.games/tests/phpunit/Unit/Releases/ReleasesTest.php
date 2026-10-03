<?php

declare(strict_types=1);

namespace Tests\Unit\Releases;

use Alv\Releases\Releases;
use Alv\SteamStats\SteamStatsDB;
use PHPUnit\Framework\TestCase;
use Tests\Support\Files;
use Tests\Support\PluginClasses;
use Tests\Support\RecordingReleasesClient;
use Tests\Support\TempDatabase;

final class ReleasesTest extends TestCase
{
    use TempDatabase;

    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    protected function setUp(): void
    {
        $this->setUpTempDatabase();
    }

    protected function tearDown(): void
    {
        Files::removeDir($this->tempDatabaseDir);
        $this->tearDownTempDatabase();
    }

    private function game(array $overrides = []): array
    {
        return array_merge([
            'id' => 100,
            'name' => 'Upcoming One',
            'slug' => 'upcoming-one',
            'first_release_date' => gmmktime(0, 0, 0, 10, 15, 2026),
            'hypes' => 120,
            'cover' => ['id' => 5, 'image_id' => 'cover_abc'],
            'platforms' => [['abbreviation' => 'PC'], ['abbreviation' => 'PS5']],
            'genres' => [['name' => 'RPG']],
            'release_dates' => [['human' => 'Oct 15, 2026']],
            'category' => null,
        ], $overrides);
    }

    private function client(array $upcoming = [], array $anticipated = [], array $recentlyReleased = []): RecordingReleasesClient
    {
        return new RecordingReleasesClient($upcoming, $anticipated, $recentlyReleased);
    }

    public function testUpcomingGamesAreNormalized(): void
    {
        $service = new Releases([], $this->client([
            $this->game(['id' => 1, 'name' => 'First', 'slug' => 'first']),
            $this->game([
                'id' => 2,
                'name' => 'Second',
                'slug' => 'second',
                'first_release_date' => gmmktime(0, 0, 0, 11, 2, 2026),
            ]),
        ]));

        $games = $service->getNotableUpcoming(6);

        $this->assertCount(2, $games);
        $this->assertSame('First', $games[0]['name']);
        $this->assertSame('15 de octubre de 2026', $games[0]['display_date']);
        $this->assertSame('2026-10-15', $games[0]['release_date']);
        $this->assertSame('Octubre 2026', $games[0]['month_label']);
        $this->assertSame('https://images.igdb.com/igdb/image/upload/t_cover_big/cover_abc.jpg', $games[0]['cover_url']);
        $this->assertSame(['PC', 'PS5'], $games[0]['platforms']);
        $this->assertSame(['RPG'], $games[0]['genres']);
        $this->assertNull($games[0]['local_url']);
    }

    public function testNotableUpcomingFiltersByHypesAndExcludesAnticipated(): void
    {
        $service = new Releases([], $this->client(
            [
                $this->game(['id' => 1, 'name' => 'Unknown', 'slug' => 'unknown', 'hypes' => 5]),
                $this->game(['id' => 2, 'name' => 'Known', 'slug' => 'known', 'hypes' => 20]),
                $this->game(['id' => 3, 'name' => 'Hyped', 'slug' => 'hyped', 'hypes' => 500]),
            ],
            [
                $this->game(['id' => 3, 'name' => 'Hyped', 'slug' => 'hyped', 'hypes' => 500]),
            ]
        ));

        $games = $service->getNotableUpcoming(10);

        $this->assertCount(1, $games);
        $this->assertSame('Known', $games[0]['name']);
    }

    public function testNotableUpcomingRespectsLimit(): void
    {
        $service = new Releases([], $this->client([
            $this->game(['id' => 1, 'name' => 'A', 'slug' => 'a']),
            $this->game(['id' => 2, 'name' => 'B', 'slug' => 'b']),
            $this->game(['id' => 3, 'name' => 'C', 'slug' => 'c']),
        ]));

        $this->assertCount(2, $service->getNotableUpcoming(2));
    }

    public function testRecentlyReleasedIsReturned(): void
    {
        $service = new Releases([], $this->client(
            [],
            [],
            [
                $this->game(['id' => 10, 'name' => 'Wolverine', 'slug' => 'wolverine', 'hypes' => 292]),
                $this->game(['id' => 11, 'name' => 'Dawnwalker', 'slug' => 'dawnwalker', 'hypes' => 272]),
            ]
        ));

        $games = $service->getRecentlyReleased(1);

        $this->assertCount(1, $games);
        $this->assertSame('Wolverine', $games[0]['name']);
    }

    public function testAnticipatedRespectsLimit(): void
    {
        $service = new Releases([], $this->client(
            [],
            [
                $this->game(['id' => 20, 'name' => 'GTA VI', 'slug' => 'gta-vi', 'hypes' => 1071]),
                $this->game(['id' => 21, 'name' => 'Fable', 'slug' => 'fable', 'hypes' => 423]),
            ]
        ));

        $this->assertCount(1, $service->getAnticipated(1));
        $this->assertSame('GTA VI', $service->getAnticipated(10)[0]['name']);
    }

    public function testFuzzyQuarterDateUsesHumanLabel(): void
    {
        $service = new Releases([], $this->client([
            $this->game([
                'id' => 3,
                'name' => 'Fuzzy',
                'slug' => 'fuzzy',
                'first_release_date' => gmmktime(0, 0, 0, 1, 1, 2027),
                'release_dates' => [['human' => 'Q1 2027']],
            ]),
        ]));

        $game = $service->getNotableUpcoming(1)[0];

        $this->assertSame('Q1 2027', $game['display_date']);
        $this->assertNull($game['release_date']);
        $this->assertSame('2027-q1', $game['month_key']);
        $this->assertSame('Q1 2027', $game['month_label']);
    }

    public function testYearOnlyDateUsesHumanLabel(): void
    {
        $service = new Releases([], $this->client([
            $this->game([
                'id' => 4,
                'name' => 'Yearly',
                'slug' => 'yearly',
                'first_release_date' => gmmktime(0, 0, 0, 1, 1, 2027),
                'release_dates' => [['human' => '2027']],
            ]),
        ]));

        $game = $service->getNotableUpcoming(1)[0];

        $this->assertSame('2027', $game['display_date']);
        $this->assertSame('2027', $game['month_key']);
        $this->assertSame('2027', $game['month_label']);
    }

    public function testExcludedGamesAreDropped(): void
    {
        $service = new Releases([], $this->client([
            $this->game(['id' => 5, 'name' => 'Battle Pass Pack', 'slug' => 'battle-pass']),
            $this->game(['id' => 6, 'name' => 'Keep Me', 'slug' => 'keep-me']),
        ]));

        $games = $service->getNotableUpcoming(6);

        $this->assertCount(1, $games);
        $this->assertSame('Keep Me', $games[0]['name']);
    }

    public function testLocalUrlResolvedForImportedGames(): void
    {
        $db = new SteamStatsDB();
        $db->upsertGame(990001, 'alpha-quest', 'Alpha Quest', 100);

        $service = new Releases([], $this->client([$this->game()]));

        $this->assertSame('/alpha-quest', $service->getNotableUpcoming(1)[0]['local_url']);
    }

    public function testQueryUsesNullAwareCategoryFilter(): void
    {
        $client = $this->client([$this->game()]);
        $service = new Releases([], $client);

        $service->getRecentlyReleased(1);

        $this->assertCount(3, $client->whereClauses);
        foreach ($client->whereClauses as $where) {
            $this->assertStringContainsString('(category = 0 | category = null)', $where);
            $this->assertStringNotContainsString('& category = 0 &', $where);
        }
        $this->assertStringContainsString('hypes >= 20', $client->whereClauses[1]);
    }

    public function testFixtureFileBypassesClient(): void
    {
        $fixtureFile = $this->tempDatabaseDir . '/releases-fixture.json';
        file_put_contents($fixtureFile, json_encode([
            'recentlyReleased' => [[
                'igdb_id' => 41,
                'slug' => 'fixture-recent',
                'name' => 'Fixture Recent',
                'release_date' => '2026-09-20',
                'display_date' => '20 de septiembre de 2026',
                'month_key' => '2026-09',
                'month_label' => 'Septiembre 2026',
                'hypes' => 40,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'upcoming' => [[
                'igdb_id' => 42,
                'slug' => 'fixture-game',
                'name' => 'Fixture Game',
                'release_date' => '2026-12-01',
                'display_date' => '1 de diciembre de 2026',
                'month_key' => '2026-12',
                'month_label' => 'Diciembre 2026',
                'hypes' => 7,
                'cover_url' => null,
                'platforms' => ['PC'],
                'genres' => ['RPG'],
                'local_url' => null,
            ]],
            'anticipated' => [],
        ]));

        $service = new Releases(['fixture_file' => $fixtureFile]);

        $this->assertSame('Fixture Recent', $service->getRecentlyReleased(1)[0]['name']);
        $this->assertSame([], $service->getNotableUpcoming(1));
    }
}
