<?php

declare(strict_types=1);

namespace Tests\Unit\AffBanners;

use Alv\AffBanners\AffiliateBanners;
use PHPUnit\Framework\TestCase;
use Tests\Support\PluginClasses;

final class AffiliateBannersTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        PluginClasses::load();
    }

    public function testIsEnabledHandlesMissingAndStringValues(): void
    {
        $this->assertTrue(AffiliateBanners::isEnabled(null));
        $this->assertTrue(AffiliateBanners::isEnabled(''));
        $this->assertTrue(AffiliateBanners::isEnabled('true'));
        $this->assertTrue(AffiliateBanners::isEnabled('1'));
        $this->assertFalse(AffiliateBanners::isEnabled('false'));
        $this->assertFalse(AffiliateBanners::isEnabled('0'));
        $this->assertFalse(AffiliateBanners::isEnabled(false));
    }

    public function testParseProgramsPipeFrequency(): void
    {
        $programs = AffiliateBanners::parsePrograms([[
            'name' => 'Program A',
            'enabled' => 'true',
            'frequency' => '2|5|9',
            'type' => 'instant-gaming',
            'affiliate_id' => 'aff-a',
        ]]);

        $this->assertCount(1, $programs);
        $this->assertSame('Program A', $programs[0]['name']);
        $this->assertTrue($programs[0]['enabled']);
        $this->assertSame(2, $programs[0]['sm_position']);
        $this->assertSame(5, $programs[0]['md_position']);
        $this->assertSame(9, $programs[0]['xl_position']);
        $this->assertSame('instant-gaming', $programs[0]['type']);
        $this->assertSame('aff-a', $programs[0]['affiliate_id']);
        $this->assertSame('Ofertas destacadas', $programs[0]['banner_label']);
        $this->assertSame('Patrocinado', $programs[0]['banner_sponsor']);
    }

    public function testParseProgramsNumericFrequencyDerivesPositions(): void
    {
        $programs = AffiliateBanners::parsePrograms([['name' => 'Numeric', 'frequency' => 6]]);

        $this->assertSame(1, $programs[0]['sm_position']);
        $this->assertSame(3, $programs[0]['md_position']);
        $this->assertSame(6, $programs[0]['xl_position']);
    }

    public function testParseProgramsStringFrequencyAndClamping(): void
    {
        $programs = AffiliateBanners::parsePrograms([
            ['name' => 'String', 'frequency' => 'sm:3 md:7 xl:11'],
            ['name' => 'Zero', 'frequency' => '0|0|0'],
        ]);

        $this->assertSame(3, $programs[0]['sm_position']);
        $this->assertSame(7, $programs[0]['md_position']);
        $this->assertSame(11, $programs[0]['xl_position']);

        $this->assertSame(1, $programs[1]['sm_position']);
        $this->assertSame(1, $programs[1]['md_position']);
        $this->assertSame(1, $programs[1]['xl_position']);
    }

    public function testParseProgramsDefaultsAndDisabledFlag(): void
    {
        $programs = AffiliateBanners::parsePrograms([
            ['name' => 'Defaults'],
            ['name' => 'Off', 'enabled' => 'false'],
            ['name' => 'ZeroString', 'enabled' => '0'],
        ]);

        $this->assertSame(1, $programs[0]['sm_position']);
        $this->assertSame(2, $programs[0]['md_position']);
        $this->assertSame(4, $programs[0]['xl_position']);
        $this->assertTrue($programs[0]['enabled']);
        $this->assertFalse($programs[1]['enabled']);
        $this->assertFalse($programs[2]['enabled']);
    }

    public function testMatchingProgramsUsesSmThenMdThenXlPrecedence(): void
    {
        $programs = AffiliateBanners::parsePrograms([[
            'name' => 'A',
            'frequency' => '2|2|4',
        ]]);

        $matchSm = AffiliateBanners::matchingPrograms(2, $programs);
        $this->assertCount(1, $matchSm);
        $this->assertSame('sm', $matchSm[0]['_bpType']);

        $matchXl = AffiliateBanners::matchingPrograms(4, $programs);
        $this->assertCount(1, $matchXl);
        $this->assertSame('xl', $matchXl[0]['_bpType']);

        $this->assertSame([], AffiliateBanners::matchingPrograms(3, $programs));
    }

    public function testMatchingProgramsSkipsDisabledAndNonMatching(): void
    {
        $programs = AffiliateBanners::parsePrograms([
            ['name' => 'Off', 'frequency' => '1|2|4', 'enabled' => 'false'],
            ['name' => 'On', 'frequency' => '1|2|4'],
        ]);

        $matches = AffiliateBanners::matchingPrograms(4, $programs);

        $this->assertCount(1, $matches);
        $this->assertSame('On', $matches[0]['name']);
    }
}
