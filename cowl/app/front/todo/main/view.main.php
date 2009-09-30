<h2>ToDo</h2>

<?php if ( $items->count() ): ?>
<ul>
	<?php foreach ( $items as $item ): ?>
	<li <?php only_if($item->is_done, 'class="is-done"'); ?>>
		<?php if ($item->is_done): ?><del><?php endif; ?><?php echo $item->todo; ?><?php if ($item->is_done): ?></del><?php endif; ?>
		<a href="<?php url('todo', $item->id, 'check'); ?>">v/</a>
		<a href="<?php url('todo', $item->id, 'remove'); ?>">x</a>
	</li>
	<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No items in this list. How's about adding some?</p>
<?php endif; ?>

<p><?php echo $pager->html(array('todo', $list_id)); ?></p>

<?php $parent = true;
      include('view.add.php'); ?>