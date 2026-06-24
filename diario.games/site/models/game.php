<?php

class GamePage extends \Kirby\Cms\Page
{
    public function genres(): string
    {
        return $this->content()->get('genres')->value() ?? '';
    }

    public function genreList(): array
    {
        return array_map('trim', explode(',', $this->genres()));
    }

    public function tags(): string
    {
        return $this->content()->get('tags')->value() ?? '';
    }

    public function tagList(): array
    {
        return array_map('trim', explode(',', $this->tags()));
    }

    public function posts(): \Kirby\Cms\Pages
    {
        return $this->children()->sortBy('date', 'desc');
    }

    public function cover(): ?\Kirby\Cms\File
    {
        return $this->files()->template('cover')->first();
    }

    public function hero(): ?\Kirby\Cms\File
    {
        return $this->files()->template('hero')->first();
    }

    public function screenshotFiles(): \Kirby\Cms\Files
    {
        return $this->files()->template('screenshot');
    }

    public function screenshots(): array
    {
        $raw = $this->content()->get('Screenshots')->value() ?? '';
        if (empty(trim($raw))) return [];
        $ids = array_map('trim', explode(',', $raw));
        $local = $this->screenshotFiles();
        $localUrls = [];
        foreach ($local as $f) {
            preg_match('/screenshot-(\d+)/', $f->filename(), $m);
            if ($m) $localUrls[(int)$m[1]] = $f->url();
        }
        $result = [];
        foreach ($ids as $i => $id) {
            $thumb = $localUrls[$i] ?? 'https://images.igdb.com/igdb/image/upload/t_screenshot_med/' . $id . '.jpg';
            $full  = $localUrls[$i] ?? 'https://images.igdb.com/igdb/image/upload/t_screenshot_huge/' . $id . '.jpg';
            $result[] = ['thumb' => $thumb, 'full' => $full];
        }
        return $result;
    }

    public function gameVideos(): array
    {
        $raw = $this->content()->get('Videos')->value() ?? '';
        if (empty(trim($raw))) return [];
        return array_map(function ($id) {
            $id = trim($id);
            return 'https://www.youtube.com/embed/' . $id;
        }, explode(',', $raw));
    }

    public function videoThumbs(): \Kirby\Cms\Files
    {
        return $this->files()->template('video-thumb');
    }

    public function websites(): array
    {
        $raw = $this->content()->get('Websites')->value() ?? '';
        if (empty(trim($raw))) return [];

        $domainIcons = [
            'store.steampowered.com' => ['label' => 'Steam', 'icon' => 'steam'],
            'store.epicgames.com' => ['label' => 'Epic Games', 'icon' => 'epicgames'],
            'gog.com' => ['label' => 'GOG', 'icon' => 'gogdotcom'],
            'reddit.com' => ['label' => 'Reddit', 'icon' => 'reddit'],
            'discord.gg' => ['label' => 'Discord', 'icon' => 'discord'],
            'twitch.tv' => ['label' => 'Twitch', 'icon' => 'twitch'],
            'twitter.com' => ['label' => 'Twitter / X', 'icon' => 'x'],
            'x.com' => ['label' => 'Twitter / X', 'icon' => 'x'],
            'instagram.com' => ['label' => 'Instagram', 'icon' => 'instagram'],
            'youtube.com' => ['label' => 'YouTube', 'icon' => 'youtube'],
            'facebook.com' => ['label' => 'Facebook', 'icon' => 'facebook'],
            'apps.apple.com' => ['label' => 'App Store', 'icon' => 'apple'],
            'play.google.com' => ['label' => 'Google Play', 'icon' => 'googleplay'],
            'wikipedia.org' => ['label' => 'Wikipedia', 'icon' => 'wikipedia'],
            'fandom.com' => ['label' => 'Fandom', 'icon' => 'fandom'],
            'wikia.com' => ['label' => 'Fandom', 'icon' => 'fandom'],
            'meta.com' => ['label' => 'Meta', 'icon' => 'meta'],
        ];

        return array_map(function ($entry) use ($domainIcons) {
            $entry = trim($entry);
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2) {
                return ['label' => 'Website', 'icon' => 'globe', 'url' => $entry];
            }
            $url = $parts[1];
            $host = parse_url($url, PHP_URL_HOST) ?? '';
            $info = ['label' => 'Website', 'icon' => 'globe'];
            foreach ($domainIcons as $domain => $mapping) {
                if (str_contains($host, $domain)) {
                    $info = $mapping;
                    break;
                }
            }
            return [
                'label' => $info['label'],
                'icon' => $info['icon'],
                'url' => $url,
            ];
        }, explode(',', $raw));
    }

    public function releaseDate(): string
    {
        return $this->content()->get('ReleaseDate')->value() ?? '';
    }

    public function releaseYear(): string
    {
        $date = $this->releaseDate();
        if (preg_match('/^\d{4}/', $date, $m)) {
            return $m[0];
        }
        return $date;
    }
}
