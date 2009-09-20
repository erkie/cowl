<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<title>Cowl</title>
		
		<?php VH::css(); ?>
	</head>
	
	<body>
		<div id="wrapper">
			<h1><a href="<?php VH::url(); ?>">Cowl</a></h1>
			
			<?php if ( isset($message) ): ?><p id="cowl-message"><?php echo $message; ?></p><?php endif; ?>
			
			<?php include($this->template); ?>
		</div>
		
		<?php VH::js(); ?>
	</body>
</html>