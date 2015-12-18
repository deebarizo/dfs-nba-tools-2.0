@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers</h2>

			<h3>NBA</h3>

			<ul>
				<li><a href="/scrapers/fd_nba_salaries">FD Salaries</a></li>
				<li><a href="/scrapers/br_nba_games">BR Games</a></li>
				<li><a href="/scrapers/br_nba_box_score_lines">BR Box Score Lines</a></li>
				<li><a href="/scrapers/dk_nba_ownerships">DK Ownerships</a></li>
			</ul>

			<h3>MLB</h3>

			<ul>
				<li><a href="/scrapers/dk_mlb_salaries">DK Salaries</a></li>
				<li><a href="/scrapers/bat_mlb_projections">BAT Projections</a></li>
				<li><a href="/scrapers/fg_mlb_box_score_lines">FG Box Score Lines</a></li>
				<li><a href="/scrapers/dk_mlb_contests">DK Contests</a></li>
			</ul>			
		</div>
	</div>
@stop