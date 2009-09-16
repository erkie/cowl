<h1>Hello <?php=$name; ?></h1>

<ol>
	<?php foreach ( $tests as $test ): ?>
	<li><?php=$test->value; ?></li>
	<?php endforeach; ?>
</ol>