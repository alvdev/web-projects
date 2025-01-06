<div class="container flex justify-between">
    <div>
        <a href="<?= $this->Html->safe($this->base_uri . 'order/main/index/' . (isset($order_form->label) ? $order_form->label : null)); ?>">
            <strong><?php $this->_('Main.steps.step_1_name'); ?></strong>
        </a>
        <p><?php $this->_('Main.steps.step_1_description'); ?></p>
    </div>

    <div>
        <a href="<?= $this->Html->safe($this->base_uri . 'order/config/index/' . (isset($order_form->label) ? $order_form->label : null)); ?>">
            <strong><?php $this->_('Main.steps.step_2_name'); ?></strong>
        </a>
        <p><?php $this->_('Main.steps.step_2_description'); ?></p>
    </div>

    <div>
        <a href="<?= $this->Html->safe($this->base_uri . 'order/cart/index/' . (isset($order_form->label) ? $order_form->label : null)); ?>">
            <strong><?php $this->_('Main.steps.step_3_name'); ?></strong>
        </a>
        <p><?php $this->_('Main.steps.step_3_description'); ?></p>
    </div>

    <div>
        <a href="<?= $this->Html->safe($this->base_uri . 'order/checkout/index/' . (isset($order_form->label) ? $order_form->label : null)); ?>" class="active">
            <strong><?php $this->_('Main.steps.step_4_name'); ?></strong>
        </a>
        <p><?php $this->_('Main.steps.step_4_description'); ?></p>
    </div>
</div>
