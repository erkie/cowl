<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8" />
		<title>Your site</title>
		
		<?php css(); ?>
	</head>
	
	<body id="page-<?php p($request->pieces[0]); ?>">
		<div id="wrapper">
			<h1><a href="<?php url(); ?>">Welcome, to your site</a></h1>
			
			<?php flash(); ?>
			
			<?php include($this->template); ?>
		</div>
		
		<?php js(); ?>
	</body>
</html>