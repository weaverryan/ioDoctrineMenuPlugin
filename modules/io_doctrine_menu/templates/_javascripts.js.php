<script type="text/javascript">
/* <![CDATA[ */
$(function($) {
  $('#nested-set').NestedSortableWidget({
        loadUrl: '<?php echo url_for('io_doctrine_menu_reorder_json', $menu) ?>',
        saveUrl: '<?php echo url_for('io_doctrine_menu_reorder_save', $menu) ?>',
        handle: true
  });
});
/* ]]> */
</script>
