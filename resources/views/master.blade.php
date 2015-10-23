<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">

	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap-theme.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.10.4/css/jquery.dataTables.css">
	<link rel="stylesheet" href="/css/jquery.qtip.min.css">
	<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.2/themes/humanity/jquery-ui.css">
	<link rel="stylesheet" href="/css/style.css">

	<script src="https://code.jquery.com/jquery-1.11.1.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
	<script src="/js/highcharts.js"></script>
	<script src="https://cdn.datatables.net/1.10.4/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/plug-ins/9dcbecd42ad/integration/bootstrap/3/dataTables.bootstrap.js"></script>
	<script src="/js/jquery.qtip.min.js"></script>
	<script src="https://code.jquery.com/ui/1.11.2/jquery-ui.min.js"></script>

	<?php 
		if (isset($name)) { $titleTag = $name.' | '; }
		else { $titleTag = ''; }
	?>

	<title>{{ $titleTag }}DFS Tools</title>
</head>
<body>
	<div class="navbar navbar-inverse" role="navigation">
		<div class="container">
			<div class="navbar-header">
				<a class="navbar-brand" href="/">DFS Tools</a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li class="{!! setActive('daily*') !!}{!! setActive('solver_fd_nba*') !!}{!! setActive('/') !!}{!! setActive('solver_with_top_plays_fd_nba*') !!}{!! setActive('solver_top_plays*') !!}{!! setActive('lineup_builder*') !!}"><a href="/">Daily</a></li>
					<li class="{!! setActive('games*') !!}"><a href="/games">Games</a></li>
					<li class="{!! setActive('teams*') !!}"><a href="/teams/ATL">Teams</a></li>
					<li class="{!! setActive('players*') !!}"><a href="/players">Players</a></li>
					<li class="{!! setActive('scrapers*') !!}"><a href="/scrapers">Scrapers</a></li>
					<li class="{!! setActive('admin*') !!}"><a href="/admin">Admin</a></li>
					<li class="{!! setActive('nbawowy*') !!}"><a href="/nbawowy">nbawowy!</a></li>
					<li class="{!! setActive('studies*') !!}"><a href="/studies">Studies</a></li>
				</ul>
			</div><!--/.nav-collapse -->
		</div>
    </div>
	<div class="container">
		@yield('content')
	</div>
</body>
</html>