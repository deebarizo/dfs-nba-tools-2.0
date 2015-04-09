@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers</h2>

			<h3>NBA</h3>

			<ul>
				<li><a target="_blank" href="/scrapers/br_nba_games">BR Games</a></li>
				<li><a target="_blank" href="/scrapers/br_nba_box_score_lines">BR Box Score Lines</a></li>
				<li><a target="_blank" href="/scrapers/fd_nba_salaries">FD Salaries</a></li>
			</ul>

			<h3>MLB</h3>

			<ul>
				<li><a target="_blank" href="/scrapers/fg_mlb_box_score_lines">FG Box Score Lines</a></li>
				<li><a target="_blank" href="/scrapers/dk_mlb_salaries">DK Salaries</a></li>
			</ul>			
		</div>
	</div>
@stop