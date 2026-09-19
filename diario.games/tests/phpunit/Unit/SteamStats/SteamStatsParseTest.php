<?php

declare(strict_types=1);

namespace Tests\Unit\SteamStats;

use Alv\SteamStats\SteamStats;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class SteamStatsParseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    private function statsHtmlFixture(): string
    {
        return <<<'HTML'
<table>
<tr class="player_count_row">
    <td class="right"><span>1,234,567</span></td>
    <td class="right"><span>2,000,000</span></td>
    <td>Current</td>
    <td><a href="https://store.steampowered.com/app/730/">Counter-Strike &amp; Friends</a></td>
</tr>
<tr class="player_count_row">
    <td><span>42,000</span></td>
    <td><span>50,000</span></td>
    <td>Current</td>
    <td><a href="https://store.steampowered.com/app/570/?snr=1">Dota 2</a></td>
</tr>
</table>
HTML;
    }

    public function testParseStatsHtmlExtractsGames(): void
    {
        $games = SteamStats::parseStatsHtml($this->statsHtmlFixture());

        $this->assertSame([
            ['appid' => 730, 'name' => 'Counter-Strike & Friends', 'current_players' => 1234567, 'peak_today' => 2000000],
            ['appid' => 570, 'name' => 'Dota 2', 'current_players' => 42000, 'peak_today' => 50000],
        ], $games);
    }

    public function testParseStatsHtmlReturnsEmptyForUnrelatedHtml(): void
    {
        $this->assertSame([], SteamStats::parseStatsHtml('<html><body>nope</body></html>'));
    }

    public function testParseMostPlayedJsonReturnsRanks(): void
    {
        $ranks = [['rank' => 1, 'appid' => 570, 'peak_in_game' => 800000]];

        $this->assertSame($ranks, SteamStats::parseMostPlayedJson(['response' => ['ranks' => $ranks]]));
        $this->assertSame([], SteamStats::parseMostPlayedJson([]));
    }
}
