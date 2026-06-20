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
