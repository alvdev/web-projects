<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Tests\Support\RouteTestApp;

final class HomePagePostsTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        RouteTestApp::boot();

        mkdir(RouteTestApp::content('home'), 0775, true);
        file_put_contents(
            RouteTestApp::content('home/home.txt'),
            "Title: Inicio\n\n----\n\nTemplate: home\n"
        );

        $gameDir = RouteTestApp::content('games/2024/03/alpha-quest');
        mkdir($gameDir, 0775, true);
        file_put_contents(
            $gameDir . '/game.txt',
            "Title: Alpha Quest\n\n----\n\nTemplate: game\n\n----\n\nGenres: Acción\n"
        );

        $posts = [
            'news-six'   => ['template' => 'news',  'date' => '2024-03-06', 'title' => 'News Six'],
            'guide-five' => ['template' => 'guide', 'date' => '2024-03-05', 'title' => 'Guide Five'],
            'news-four'  => ['template' => 'news',  'date' => '2024-03-04', 'title' => 'News Four'],
            'guide-three' => ['template' => 'guide', 'date' => '2024-03-03', 'title' => 'Guide Three'],
            'news-two'   => ['template' => 'news',  'date' => '2024-03-02', 'title' => 'News Two'],
            'guide-one'  => ['template' => 'guide', 'date' => '2024-03-01', 'title' => 'Guide One'],
        ];

        foreach ($posts as $slug => $data) {
            $dir = $gameDir . '/' . $slug;
            mkdir($dir, 0775, true);
            file_put_contents(
                $dir . '/' . $data['template'] . '.txt',
                "Title: {$data['title']}\n\n----\n\nTemplate: {$data['template']}\n\n----\n\nDate: {$data['date']}\n"
            );
        }
    }

    public static function tearDownAfterClass(): void
    {
        RouteTestApp::shutdown();
    }

    private function renderHome(): string
    {
        return RouteTestApp::app()->page('home')->render();
    }

    private function segment(string $html, string $start, string $end): string
    {
        $startPos = strpos($html, $start);
        $this->assertNotFalse($startPos, "Marker {$start} not found");

        $endPos = strpos($html, $end, $startPos);
        $this->assertNotFalse($endPos, "Marker {$end} not found after {$start}");

        return substr($html, $startPos, $endPos - $startPos);
    }

    public function testHomeRendersRepeatedlyInOneProcess(): void
    {
        $first = RouteTestApp::app()->page('home')->render();
        $second = RouteTestApp::app()->page('home')->render();

        $this->assertStringContainsString('data-home-charts-row', $first);
        $this->assertStringContainsString('data-home-charts-row', $second);
    }

    public function testSteamChartsRenderBeforeTheSpotlight(): void
    {
        $html = $this->renderHome();

        $charts = strpos($html, 'data-tab="most-played"');
        $spotlight = strpos($html, 'data-spotlight');

        $this->assertNotFalse($charts, 'Steam charts tabs missing from homepage');
        $this->assertNotFalse($spotlight, 'Spotlight missing from homepage');
        $this->assertLessThan($spotlight, $charts, 'Steam charts should render above the spotlight');
    }

    public function testTwitchWidgetTakesTheChartsRowRightHalf(): void
    {
        $html = $this->renderHome();

        $rowOne = $this->segment($html, 'data-home-charts-row', 'data-home-posts-row');

        $this->assertStringContainsString('data-twitch-widget', $rowOne);
        $this->assertStringContainsString('data-twitch-tab="games"', $rowOne);
    }

    public function testSpotlightShowsTheFiveNewestPostsInOrder(): void
    {
        $html = $this->renderHome();
        $spotlight = $this->segment($html, 'data-spotlight', 'data-home-ticker');

        $this->assertSame(5, substr_count($spotlight, 'data-spotlight-card'));

        $positions = [];
        foreach (['News Six', 'Guide Five', 'News Four', 'Guide Three', 'News Two'] as $title) {
            $pos = strpos($spotlight, $title);
            $this->assertNotFalse($pos, "Spotlight missing post: {$title}");
            $positions[] = $pos;
        }

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Spotlight posts are not ordered newest first');

        $this->assertStringNotContainsString('Guide One', $spotlight, 'Spotlight should be limited to 5 posts');
    }

    public function testExactlyTheFirstSpotlightCardIsVisibleByDefault(): void
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($this->renderHome());
        $xpath = new \DOMXPath($dom);

        $visible = $xpath->query(
            '//*[@data-spotlight-card and not(contains(concat(" ", normalize-space(@class), " "), " hidden "))]'
        );

        $this->assertSame(1, $visible->length, 'Exactly one spotlight card should be visible');
        $card = $visible->item(0);
        $this->assertInstanceOf(\DOMElement::class, $card);
        $this->assertSame('0', $card->getAttribute('data-spotlight-card'));
    }

    public function testSpotlightCardsLinkToTheirPosts(): void
    {
        $html = $this->renderHome();
        $spotlight = $this->segment($html, 'data-spotlight', 'data-home-ticker');

        $this->assertMatchesRegularExpression('~href="[^"]*/news-six"~', $spotlight);
        $this->assertMatchesRegularExpression('~href="[^"]*/guide-five"~', $spotlight);
    }

    public function testTickerShowsCategoryAndTitleForEachPost(): void
    {
        $html = $this->renderHome();
        $ticker = substr($html, (int) strpos($html, 'data-home-ticker'));

        $this->assertSame(5, substr_count($ticker, 'data-ticker-card="'));

        $positions = [];
        foreach (['News Six', 'Guide Five', 'News Four', 'Guide Three', 'News Two'] as $title) {
            $pos = strpos($ticker, $title);
            $this->assertNotFalse($pos, "Ticker missing post: {$title}");
            $positions[] = $pos;
        }

        $sorted = $positions;
        sort($sorted);
        $this->assertSame($sorted, $positions, 'Ticker posts are not ordered newest first');

        $this->assertStringContainsString('Noticia', $ticker);
        $this->assertStringContainsString('Guía', $ticker);
        $this->assertStringNotContainsString('Guide One', $ticker);
    }

    public function testTickerFirstCardIsSelectedByDefault(): void
    {
        $html = $this->renderHome();
        $ticker = substr($html, (int) strpos($html, 'data-home-ticker'));

        $this->assertStringContainsString('data-ticker-card="0"', $ticker);
        $this->assertStringContainsString('data-active', $ticker);
    }
}
