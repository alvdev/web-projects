<?php
$text = $text ?? '';
$color = $color ?? 'pink';
$size = $size ?? 'md';

if ($text === '') return;

$colorVar = match ($color) {
    'yellow' => 'var(--color-yellow)',
    default  => 'var(--color-pink)',
};

$sizeClass = match ($size) {
    'sm' => 'text-lg md:text-2xl',
    'lg' => 'text-3xl md:text-5xl',
    default => 'text-2xl md:text-3xl',
};

$style = "color: {$colorVar}; text-shadow: 0 0 6px {$colorVar};";
?>
<span class="font-script <?= $sizeClass ?>" style="<?= $style ?>"><?= htmlspecialchars($text) ?></span>
