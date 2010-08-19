<h3>Add Item</h3>

<?php if ( ! isset($parent) ): ?><p><a href="<?php url('todo'); ?>">Back</a></p><?php endif; ?>

<form action="<?php url('todo', $list_id, 'add'); ?>" method="post">	
	<p>
		<label>Value: <input type="text" name="value" /></label>
		<select name="list_id"><?php to_options($lists, 'id', 'name', $list_id); ?></select>
		<input type="submit" value="Add" />
	</p>
</form>