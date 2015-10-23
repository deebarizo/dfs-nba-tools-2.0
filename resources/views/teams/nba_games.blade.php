@extends('master')

@section('content')

	<div class="row">
		<div class="col-lg-12">
			<h2>NBA Teams ({{ $name }}</a>)</h2>

			<table class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Date</th>
						<th>Matchup</th>
						<th>Result</th>
						<th>Line</th>
						<th>Links</th>
						<th>Ot</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($games as $game)
						<tr>
							<td>{{ $game->date }}</td>
							<td>{{ $game->matchup }}</td>
							<td>{!! $game->result !!}</td>
							<td>{{ $game->line }}</td>
							<td>{!! $game->links !!}</td>
							<td>{!! $game->ot !!}</td>
						</tr>
					@endforeach
				</tbody>
			</table>

			<div class="opp-team-fpts-profile-chart"></div>
		</div>
	</div>

	<script>

		$(document).ready(function() {
			$(function () {
			        $('.opp-team-fpts-profile-chart').highcharts({
			            chart: {
			                type: 'column'
			            },
			            title: {
			                text: '<?php echo $endYear; ?> Opp Team Fpts Profile'
			            },
			            xAxis: {
			                categories: ['2P', '3P', 'FT', 'TRB', 'ORB', 'DRB', 'AST', 'TO', 'STL', 'BLK']
			            },
			            yAxis: {min: -50, max: 50},
			            credits: {
			                enabled: false
			            },
			            legend: false,
			            series: [{
		                	data: <?php echo json_encode($thisTeamPercentages); ?>
		                }],
		                plotOptions: {
		                	column: {colorByPoint: true}
		                }
			        });
			    });
		});

	</script>

@stop