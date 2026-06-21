<?php
$phrase = $phrase ?? 'ÚLTIMOS POSTS';
$color = $color ?? 'pink';
$bg = $bg ?? 'black';
$speed = $speed ?? 'medium';

$colorVar = match ($color) {
    'yellow' => 'var(--color-yellow)',
    'purple' => 'var(--color-purple)',
    default  => 'var(--color-pink)',
};

$bgClass = match ($bg) {
    'pink'   => 'band-pink',
    'purple' => 'band-purple',
    'transparent' => '',
    default  => 'bg-bg',
};

$dur = match ($speed) {
    'slow'   => '30s',
    'fast'   => '10s',
    default  => '18s',
};

$separator = '★';
$display = "{$phrase} {$separator} {$phrase} {$separator} {$phrase} {$separator} {$phrase} {$separator} {$phrase} {$separator}";

$style = "color: {$colorVar}; text-shadow: 0 0 8px {$colorVar}, 0 0 24px {$colorVar}; --marquee-duration: {$dur};";
?>
<div class="marquee <?= $bgClass ?>" style="<?= $bg === 'transparent' ? 'background:transparent;' : '' ?>">
  <div class="marquee-track" style="<?= $style ?>">
    <span class="font-graffiti uppercase tracking-widest text-3xl md:text-5xl whitespace-nowrap px-4"><?= htmlspecialchars($display) ?></span>
    <span class="font-graffiti uppercase tracking-widest text-3xl md:text-5xl whitespace-nowrap px-4" aria-hidden="true"><?= htmlspecialchars($display) ?></span>
  </div>
</div>
