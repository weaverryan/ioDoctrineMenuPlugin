<script type="text/javascript">
/* <![CDATA[ */
$(function($) {
  $('#nested-set').NestedSortableWidget({
        loadUrl: '<?php echo url_for("menu/json")."?menu=".$name ?>',
        saveUrl: '<?php echo url_for("menu/savejson")."?menu=".$name ?>',
        handle: true
  });
});
/* ]]> */
</script>
