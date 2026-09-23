<?php

declare(strict_types=1);

namespace Tests\Unit\TwitchStats;

use Alv\TwitchStats\TwitchTrackerClient;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class TwitchTrackerClientTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testParseSummaryCastsNumericFields(): void
    {
        $summary = TwitchTrackerClient::parseSummary('{"avg_viewers":272891,"avg_channels":4421,"rank":1,"hours_watched":45936718}');

        $this->assertSame(272891, $summary['avg_viewers']);
        $this->assertSame(1, $summary['rank']);
        $this->assertSame(45936718, $summary['hours_watched']);
    }

    public function testParseSummaryReturnsNullForInvalidPayloads(): void
    {
        $this->assertNull(TwitchTrackerClient::parseSummary('<html>challenge</html>'));
        $this->assertNull(TwitchTrackerClient::parseSummary(''));
        $this->assertNull(TwitchTrackerClient::parseSummary('{}'));
        $this->assertNull(TwitchTrackerClient::parseSummary('{"rank":1}'));
    }

    public function testGetGameSummaryUsesInjectedHttp(): void
    {
        $seen = [];
        $client = new TwitchTrackerClient(function (string $url) use (&$seen): string {
            $seen[] = $url;

            return '{"avg_viewers":100,"avg_channels":2,"rank":4,"hours_watched":900}';
        });

        $summary = $client->getGameSummary(509658);

        $this->assertSame('https://twitchtracker.com/api/games/summary/509658', $seen[0]);
        $this->assertSame(100, $summary['avg_viewers']);
    }

    public function testGetChannelSummaryEncodesLoginAndParses(): void
    {
        $seen = [];
        $client = new TwitchTrackerClient(function (string $url) use (&$seen): string {
            $seen[] = $url;

            return '{"rank":25,"minutes_streamed":3694,"avg_viewers":44300,"max_viewers":94305,"hours_watched":2727419,"followers":35315,"followers_total":17064030}';
        });

        $summary = $client->getChannelSummary('some streamer/name');

        $this->assertSame('https://twitchtracker.com/api/channels/summary/some%20streamer%2Fname', $seen[0]);
        $this->assertSame(44300, $summary['avg_viewers']);
        $this->assertSame(17064030, $summary['followers_total']);
    }

    public function testGetChannelSummaryReturnsNullOnTransportFailure(): void
    {
        $client = new TwitchTrackerClient(fn (string $url): ?string => null);

        $this->assertNull($client->getChannelSummary('ibai'));
    }
}
