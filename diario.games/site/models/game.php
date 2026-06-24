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

        $labels = [
            1 => 'Official Website',
            13 => 'Steam',
            16 => 'Epic Games',
            17 => 'GOG',
            14 => 'Reddit',
            18 => 'Discord',
            6 => 'Twitch',
            5 => 'Twitter / X',
            8 => 'Instagram',
            9 => 'YouTube',
            10 => 'App Store (iOS)',
            12 => 'Google Play',
            20 => 'Google Play',
            21 => 'App Store (iOS)',
        ];

        return array_map(function ($entry) use ($labels) {
            $entry = trim($entry);
            $parts = explode(':', $entry, 2);
            if (count($parts) !== 2) {
                return ['label' => 'Website', 'url' => $entry];
            }
            $cat = (int) $parts[0];
            $url = $parts[1];
            return [
                'label' => $labels[$cat] ?? 'Website',
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
