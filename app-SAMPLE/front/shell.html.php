<!DOCTYPE html>
<html>
	<head>
		<title>Cowl</title>
		
		<?php css(); ?>
	</head>
	
	<body>
		<div id="wrapper">
			<h1><a href="<?php url(); ?>">Cowl</a></h1>
			
			<?php if ( isset($message) ): ?><p id="cowl-message"><?php echo $message; ?></p><?php endif; ?>
			
			<?php include($this->template); ?>
		</div>
		
		<?php js(); ?>
	</body>
</html>