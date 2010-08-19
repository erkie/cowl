<h2>Listing forums -- subs.</h2>

<ul>
	<?php foreach ( $categories as $category ): ?>
	<li><?php url($category); ?></li>
	<?php endforeach; ?>
</ul>