<h2>Todo Lists</h2>

<p><a href="<?php url('todo', 'list', 'add'); ?>">Add list</a></p>

<?php if ( $lists->count() ): ?>
<ul>
	<?php foreach ( $lists as $list ): ?>
	<li>
		<a href="<?php url('todo', $list->id); ?>"><?php echo $list->name; ?></a>
		(<?php echo count($items[$list->id]); ?> items)
		<a href="<?php url('todo', 'list', $list->id, 'remove'); ?>">x</a></li>
	<?php endforeach; ?>
</ul>
<?php else: ?>
<p>No todo-lists.</p>
<?php endif; ?>