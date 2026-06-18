<?php

class GamePage extends \Kirby\Cms\Page
{
    public function genres(): array
    {
        $genres = $this->content()->get('genres')->split(',');
        $labels = [
            'accion' => 'Acción',
            'aventura' => 'Aventura',
            'rpg' => 'RPG',
            'shooter' => 'Shooter',
            'estrategia' => 'Estrategia',
            'simulacion' => 'Simulación',
            'deportes' => 'Deportes y Carreras',
            'terror' => 'Terror',
            'puzzle' => 'Puzzle',
            'supervivencia' => 'Supervivencia',
            'mundo-abierto' => 'Mundo abierto',
            'multijugador' => 'Multijugador',
        ];

        return array_map(fn($g) => $labels[trim($g)] ?? trim($g), $genres);
    }

    public function posts(): \Kirby\Cms\Pages
    {
        return $this->children()->listed()->sortBy('date', 'desc');
    }

    public function cover(): ?\Kirby\Cms\File
    {
        return $this->files()->template('cover')->first();
    }

    public function releaseDate(): string
    {
        return $this->content()->get('release_date')->value();
    }
}
