<?php

declare(strict_types=1);

namespace Tests\Unit\Igdb;

use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

use function DiarioGames\IGDB\deriveYearMonth;
use function DiarioGames\IGDB\igdbImageUrl;
use function DiarioGames\IGDB\normalizePlatformNames;
use function DiarioGames\IGDB\platformCategory;
use function DiarioGames\IGDB\romanToDigits;
use function DiarioGames\IGDB\slugify;

final class HelpersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testSlugifyBuildsUrlSafeSlug(): void
    {
        $this->assertSame('the-legend-of-zelda-breath-of-the-wild', slugify('The Legend of Zelda: Breath of the Wild'));
        $this->assertSame('half-life-2', slugify('Half-Life 2'));
        $this->assertSame('foo', slugify('  Foo!!!  '));
        $this->assertSame('', slugify('   '));
    }

    public function testRomanToDigitsConvertsStandaloneNumerals(): void
    {
        $this->assertSame('final-fantasy-16', romanToDigits('final-fantasy-xvi'));
        $this->assertSame('grand-theft-auto-5', romanToDigits('grand-theft-auto-v'));
        $this->assertSame('civilization-6', romanToDigits('civilization-vi'));
        $this->assertSame('kingdom-hearts-3', romanToDigits('kingdom-hearts-iii'));
        $this->assertSame('halo-3', romanToDigits('halo-3'));
        $this->assertSame('doom', romanToDigits('doom'));
    }

    public function testDeriveYearMonth(): void
    {
        $this->assertSame(['2024', '03'], deriveYearMonth('2024-03-15'));
        $this->assertSame(['2024', '00'], deriveYearMonth('2024'));
        $this->assertSame(['2024', '00'], deriveYearMonth('2024-3'));
        $this->assertSame(['00', '00'], deriveYearMonth('TBA'));
        $this->assertSame(['00', '00'], deriveYearMonth(''));
    }

    public function testIgdbImageUrl(): void
    {
        $this->assertSame(
            'https://images.igdb.com/igdb/image/upload/t_cover_big/abc123.jpg',
            igdbImageUrl('abc123')
        );
        $this->assertSame(
            'https://images.igdb.com/igdb/image/upload/t_screenshot_huge/abc123.jpg',
            igdbImageUrl('abc123', 'screenshot_huge')
        );
    }

    public function testNormalizePlatformNamesGroupsAndOrders(): void
    {
        $this->assertSame(
            'PC, PS 4, Xbox One',
            normalizePlatformNames('PC (Microsoft Windows), PlayStation 4, Xbox One')
        );
        $this->assertSame(
            'PS 4|5|Vita',
            normalizePlatformNames('PlayStation 4, PlayStation 5, PlayStation Vita')
        );
        $this->assertSame('Switch', normalizePlatformNames('Nintendo Switch'));
        $this->assertSame(
            'Switch 1|2',
            normalizePlatformNames('Nintendo Switch, Nintendo Switch 2')
        );
        $this->assertSame('Xbox X|S|One', normalizePlatformNames('Xbox (X|S, One)'));
        $this->assertSame('Xbox X|S', normalizePlatformNames('Xbox Series X|S'));
        $this->assertSame(
            'PC, PS 5, Android',
            normalizePlatformNames('PC (Microsoft Windows), PlayStation 5, Android')
        );
        $this->assertSame(
            'PC',
            normalizePlatformNames('Legacy Mobile Device, PC (Microsoft Windows)')
        );
        $this->assertSame('', normalizePlatformNames('   '));
    }

    public function testPlatformCategoryBuckets(): void
    {
        $this->assertSame(0, platformCategory('PC'));
        $this->assertSame(0, platformCategory('linux'));
        $this->assertSame(0, platformCategory('Mac'));
        $this->assertSame(1, platformCategory('PlayStation 5'));
        $this->assertSame(1, platformCategory('Switch'));
        $this->assertSame(2, platformCategory('Android'));
        $this->assertSame(2, platformCategory('iOS'));
    }
}
