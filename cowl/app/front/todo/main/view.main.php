<h2>ToDo</h2>

<ul>
	<?php foreach ( $items as $item ): ?>
	<li>
		<?php if ($item->is_done): ?><del><?php endif; ?><?php echo $item->todo; ?><?php if($item->is_done): ?></del><?php endif; ?>
		<a href="<?php VH::url('todo', $item->id, 'check'); ?>">v/</a>
		<a href="<?php VH::url('todo', $item->id, 'remove'); ?>">x</a>
	</li>
	<?php endforeach; ?>
</ul>

<p><?php echo $pager->html(array('todo')); ?></p>

<?php $parent = true;
      include('view.add.php'); ?>