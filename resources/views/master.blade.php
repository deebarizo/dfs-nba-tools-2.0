<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">

	<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
	<script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
	<script src="http://code.highcharts.com/highcharts.js"></script>

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
	<style>body { padding-top: 50px; }</style>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>

	<title>DFS NBA Tools</title>
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