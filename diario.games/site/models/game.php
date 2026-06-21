<?php

class GamePage extends \Kirby\Cms\Page
{
    public const GENRE_PHRASES = [
        'Acción' => 'dispara primero',
        'Aventura' => 'explora lo oculto',
        'RPG' => 'sumérgete',
        'Shooter' => 'aprieta el gatillo',
        'Estrategia' => 'piensa, luego vence',
        'Simulación' => 'vive otras vidas',
        'Deportes y Carreras' => 'a toda velocidad',
        'Terror' => 'no mires atrás',
        'Puzzle' => 'piezas en su sitio',
        'Supervivencia' => 'aguanta la noche',
        'Mundo abierto' => 'cabalga libre',
        'Multijugador' => 'juntos o nada',
    ];

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

    public function screenshots(): array
    {
        $raw = $this->content()->get('Screenshots')->value() ?? '';
        if (empty(trim($raw))) return [];
        return array_map(function ($id) {
            $id = trim($id);
            return [
                'thumb' => 'https://images.igdb.com/igdb/image/upload/t_screenshot_med/' . $id . '.jpg',
                'full'  => 'https://images.igdb.com/igdb/image/upload/t_screenshot_huge/' . $id . '.jpg',
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
