<h3>Add Item</h3>

<?php if ( ! isset($parent) ): ?><p><a href="<?php VH::url('todo'); ?>">Back</a></p><?php endif; ?>

<form action="<?php VH::url('todo', 'add'); ?>" method="post">
	<p><label>Value: <input type="text" name="value" /></label> <input type="submit" value="Add" /></p>
</form>