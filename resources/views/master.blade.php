<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.4/css/jquery.dataTables.css">

	<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script src="http://code.highcharts.com/highcharts.js"></script>
	<script src="https://cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/plug-ins/9dcbecd42ad/integration/bootstrap/3/dataTables.bootstrap.js"></script>

	<style>
		body { padding-top: 50px; }

		hr {
			border-color: #333;
		}
	</style>

	<?php 
		if (isset($name)) { $titleTag = $name.' | '; }
		else { $titleTag = ''; }
	?>

	<title>{{ $titleTag }}DFS NBA Tools</title>
</head>
<body>
	<div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="/">DFS Tools</a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li class="{!! setActive('/') !!}"><a href="/">Home</a></li>
					<li class="{!! setActive('daily_fd_nba') !!}"><a href="/daily_fd_nba">Daily FD</a></li>
					<li class="{!! setActive('studies*') !!}"><a href="/studies">Studies</a></li>
					<li class="{!! setActive('scrapers*') !!}"><a href="/scrapers">Scrapers</a></li>
				</ul>
			</div><!--/.nav-collapse -->
		</div>
    </div>
	<div class="container">
		@yield('content')
	</div>
</body>
</html>