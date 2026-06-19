<?php

$genres = [
    ['slug' => 'accion', 'label' => 'Acción'],
    ['slug' => 'aventura', 'label' => 'Aventura'],
    ['slug' => 'rpg', 'label' => 'RPG'],
    ['slug' => 'shooter', 'label' => 'Shooter'],
    ['slug' => 'estrategia', 'label' => 'Estrategia'],
    ['slug' => 'simulacion', 'label' => 'Simulación'],
    ['slug' => 'deportes', 'label' => 'Deportes y Carreras'],
    ['slug' => 'terror', 'label' => 'Terror'],
    ['slug' => 'puzzle', 'label' => 'Puzzle'],
    ['slug' => 'supervivencia', 'label' => 'Supervivencia'],
    ['slug' => 'mundo-abierto', 'label' => 'Mundo abierto'],
    ['slug' => 'multijugador', 'label' => 'Multijugador'],
];
?>
<nav class="flex gap-4 overflow-x-auto text-sm">
    <?php foreach ($genres as $genre): ?>
    <a href="<?= url('genre/' . $genre['slug']) ?>" class="text-muted hover:text-neon-cyan transition whitespace-nowrap">
        <?= $genre['label'] ?>
    </a>
    <?php endforeach ?>
</nav>
