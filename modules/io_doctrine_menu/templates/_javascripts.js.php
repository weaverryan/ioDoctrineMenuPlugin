<script type="text/javascript">
/* <![CDATA[ */
$(function($) {
  $('#nested-set').NestedSortableWidget({
        loadUrl: '<?php echo url_for("@io_doctrine_menu_reorder_json")."?name=".$name ?>',
        saveUrl: '<?php echo url_for("io_doctrine_menu_reorder_save")."?name=".$name ?>',
        handle: true
  });
});
/* ]]> */
</script>
