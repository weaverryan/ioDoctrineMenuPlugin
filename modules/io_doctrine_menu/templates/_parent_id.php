<?php if (isset($form['parent_id'])): ?>
  <div class="sf_admin_form_row<?php $form['parent_id']->hasError() and print ' errors' ?>">
    <?php echo $form['parent_id']->renderError() ?>
    <div>
      <?php echo $form['parent_id']->renderLabel() ?>

      <div class="content"><?php echo $form['parent_id']->render($attributes instanceof sfOutputEscaper ? $attributes->getRawValue() : $attributes) ?></div>

      <div class="help"><?php echo $form->getWidgetSchema()->getHelp('parent_id') ?></div>
    </div>
  </div>
<?php else: ?>
  <div class="sf_admin_form_row">
    <div>
      <label>Position</label>
      <div class="content">
        <?php echo link_to('Reorder this menu item', 'io_doctrine_menu_reorder', $form->getObject()) ?>
      </div>
    </div>
  </div>
<?php endif; ?>