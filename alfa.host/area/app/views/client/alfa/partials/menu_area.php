<nav class="flex flex-col items-center relative lg:flex-row gap-x-8 gap-y-4 text-white">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-navbar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="w-full" id="main-navbar">
        <div class="flex justify-between">
            <?php $active_nav = null; ?>
            <ul class="flex gap-4">
                <?php foreach ((isset($nav) ? $nav : []) as $link => $value):
                    $active = false;
                    if ($value['active']) {
                        $active = true;
                        $attributes['class'][] = 'active';
                        $active_nav = $value;
                    }

                    // Build dropdown
                    $dropdown = false;
                    if (
                        isset($value['sub'])
                        && is_array($value['sub'])
                        && (count($value['sub']) > 1 || !array_key_exists($link, (array)$value['sub']))
                    ) {
                        $dropdown = true;
                        // Set parent to active if child is
                        if (!$active) {
                            foreach ((isset($value['sub']) ? $value['sub'] : []) as $sub_link => $sub_value) {
                                if ($sub_value['active']) {
                                    $attributes['class'][] = 'active';
                                    break;
                                }
                            }
                        }
                    }
                ?>

                    <li <?= $this->Html->buildAttributes($attributes) ?>>
                        <?php if (!$dropdown): ?>
                            <a href="<?php (print(isset($link) ? $this->Html->safe($link) : null)) ?>"
                                <?= $this->Html->buildAttributes($link_attributes) ?>>
                                <i
                                    class="<?php (print(isset($value['icon']) ? $this->Html->safe($value['icon']) : null)); ?>"></i>
                                <?php (print(isset($value['name']) ? $this->Html->safe($value['name']) : null)) ?>
                            </a>
                        <?php else: ?>
                            <!-- TODO: force tailwind to build dynamic menu classes -->
                            <?php $twForcedClasses = '
                             [anchor-name:--menu42-btn] [position-anchor:--menu42-btn] [anchor-name:--menu45-btn] [position-anchor:--menu45-btn] [anchor-name:--menu49-btn] [position-anchor:--menu49-btn]
                             [anchor-name:--menu52-btn] [position-anchor:--menu52-btn]'
                            ?>
                            <!-- end forced tailwind classes -->
                            <?php ?>
                            <button popovertarget="menu<?= $value['id'] ?>" class="[anchor-name:--menu<?= $value['id']; ?>-btn]">
                                <i class="<?php (print(isset($value['icon']) ? $this->Html->safe($value['icon']) : null)); ?>"></i>
                                <?php (print(isset($value['name']) ? $this->Html->safe($value['name']) : null)) ?>
                                <div class="font-black float-right ml-0.5">▾</div>
                            </button>
                        <?php endif ?>

                        <?php if ($dropdown): ?>
                            <div popover id="menu<?= $value['id']; ?>" class="[position-anchor:--menu<?= $value['id']; ?>-btn] top-[anchor(bottom)] left-[anchor(left)] mt-4 -ml-6">
                                <?php foreach ((isset($value['sub']) ? $value['sub'] : []) as $sub_link => $sub_value): ?>
                                    <a class="dropdown-item"
                                        href="<?php (print(isset($sub_link) ? $this->Html->safe($sub_link) : null)); ?>"><i
                                            class="<?php (print(isset($sub_value['icon']) ? $this->Html->safe($sub_value['icon']) : null)); ?>"></i>
                                        <?php (print(isset($sub_value['name']) ? $this->Html->safe($sub_value['name']) : null)); ?></a>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</nav>
