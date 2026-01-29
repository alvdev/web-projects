<?php

use Kirby\Cms\App;
use Kirby\Toolkit\Str;
use Kirby\Toolkit\Dir;
use Kirby\Toolkit\F;
use Kirby\Http\Response;

App::plugin('alv/pregenerate-thumbnails', [
    'hooks' => [
        'file.create:after'  => function ($file) {
            pregenerateStaticThumbnails($file);
            clearGalleryCache($file);
        },
        'file.replace:after' => function ($file) {
            pregenerateStaticThumbnails($file);
            clearGalleryCache($file);
        },
        'file.delete:after'  => function ($file) {
            clearGalleryCache($file);
        },
        'page.update:after' => function ($newPage) {
            clearGalleryCacheForPage($newPage);
        },
        'page.changeSort:after' => function ($newPage) {
            clearGalleryCacheForPage($newPage);
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
                $thumb = $this->thumb($options);
                $filename = $thumb->filename();
                
                $staticRoot = $this->parent()->root() . '/thumbnails/' . $filename;
                
                if (F::exists($staticRoot)) {
                    $url = $kirby->url() . '/content/' . $this->parent()->diruri() . '/thumbnails/' . $filename;
                    $srcs[] = $url . ' ' . $condition;
                } else if (!Str::startsWith($setName, 'book-')) {
                    // Only fallback for non-book galleries
                    $srcs[] = $thumb->url() . ' ' . $condition;
                }
            }

            return implode(', ', $srcs);
        },
        'staticUrl' => function ($options = []) {
            $kirby = $this->kirby();
            $thumb = $this->thumb($options);
            $filename = $thumb->filename();
            
            $staticRoot = $this->parent()->root() . '/thumbnails/' . $filename;

            if (F::exists($staticRoot)) {
                return $kirby->url() . '/content/' . $this->parent()->diruri() . '/thumbnails/' . $filename;
            }

            // For Books, we avoid triggering heavy processing
            if (Str::contains($this->parent()->id(), 'book')) {
                return null; 
            }

            return $thumb->url();
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
                    $filename = $thumb->filename();
                    
                    if (in_array($setName, ['gallery-avif', 'gallery-webp', 'gallery-default', 'book-avif', 'book-webp', 'book-default'])) {
                        $target = $thumbDir . '/' . $filename;
                        if (!F::exists($target)) {
                            Dir::make($thumbDir);
                            F::copy($thumb->root(), $target, true);
                        }
                    }
                } catch (\Exception $e) {}
            }
        }
    }
    
    // Also generate a standard 600px thumb which is used as fallback 'src'
    try {
        $thumb = $file->thumb(['width' => 600]);
        if (Str::contains($parentId, 'pictures') || Str::contains($parentId, 'people') || Str::contains($parentId, 'book')) {
            $target = $thumbDir . '/' . $thumb->filename();
            if (!F::exists($target)) {
                Dir::make($thumbDir);
                F::copy($thumb->root(), $target, true);
            }
        }
    } catch (\Exception $e) {}
}

/**
 * Helper to clear caches by either file or page object
 */
function clearGalleryCache($file) {
    clearInternalGalleryCache($file->parent());
}

function clearGalleryCacheForPage($page) {
    clearInternalGalleryCache($page);
}

function clearInternalGalleryCache($parent) {
    $parentId = $parent->id();
    
    if (Str::contains($parentId, 'pictures') || Str::contains($parentId, 'people') || Str::contains($parentId, 'book')) {
        $kirby = $parent->kirby();
        
        // 1. Flush the custom 'gallery' cache
        if ($galleryCache = $kirby->cache('gallery')) {
            $galleryCache->flush();
        }
        
        // 2. Flush the native 'pages' cache
        if ($pagesCache = $kirby->cache('pages')) {
            $pagesCache->flush(); 
        }
    }
}
