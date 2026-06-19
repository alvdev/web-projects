<?php
/**
 * Seed/Remove dummy content for testing.
 * Usage:
 *   php scripts/seed.php          # create content
 *   php scripts/seed.php --clean  # remove created content
 */

$root = dirname(__DIR__);
$marker = '.seed-generated';

// Fun color pairs per game for visual variety
$palettes = [
    ['#ff0066', '#ff6600'], ['#0066ff', '#00ffcc'], ['#6600ff', '#ff00aa'],
    ['#ffaa00', '#ff4400'], ['#00cc44', '#006633'], ['#ff00aa', '#6600ff'],
    ['#00ffff', '#0066ff'], ['#ff4400', '#cc0000'], ['#39ff14', '#006600'],
    ['#cc00ff', '#330066'], ['#ff6600', '#ffcc00'], ['#00ffcc', '#006666'],
];

function placeholder($title, $colors, $size = [1200, 630]): string {
    [$w, $h] = $size;
    [$c1, $c2] = $colors;
    $lines = explode("\n", wordwrap($title, 30, "\n"));
    $y = $h / 2 - (count($lines) * 20);
    $text = '';
    foreach ($lines as $i => $line) {
        $ty = $y + $i * 40;
        $text .= "<text x='$w/2' y='$ty' font-family='system-ui,sans-serif' font-size='28' font-weight='700' fill='#fff' text-anchor='middle' dominant-baseline='middle'>" . htmlspecialchars($line) . "</text>\n";
    }
    return "<svg xmlns='http://www.w3.org/2000/svg' width='$w' height='$h'>
  <defs><linearGradient id='g' x1='0%' y1='0%' x2='100%' y2='100%'>
    <stop offset='0%' stop-color='$c1'/><stop offset='100%' stop-color='$c2'/>
  </linearGradient></defs>
  <rect width='$w' height='$h' fill='url(#g)'/>
  <text x='$w/2' y='60' font-family='system-ui,sans-serif' font-size='14' fill='rgba(255,255,255,0.6)' text-anchor='middle'>diario.games</text>
  $text</svg>";
}

if (($argv[1] ?? '') === '--clean') {
    $paths = file("$root/content/$marker", FILE_IGNORE_NEW_LINES);
    foreach (array_reverse($paths) as $path) {
        $full = "$root/content/$path";
        if (is_file($full)) { unlink($full); }
        elseif (is_dir($full)) { array_map('unlink', glob("$full/*")); rmdir($full); }
    }
    // remove orphan dirs (txt file tracked separately after the svg)
    $seen = [];
    foreach ($paths as $path) {
        $dir = dirname($path);
        if ($dir !== '.' && !in_array($dir, $seen) && is_dir("$root/content/$dir")) {
            $remaining = glob("$root/content/$dir/*");
            if (empty($remaining)) { rmdir("$root/content/$dir"); }
            $seen[] = $dir;
        }
    }
    unlink("$root/content/$marker");
    echo "Done. All seed content removed.\n";
    exit;
}

// ----- Game definitions -----
$games = [
    [
        'slug' => '02-cyberpunk-2077',
        'title' => 'Cyberpunk 2077',
        'summary' => 'An open-world, action-adventure story set in Night City, a megalopolis obsessed with power, glamour and body modification.',
        'release_date' => '2020-12-10',
        'developer' => 'CD Projekt Red',
        'publisher' => 'CD Projekt',
        'genres' => ['rpg', 'mundo-abierto', 'accion'],
        'featured' => 'true',
        'posts' => [
            ['slug' => '01-phantom-liberty-review', 'title' => 'Phantom Liberty Review', 'date' => '2024-09-26', 'author' => 'Alex Rivera', 'summary' => 'CD Projekt Red delivers a spy-thriller expansion that reinvigorates Night City.', 'text' => "Phantom Liberty is everything we hoped for and more. The spy-thriller narrative fits Cyberpunk's world like a glove, bringing a level of polish and storytelling that surpasses the base game.\n\nThe new district of Dogtown is dense, vertical, and packed with secrets. Idris Elba's performance as Solomon Reed is a standout. The branching narrative paths give real weight to player choices.\n\n**Verdict:** A must-play expansion that sets a new standard for post-launch support."],
        ],
    ],
    [
        'slug' => '03-baldurs-gate-3',
        'title' => "Baldur's Gate 3",
        'summary' => 'Gather your party and return to the Forgotten Realms in this tale of friendship and betrayal, sacrifice and survival, and the lure of absolute power.',
        'release_date' => '2023-08-03',
        'developer' => 'Larian Studios',
        'publisher' => 'Larian Studios',
        'genres' => ['rpg', 'estrategia', 'aventura'],
        'featured' => 'true',
        'posts' => [
            ['slug' => '01-year-later-retrospective', 'title' => 'One Year Later: Still the GOAT', 'date' => '2024-08-03', 'author' => 'Sarah Chen', 'summary' => 'A year on, Baldur\'s Gate 3 remains unmatched in player freedom and narrative depth.', 'text' => "Twelve months after its full release, Baldur's Gate 3 continues to define what CRPGs can be. The sheer density of player choices, the reactivity of the world, and the quality of the writing set a bar that few games can reach.\n\nWith the recent modding tools and promised cross-play, Larian shows no signs of slowing down. The community continues to discover new interactions and endings.\n\n**Verdict:** An enduring masterpiece that only gets better with age."],
        ],
    ],
    [
        'slug' => '04-resident-evil-4',
        'title' => 'Resident Evil 4 Remake',
        'summary' => 'Survive the nightmare in this reimagining of the classic survival horror masterpiece.',
        'release_date' => '2023-03-24',
        'developer' => 'Capcom',
        'publisher' => 'Capcom',
        'genres' => ['terror', 'accion'],
        'featured' => 'false',
        'posts' => [
            ['slug' => '01-separate-ways-review', 'title' => 'Separate Ways DLC Review', 'date' => '2023-09-21', 'author' => 'Alex Rivera', 'summary' => 'Ada Wong\'s campaign is more than a simple add-on—it\'s essential Resident Evil.', 'text' => "Capcom's Separate Ways rounds out the Resident Evil 4 Remake experience. Playing as Ada Wong offers a fresh perspective on the main campaign's events, with unique mechanics and encounters.\n\nThe mercenaries mode is as addictive as ever, and the gameplay refinements make this the definitive way to experience RE4.\n\n**Verdict:** An excellent companion piece that justifies its existence."],
        ],
    ],
    [
        'slug' => '05-zelda-tears-of-the-kingdom',
        'title' => 'The Legend of Zelda: Tears of the Kingdom',
        'summary' => 'An epic adventure that redefines open-world gaming through unprecedented freedom and creativity.',
        'release_date' => '2023-05-12',
        'developer' => 'Nintendo EPD',
        'publisher' => 'Nintendo',
        'genres' => ['aventura', 'mundo-abierto', 'puzzle'],
        'featured' => 'true',
        'posts' => [
            ['slug' => '01-building-guide', 'title' => 'The Art of Ultrahand: Building Guide', 'date' => '2023-06-15', 'author' => 'Marcus Kim', 'summary' => 'Master the new building mechanics with these essential tips and creative builds.', 'text' => "Tears of the Kingdom's Ultrahand ability is the most transformative mechanic Nintendo has ever introduced. From simple bridges to complex flying machines, the only limit is your imagination.\n\nThis guide covers basic principles, advanced techniques, and showcases community creations that push the physics engine to its limits.\n\n**Verdict:** The building system alone is worth the price of admission."],
        ],
    ],
    [
        'slug' => '06-marvel-snap',
        'title' => 'Marvel Snap',
        'summary' => 'A fast-paced, strategic card game featuring iconic Marvel characters with stunning art and innovative mechanics.',
        'release_date' => '2022-10-18',
        'developer' => 'Second Dinner',
        'publisher' => 'Nuverse',
        'genres' => ['estrategia', 'multijugador'],
        'featured' => 'false',
        'posts' => [],
    ],
    [
        'slug' => '07-cities-skylines-2',
        'title' => 'Cities: Skylines II',
        'summary' => 'Build the city of your dreams from the ground up in the most detailed city builder ever made.',
        'release_date' => '2023-10-24',
        'developer' => 'Colossal Order',
        'publisher' => 'Paradox Interactive',
        'genres' => ['simulacion', 'estrategia'],
        'featured' => 'false',
        'posts' => [],
    ],
    [
        'slug' => '08-fc-25',
        'title' => 'EA Sports FC 25',
        'summary' => 'The world\'s game. Experience the most authentic football simulation with new gameplay innovations.',
        'release_date' => '2025-09-26',
        'developer' => 'EA Vancouver',
        'publisher' => 'Electronic Arts',
        'genres' => ['deportes'],
        'featured' => 'false',
        'posts' => [],
    ],
    [
        'slug' => '09-forza-motorsport',
        'title' => 'Forza Motorsport',
        'summary' => 'Race the world in the most comprehensive racing simulation with over 500 cars and 20 tracks.',
        'release_date' => '2023-10-10',
        'developer' => 'Turn 10 Studios',
        'publisher' => 'Xbox Game Studios',
        'genres' => ['deportes', 'simulacion'],
        'featured' => 'false',
        'posts' => [],
    ],
    [
        'slug' => '10-dead-space',
        'title' => 'Dead Space Remake',
        'summary' => 'A masterful reimagining of the sci-fi survival horror classic. Isaac Clarke faces the nightmare on the USG Ishimura once again.',
        'release_date' => '2023-01-27',
        'developer' => 'Motive Studio',
        'publisher' => 'Electronic Arts',
        'genres' => ['terror', 'shooter', 'aventura'],
        'featured' => 'false',
        'posts' => [],
    ],
    [
        'slug' => '11-sea-of-thieves',
        'title' => 'Sea of Thieves',
        'summary' => 'A shared-world adventure game where pirates sail the seas in search of treasure, glory, and epic encounters.',
        'release_date' => '2018-03-20',
        'developer' => 'Rare',
        'publisher' => 'Xbox Game Studios',
        'genres' => ['aventura', 'multijugador', 'mundo-abierto'],
        'featured' => 'false',
        'posts' => [],
    ],
    [
        'slug' => '12-alan-wake-2',
        'title' => 'Alan Wake 2',
        'summary' => 'A survival horror masterpiece that blurs the line between reality and fiction. Saga Anderson investigates murders in Cauldron Lake.',
        'release_date' => '2023-10-27',
        'developer' => 'Remedy Entertainment',
        'publisher' => 'Epic Games Publishing',
        'genres' => ['terror', 'aventura', 'supervivencia'],
        'featured' => 'false',
        'posts' => [
            ['slug' => '01-night-springs-dlc', 'title' => 'Night Springs DLC Impressions', 'date' => '2024-06-08', 'author' => 'Sarah Chen', 'summary' => 'Remedy\'s surreal DLC episodes are a love letter to Twilight Zone fans.', 'text' => "Night Springs takes Alan Wake 2's weirdness and amplifies it. Each episode channels a different genre-parody while expanding the game's lore in unexpected ways.\n\nControl fans will love the connections, and the live-action segments are some of the most creative storytelling in gaming.\n\n**Verdict:** Weird, wonderful, and essential for Remedy-verse fans."],
        ],
    ],
];

// ----- Create content -----
$created = [];

foreach ($games as $i => $game) {
    $palette = $palettes[$i % count($palettes)];
    $gameDir = "games/{$game['slug']}";
    $fullPath = "$root/content/$gameDir";
    mkdir($fullPath, 0755, true);
    $created[] = $gameDir;

    // game.txt
    $genresStr = implode(', ', $game['genres']);
    file_put_contents("$fullPath/game.txt", <<<TXT
Title: {$game['title']}

----

Summary: {$game['summary']}

----

Release date: {$game['release_date']}

----

Developer: {$game['developer']}

----

Publisher: {$game['publisher']}

----

Genres: {$genresStr}

----

Featured: {$game['featured']}

----
TXT
    );

    // cover image placeholder
    $coverFile = "cover.svg";
    file_put_contents("$fullPath/$coverFile", placeholder($game['title'], $palette));
    file_put_contents("$fullPath/cover.svg.txt", "Title: Cover\n\n----\n\nTemplate: cover\n\n----\n");
    $created[] = "$gameDir/$coverFile";
    $created[] = "$gameDir/cover.svg.txt";
    echo "  created game: {$game['title']}\n";

    // posts
    foreach ($game['posts'] as $j => $post) {
        $postPalette = $palettes[($i + $j + 3) % count($palettes)];
        $postDir = "$gameDir/{$post['slug']}";
        $postFull = "$root/content/$postDir";
        mkdir($postFull, 0755, true);
        $created[] = $postDir;

        file_put_contents("$postFull/post.txt", <<<TXT
Title: {$post['title']}

----

Summary: {$post['summary']}

----

Text: {$post['text']}

----

Date: {$post['date']}

----

Author: {$post['author']}

----
TXT
        );

        // header image placeholder
        $headerFile = "header.svg";
        file_put_contents("$postFull/$headerFile", placeholder($post['title'], $postPalette));
        file_put_contents("$postFull/header.svg.txt", "Title: Header\n\n----\n\nTemplate: header\n\n----\n");
        $created[] = "$postDir/$headerFile";
        $created[] = "$postDir/header.svg.txt";
        echo "    created post: {$post['title']}\n";
    }
}

file_put_contents("$root/content/$marker", implode("\n", $created));
echo "\nDone. Created " . count($games) . " games and " . (count($created) - count($games) - array_sum(array_map(fn($g) => count($g['posts']), $games))) . " posts with image placeholders.\n";
echo "Run 'php scripts/seed.php --clean' to remove all seed content.\n";
