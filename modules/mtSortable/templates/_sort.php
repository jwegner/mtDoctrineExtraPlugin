<ul class="sortable-list ui-sortable">
  <?php foreach ($objects as $object): ?>
    <li id="product_<?php echo $object['id']; ?>" class="ui-state-default">
      <span class="ui-icon ui-icon-arrowthick-2-n-s"></span>
      <span class="content"><?php echo method_exists($object->getRawValue(), 'getSortableName') ? $object->getSortableName() : $object ?></span>
    </li>
  <?php endforeach; ?>
</ul>

<script type="text/javascript">
  $(function() {
    var $sortable = $('.sortable-list').sortable({
      placeholder: 'ui-state-highlight',
      revert: true,
      opacity: 0.8,
      cursor: 'move',
      scroll: false,
      update: function (event, ui) {
        var order = $(this).sortable('serialize', {
          key: 'order[]'
        });
        <?php $form = new BaseForm(); ?>
        $.post('<?php echo url_for($route, array('sf_format' => 'json', $form->getCSRFFieldName() => $form->getCSRFToken())) ?>', order);
      }
    }).disableSelection();
  });
</script>
