<?php

declare(strict_types=1);

namespace Tests\Unit\TwitchStats;

use Alv\TwitchStats\TwitchClient;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class TwitchClientParseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testParseTopGamesKeepsIgdbIdAndNormalizesBoxArt(): void
    {
        $json = ['data' => [[
            'id' => '509658',
            'name' => 'Just Chatting',
            'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/509658-{width}x{height}.jpg',
            'igdb_id' => '509658',
        ]]];

        $games = TwitchClient::parseTopGames($json);

        $this->assertSame([[
            'id' => '509658',
            'name' => 'Just Chatting',
            'box_art_url' => 'https://static-cdn.jtvnw.net/ttv-boxart/509658-144x192.jpg',
            'igdb_id' => 509658,
        ]], $games);
    }

    public function testParseTopGamesWithoutIgdbIdUsesNull(): void
    {
        $games = TwitchClient::parseTopGames(['data' => [[
            'id' => '509672',
            'name' => 'IRL',
            'box_art_url' => 'https://x/{width}x{height}.jpg',
        ]]]);

        $this->assertNull($games[0]['igdb_id']);
    }

    public function testParseStreamsCastsViewerCountAndKeepsLanguageFields(): void
    {
        $streams = TwitchClient::parseStreams(['data' => [[
            'user_id' => 'u1',
            'user_login' => 'ibai',
            'user_name' => 'Ibai',
            'game_id' => '509658',
            'game_name' => 'Just Chatting',
            'viewer_count' => '44300',
        ]]]);

        $this->assertSame('ibai', $streams[0]['user_login']);
        $this->assertSame(44300, $streams[0]['viewer_count']);
        $this->assertSame('Just Chatting', $streams[0]['game_name']);
    }

    public function testParseAvatarsMapsUserIdToImage(): void
    {
        $map = TwitchClient::parseAvatars(['data' => [
            ['id' => 'u1', 'profile_image_url' => 'https://x/a.jpg'],
            ['id' => 'u2', 'profile_image_url' => 'https://x/b.jpg'],
        ]]);

        $this->assertSame(['u1' => 'https://x/a.jpg', 'u2' => 'https://x/b.jpg'], $map);
    }

    public function testParseHandlesMalformedPayload(): void
    {
        $this->assertSame([], TwitchClient::parseTopGames([]));
        $this->assertSame([], TwitchClient::parseStreams(['data' => 'nope']));
        $this->assertSame([], TwitchClient::parseAvatars(['data' => [['id' => '']]]));
    }

    public function testAggregateByGameSumsViewersAndCountsChannels(): void
    {
        $streams = [
            ['user_id' => 'u1', 'user_login' => 'a', 'user_name' => 'A', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 100],
            ['user_id' => 'u2', 'user_login' => 'b', 'user_name' => 'B', 'game_id' => '1', 'game_name' => 'Game One', 'viewer_count' => 50],
            ['user_id' => 'u3', 'user_login' => 'c', 'user_name' => 'C', 'game_id' => '2', 'game_name' => 'Game Two', 'viewer_count' => 30],
        ];

        $aggregated = TwitchClient::aggregateByGame($streams);

        $this->assertSame(['viewers' => 150, 'channels' => 2], $aggregated['1']);
        $this->assertSame(['viewers' => 30, 'channels' => 1], $aggregated['2']);
    }

    public function testIsConfiguredRequiresBothCredentials(): void
    {
        $this->assertFalse((new TwitchClient('', ''))->isConfigured());
        $this->assertFalse((new TwitchClient('id', ''))->isConfigured());
        $this->assertTrue((new TwitchClient('id', 'secret'))->isConfigured());
    }
}
