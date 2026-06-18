<?php

class PostPage extends \Kirby\Cms\Page
{
    public function headerImage(): ?\Kirby\Cms\File
    {
        return $this->files()->template('header')->first();
    }

    public function socialImage(): ?\Kirby\Cms\File
    {
        return $this->files()->template('social')->first();
    }

    public function relatedGames(): \Kirby\Cms\Pages
    {
        $gameUids = $this->content()->get('related_games')->split(',');
        $gameUids = array_filter(array_map('trim', $gameUids));

        if (empty($gameUids)) {
            return new \Kirby\Cms\Pages([]);
        }

        return $this->site()->find(...$gameUids);
    }

    public function parentGame(): ?\Kirby\Cms\Page
    {
        return $this->parent();
    }

    public function postDate(): string
    {
        return $this->content()->get('date')->value() ?? $this->date()->toDate('Y-m-d');
    }
}
