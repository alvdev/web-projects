<?php

use Kirby\Cms\App;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\Dir;
use Kirby\Toolkit\F;
use Kirby\Http\Response;

App::plugin('alv/pregenerate-thumbnails', [
    'hooks' => [
        'file.create:after' => function ($file) {
            pregenerateStaticThumbnails($file);
        },
        'file.replace:after' => function ($file) {
            pregenerateStaticThumbnails($file);
        }
    ],
    'routes' => [
        [
            // Route to serve thumbnails from the content folder
            'pattern' => 'static-thumbs/(:all)/thumbnails/(:any)',
            'action'  => function ($path, $filename) {
                if ($page = kirby()->page($path)) {
                    $root = $page->root() . '/thumbnails/' . $filename;
                    if (F::exists($root)) {
                        return Response::file($root);
                    }
                }
                return false;
            }
        ]
    ],
    'fileMethods' => [
        'staticSrcset' => function ($setName) {
            $kirby = $this->kirby();
            $srcsetCfg = $kirby->option('thumbs.srcsets.' . $setName);
            
            if (!$srcsetCfg || !is_array($srcsetCfg)) {
                return $this->srcset($setName);
            }

            $srcs = [];
            foreach ($srcsetCfg as $condition => $options) {
                // Calculate filename manually to match Kirby's convention
                $ext = isset($options['format']) ? $options['format'] : $this->extension();
                
                // Base name parts
                $parts = [];
                if (isset($options['width'])) $parts[] = $options['width'] . 'x';
                if (isset($options['height'])) {
                    if (empty($parts)) $parts[] = 'x' . $options['height'];
                    else $parts[0] .= $options['height'];
                }
                if (isset($options['crop']) && $options['crop']) $parts[] = 'crop';
                if (isset($options['blur']) && $options['blur']) $parts[] = 'blur' . $options['blur'];
                if (isset($options['grayscale']) && $options['grayscale']) $parts[] = 'bw';
                if (isset($options['quality']) && $options['quality']) $parts[] = 'q' . $options['quality'];
                
                $suffix = !empty($parts) ? '-' . implode('-', $parts) : '';
                $filename = $this->name() . $suffix . '.' . $ext;
                
                $staticRoot = $this->parent()->root() . '/thumbnails/' . $filename;
                
                if (F::exists($staticRoot)) {
                    $url = $kirby->url() . '/content/' . $this->parent()->diruri() . '/thumbnails/' . $filename;
                } else {
                    // Fallback to media URL if static isn't found
                    $url = $this->thumb($options)->url();
                }
                
                $srcs[] = $url . ' ' . $condition;
            }

            return implode(', ', $srcs);
        },
        'staticUrl' => function ($options = []) {
            $kirby = $this->kirby();
            $ext = isset($options['format']) ? $options['format'] : $this->extension();
            
            $parts = [];
            if (isset($options['width'])) $parts[] = $options['width'] . 'x';
            if (isset($options['height'])) {
                if (empty($parts)) $parts[] = 'x' . $options['height'];
                else $parts[0] .= $options['height'];
            }
            if (isset($options['crop']) && $options['crop']) $parts[] = 'crop';
            if (isset($options['quality']) && $options['quality']) $parts[] = 'q' . $options['quality'];
            
            $suffix = !empty($parts) ? '-' . implode('-', $parts) : '';
            $filename = $this->name() . $suffix . '.' . $ext;
            
            $staticRoot = $this->parent()->root() . '/thumbnails/' . $filename;

            if (F::exists($staticRoot)) {
                return $kirby->url() . '/content/' . $this->parent()->diruri() . '/thumbnails/' . $filename;
            }

            return $this->thumb($options)->url();
        }
    ]
]);

function pregenerateStaticThumbnails($file) {
    if ($file->type() !== 'image') {
        return;
    }

    $parent = $file->parent();
    $parentId = $parent->id();
    $kirby = $file->kirby();

    $srcsets = [];

    // Identify which srcsets to pre-generate
    if (Str::contains($parentId, 'pictures') || Str::contains($parentId, 'people')) {
        $srcsets = ['gallery-avif', 'gallery-webp', 'gallery-default'];
    } elseif (Str::contains($parentId, 'book')) {
        $srcsets = ['book-avif', 'book-webp', 'book-default'];
    }
    
    // Also optimize covers for LCP Performance
    $isCover = $parent->cover()->toFile()?->uuid() === $file->uuid();
    if ($isCover) {
        $srcsets = array_merge($srcsets, ['avif', 'webp', 'avif-mobile', 'webp-mobile']);
    }

    if (empty($srcsets)) {
        return;
    }

    $srcsets = array_unique($srcsets);
    
    $thumbDir = $parent->root() . '/thumbnails';
    if (count(array_intersect($srcsets, ['gallery-avif', 'gallery-webp', 'gallery-default', 'book-avif', 'book-webp', 'book-default'])) > 0) {
        Dir::make($thumbDir);
    }

    foreach ($srcsets as $setName) {
        $srcsetCfg = $kirby->option('thumbs.srcsets.' . $setName);
        
        if ($srcsetCfg && is_array($srcsetCfg)) {
            foreach ($srcsetCfg as $options) {
                try {
                    $thumb = $file->thumb($options);
                    if (in_array($setName, ['gallery-avif', 'gallery-webp', 'gallery-default', 'book-avif', 'book-webp', 'book-default'])) {
                        F::copy($thumb->root(), $thumbDir . '/' . $thumb->filename(), true);
                    }
                } catch (\Exception $e) {}
            }
        }
    }
    
    // Also generate a standard 600px thumb which is used as fallback 'src'
    try {
        $thumb = $file->thumb(['width' => 600]);
        if (Str::contains($parentId, 'pictures') || Str::contains($parentId, 'people') || Str::contains($parentId, 'book')) {
            Dir::make($thumbDir);
            F::copy($thumb->root(), $thumbDir . '/' . $thumb->filename(), true);
        }
    } catch (\Exception $e) {}
}
