<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $title; ?></title>
<link rel='stylesheet' href="<?= base_url(); ?>css/template.css">
</head>
<body>
	<div id='header'>
    	<h1>Connect 4!</h1>
    </div>
    <div id='nav'>
    	<ul>
    		<li><?php echo "<p>" . anchor('account/index','Home') . "</p>";?></li>
    	</ul>
    </div>
	<div id='main'>
		<?php 
		$this->load->view($main);
		if (isset($errormsg))
			echo "<div id='errormsg'>" . $errormsg . "</div>";
		?>
	</div>
</body>
</html>
