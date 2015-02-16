@extends('master')

@section('content')

	<div class="row">
		<div class="col-lg-12">
			<h2>Teams ({{ $name }}</a>)</h2>

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