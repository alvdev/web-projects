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
}
