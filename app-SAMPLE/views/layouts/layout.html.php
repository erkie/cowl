<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=UTF-8" /> 
		<title>Cowl</title>
		
		<?php css(); ?>
	</head>
	
	<body>
		<div id="wrapper">
			<h1><a href="<?php url(); ?>">Cowl</a></h1>
			
			<?php flash(); ?>
			
			<?php include($this->template); ?>
		</div>
		
		<?php js(); ?>
	</body>
</html>