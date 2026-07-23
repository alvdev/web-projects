<?php

class GuidePage extends \Kirby\Cms\Page
{
    public function headerImage(): ?\Kirby\Cms\File
    {
        return $this->files()->template('header')->first();
    }

    public function parentGame(): ?\Kirby\Cms\Page
    {
        return $this->parent();
    }

    public function guideDate(): string
    {
        $date = $this->content()->get('date');
        return $date->isNotEmpty() ? $date->value() : $this->date()->toDate('Y-m-d');
    }

    public function category(): string
    {
        return $this->content()->get('category')->value() ?? '';
    }

    public function difficulty(): string
    {
        return $this->content()->get('difficulty')->value() ?? '';
    }

    public function estimatedTime(): string
    {
        return $this->content()->get('estimated_time')->value() ?? '';
    }
}
