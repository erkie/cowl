<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
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
		
		<?php if ( isset($_GET['clr'])) $_SESSION['times'] = array(); ?>
		
		<p>Page (not really) rendered in <?php $t = round(xdebug_time_index(), 4); echo $t; $_SESSION['times'][] = $t; ?> seconds. Mean is <?php echo round(array_sum($_SESSION['times']) / count($_SESSION['times']), 4); ?> (<?php echo count($_SESSION['times']); ?> times)</p> 
		
		<?php js(); ?>
	</body>
</html>