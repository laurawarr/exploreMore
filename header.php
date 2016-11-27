<!DOCTYPE html>
<html>
<head>
	<title>Database Project</title>
	<link rel='stylesheet' href='_css/main.css' />
	<link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Indie+Flower|Permanent+Marker|Shadows+Into+Light" rel="stylesheet">
</head>
<body>
	<form action="index.php#method-post" method='POST'>
		<div id="header">
			<div id="search">
				<h1 id="explore-more">EXPLORE MORE</h1>
				<div class = "toggle row">
					<select class="select" name="select">
						<?php
						$select = isset($_SESSION["select"]) ? $_SESSION["select"] : '';						
						?>
						<option value='name' <?php echo ($select == 'name') ? 'selected' : '' ;  ?> >Site Name</option>
						<option value='trail' <?php echo ($select == 'trail') ? 'selected' : '' ; ?> >Trail Name</option>
						<option value="feature" <?php echo ($select == 'feature') ? 'selected' : '' ; ?> >Site Features</option>
						<input class="submit" type='submit' value='SEARCH'/>
					</select> 
				</div>
				<h3 id="tagline">Spend less time planning your trip and more time doing what you love.</h3>
			</div>
		</div>
	</form>
	<div id="content" class="column">