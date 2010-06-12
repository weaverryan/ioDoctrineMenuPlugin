<?php if ($menu->getNode()->getChildren()): ?>
  <div class="sf_admin_list">
    <div id="nested-set"></div>
  </div>

  <?php include_partial('io_doctrine_menu/javascripts.js', array('menu' => $menu)) ?>
<?php else: ?>
  <h2>This root menu item has no children to reorder.</h2>
<?php endif; ?>