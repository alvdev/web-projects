<?php

class NewsPage extends \Kirby\Cms\Page
{
    public function headerImage(): ?\Kirby\Cms\File
    {
        return $this->files()->template('header')->first();
    }

    public function parentGame(): ?\Kirby\Cms\Page
    {
        return $this->parent();
    }

    public function newsDate(): string
    {
        $date = $this->content()->get('date');
        return $date->isNotEmpty() ? $date->value() : $this->date()->toDate('Y-m-d');
    }

    public function newsType(): string
    {
        return $this->content()->get('type')->value() ?? '';
    }

    public function source(): string
    {
        return $this->content()->get('source')->value() ?? '';
    }

    public static function generateDefaultHeader(\Kirby\Cms\Page $page): bool
    {
        $parent = $page->parent();
        if (!$parent) return false;

        if ($page->files()->template('header')->count() > 0) return false;

        $screenshots = glob($parent->root() . '/screenshot-*.jpg');
        $heros = glob($parent->root() . '/*-hero.jpg');
        $covers = glob($parent->root() . '/*.jpg');
        $covers = array_filter($covers, fn($f) => !str_ends_with($f, '-hero.jpg'));

        $total = count($screenshots);
        $source = $total > 1 ? $screenshots[$total - 1] : ($total > 0 ? $screenshots[0] : ($heros[0] ?? $covers[0] ?? ''));

        if (!$source || !file_exists($source)) return false;

        return static::createHeaderImage($source, $page->root() . '/header.jpg', 'NOTICIA', '#00ff88');
    }

    private static function createHeaderImage(string $source, string $dest, string $label, string $color): bool
    {
        $cmd = sprintf(
            'convert %s -modulate 100,80,100 -fill %s -colorize 15%% -font Helvetica -pointsize 48 -fill white -gravity center -annotate +0+60 %s %s 2>&1',
            escapeshellarg($source),
            escapeshellarg($color),
            escapeshellarg($label),
            escapeshellarg($dest)
        );
        exec($cmd, result_code: $exitCode);
        if ($exitCode !== 0 || !file_exists($dest)) return false;

        file_put_contents($dest . '.txt', "Title: Header\n\n----\n\nTemplate: header\n\n----\n");
        return true;
    }
}
