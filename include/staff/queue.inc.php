<?php
// vim: expandtab sw=2 ts=2 sts=2:

if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

$info = $qs = array();

if (!$queue) {
    $queue = CustomQueue::create(array(
        'flags' => CustomQueue::FLAG_QUEUE,
    ));
    $title=__('Add New Queue');
    $action='create';
    $submit_text=__('Create');
}
else {
    $title=__('Manage Custom Queue');
    $action='update';
    $submit_text=__('Save Changes');
    $info['id'] = $queue->getId();
    $qs += array('id' => $queue->getId());
}
?>

<form action="queues.php?<?php echo Http::build_query($qs); ?>" method="post" id="save" autocomplete="off">
  <?php csrf_token(); ?>
  <input type="hidden" name="do" value="<?php echo $action; ?>">
  <input type="hidden" name="a" value="<?php echo Format::htmlchars($_REQUEST['a']); ?>">
  <input type="hidden" name="id" value="<?php echo $info['id']; ?>">
  <input type="hidden" name="root" value="<?php echo Format::htmlchars($_REQUEST['t']); ?>">

  <h2><a href="settings.php?t=tickets#queues"><?php echo __('Ticket Queues'); ?></a>
      <i class="icon-caret-right" style="color:rgba(0,0,0,.3);"></i> <?php echo $title; ?>
      <?php if (isset($queue->id)) { ?><small>
      — <?php echo $queue->getFullName(); ?></small>
      <?php } ?>
  </h2>

  <ul class="clean tabs">
    <li class="active"><a href="#criteria"><i class="icon-filter"></i>
      <?php echo __('Criteria'); ?></a></li>
    <li><a href="#columns"><i class="icon-columns"></i>
      <?php echo __('Columns'); ?></a></li>
    <li><a href="#sorting-tab"><i class="icon-sort-by-attributes"></i>
      <?php echo __('Sort'); ?></a></li>
    <li><a href="#preview-tab"><i class="icon-eye-open"></i>
      <?php echo __('Preview'); ?></a></li>
  </ul>

  <div class="tab_content" id="criteria">
    <table class="table">
      <td style="width:60%; vertical-align:top">
        <div><strong><?php echo __('Queue Name'); ?>:</strong></div>
        <input type="text" name="name" value="<?php
          echo Format::htmlchars($queue->getName()); ?>"
          style="width:100%" />

        <br/>
        <br/>
        <div><strong><?php echo __("Queue Search Criteria"); ?></strong></div>
        <label class="checkbox" style="line-height:1.3em">
          <input type="checkbox" class="checkbox" name="inherit" <?php
            if ($queue->inheritCriteria()) echo 'checked="checked"';
            ?>/>
          <?php echo __('Include parent search criteria');
          if ($queue->parent) { ?>
            <span id="parent_q_crit" class="faded">
            <i class="icon-caret-right"></i>
            <br/><?php
              echo nl2br(Format::htmlchars($queue->parent->describeCriteria()));
            ?></span>
<?php     } ?>
        </label>
        <hr/>
        <div class="error"><?php echo $errors['criteria']; ?></div>
        <div class="advanced-search">
<?php
            $form = $queue->getForm();
            $search = $queue;
            $matches = $queue->getSupportedMatches();
            include STAFFINC_DIR . 'templates/advanced-search-criteria.tmpl.php';
?>
        </div>
      </td>
      <td style="width:35%; padding-left:40px; vertical-align:top">
        <div><strong><?php echo __("Parent Queue"); ?>:</strong></div>
        <select name="parent_id" onchange="javascript:
        $('#parent_q_crit').toggle($(this).find(':selected').val()
          == <?php echo $queue->parent_id ?: 0; ?>);">
          <option value="0">— <?php echo __('Top-Level Queue'); ?> —</option>
<?php foreach (CustomQueue::queues() as $cq) {
        if ($cq->getId() == $queue->getId())
          continue;
?>
          <option value="<?php echo $cq->id; ?>"
            <?php if ($cq->getId() == $queue->parent_id) echo 'selected="selected"'; ?>
            ><?php echo $cq->getFullName(); ?></option>
<?php } ?>
        </select>
        <div class="error"><?php echo Format::htmlchars($errors['parent_id']); ?></div>

        <br/>
        <br/>
        <div><strong><?php echo __("Quick Filter"); ?></strong></div>
        <hr/>
        <select name="filter">
          <option value="" <?php if ($queue->filter == "")
              echo 'selected="selected"'; ?>>— <?php echo __('None'); ?> —</option>
          <option value="::" <?php if ($queue->filter == "::")
              echo 'selected="selected"'; ?>>— <?php echo __('Inherit from parent');
            if ($queue->parent 
                && ($qf = $queue->parent->getQuickFilterField()))
                echo sprintf(' (%s)', $qf->getLabel()); ?> —</option>
<?php foreach (CustomQueue::getSearchableFields('Ticket') as $path=>$f) {
        list($label, $field) = $f;
        if (!$field->supportsQuickFilter())
          continue;
?>
          <option value="<?php echo $path; ?>"
            <?php if ($path == $queue->filter) echo 'selected="selected"'; ?>
            ><?php echo $label; ?></option>
<?php } ?>
        </select>
        <br/>
        <br/>
        <div><strong><?php echo __("Sort Options"); ?></strong></div>
        <hr/>
      </td>
    </table>
  </div>

  <div class="hidden tab_content" id="columns">

    <div class="tab-desc">
        <p><b><?php echo __("Manage columns in this queue"); ?></b>
        <br><?php echo __(
        "Add, remove, and customize the content of the columns in this queue using the options below. Click a column header to manage or resize it"); ?></p>
    </div>
    <?php include STAFFINC_DIR . "templates/queue-columns.tmpl.php"; ?>
  </div>
    
    
    <div class="hidden tab_content" id="sorting-tab">
        <div class="tab-desc">
            <p><b><?php echo __("Manage Queue Sorting"); ?></b>
            <br><?php echo __("Add, edit or remove the sorting criteria for this custom queue using the options below. Sorting is priortized in ascending order."); ?></p>
        </div>
        <table class="queue-sort table">
<?php
if ($queue->parent) { ?>
          <tbody>
            <tr>
              <td colspan="3">
                <input type="checkbox" name="inherit-sorting" <?php
                  if ($queue->inheritSorting()) echo 'checked="checked"'; ?>
                  onchange="javascript:$(this).closest('table').find('.if-not-inherited').toggle(!$(this).prop('checked'));" />
                <?php echo __('Inherit sorting from the parent queue'); ?>
                <br /><br />
              </td>
            </tr>
          </tbody>
<?php } ?>
          <tbody class="if-not-inherited <?php if ($queue->inheritSorting()) echo 'hidden'; ?>">
            <tr class="header">
              <td nowrap><small><b><?php echo __('Name'); ?></b></small></td>
              <td><small><b><?php echo __('Details'); ?></b></small></td>
              <td/>
            </tr>
          </tbody>
          <tbody class="sortable-rows if-not-inherited <?php
            if ($queue->inheritSorting()) echo 'hidden'; ?>">
            <tr id="sort-template" class="hidden">
              <td nowrap>
                <i class="faded-more icon-sort"></i>
                <input type="hidden" data-name="sorts[]" />
                <span data-name="name"></span>
              </td>
              <td>
                <div>
                <a class="inline action-button"
                    href="#" onclick="javascript:
                    var colid = $(this).closest('tr').find('[data-name^=sorts]').val();
                    $.dialog('ajax.php/tickets/search/sort/edit/' + colid, 201);
                    return false;
                    "><i class="icon-cog"></i> <?php echo __('Config'); ?></a>
                </div>
              </td>
              <td>
                <a href="#" class="pull-right drop-sort" title="<?php echo __('Delete');
                  ?>"><i class="icon-trash"></i></a>
              </td>
            <tr>
          </tbody>
            <tbody class="if-not-inherited <?php
            if ($queue->inheritSorting()) echo 'hidden'; ?>">
              <tr class="header">
                  <td colspan="3"></td>
              </tr>
              <tr>
                  <td colspan="3" id="append-sort">
                      <i class="icon-plus-sign"></i>
                      <select id="add-sort" data-quick-add="queue-sort">
                          <option value="">— <?php
                            echo __('Add Sort Criteria'); ?> —</option>
<?php foreach (QueueSort::forQueue($queue) as $QS) { ?>
                          <option value="<?php echo $QS->id; ?>"><?php
                            echo Format::htmlchars($QS->name); ?></option>
<?php } ?>
                          <option value="0" data-quick-add>&mdash; <?php
                            echo __('Add New Sort Criteria');?> &mdash;</option>
                      </select>
                      <button type="button" class="green button"><?php
                        echo __('Add'); ?></button>
                  </td>
              </tr>
            </tbody>
<script>
+function() {
var Q = setInterval(function() {
  if ($('#append-sort').length == 0)
    return;
  clearInterval(Q);

  var addSortOption = function(sortid, info) {
    if (!sortid) return;
    var copy = $('#sort-template').clone();
    info['sorts[]'] = sortid;
    copy.find('input[data-name]').each(function() {
      var $this = $(this),
          name = $this.data('name');
      if (info[name] !== undefined) {
        $this.val(info[name]);
      }
      $this.attr('name', name);
    });
    copy.find('span').text(info['name']);
    copy.attr('id', '').show().insertBefore($('#sort-template'));
    copy.removeClass('hidden');
    copy.find('a.drop-sort').click(function() {
      $('<option>')
        .attr('value', copy.find('input[data-name^=sorts]').val())
        .text(info.name)
        .insertBefore($('#add-sort')
          .find('[data-quick-add]')
        );
      copy.fadeOut(function() { $(this).remove(); });
      return false;
    });
    var selected = $('#add-sort').find('option[value=' + sortid + ']');
    selected.remove();
  };

  $('#append-sort').find('button').on('click', function() {
    var selected = $('#add-sort').find(':selected'),
        id = parseInt(selected.val());
    if (!id)
        return;
    addSortOption(id, {name: selected.text()});
    return false;
  });
<?php foreach ($queue->getSortOptions() as $C) {
  echo sprintf('addSortOption(%d, {name: %s});',
    $C->sort_id, JsonDataEncoder::encode($C->getName())
  );
} ?>
}, 25);
}();
</script>
        </table>
    </div>    
    
    <div class="hidden tab_content" id="preview-tab">
    <div id="preview">
    </div>

    <script>
      $(function() {
        $('#preview-tab').on('afterShow', function() {
          $.ajax({
            url: 'ajax.php/queue/preview',
            type: 'POST',
            data: $('#save').serializeArray(),
            success: function(html) {
              $('#preview').html(html);
            }
          });
        });
      });
    </script>

  </div>

  <p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo $submit_text; ?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="window.history.go(-1);">
  </p>

</form>
