@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Solver FD NBA | {{ $date }} {{ $timePeriod }}</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6">
			<h4>Player Percentages</h4>

			<div id="player-percentages-container" style="width:100%; height:800px"></div>
		</div>

		<div class="col-lg-6" style="overflow-y: scroll; height: 800px">
			<h4>Lineups</h4>

			@foreach ($lineups as $lineupIndex => $lineup)
				<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
					<thead>
						<tr>
							<th>Pos</th>
							<th>Name</th>
							<th>Sal</th>
							<th>FP</th>
						</tr>
					</thead>
					
					<tbody>
						@foreach ($lineup as $key => $rosterSpot)
							@if (is_numeric($key))
								<tr>
									<td>{{ $rosterSpot->position }}</td>
									<td>{{ $rosterSpot->name }}</td>
									<td>{{ $rosterSpot->salary }}</td>
									<td>{{ $rosterSpot->fppg_minus1 }}</td>
								</tr>
							@endif
						@endforeach
						<tr>
							<td style="text-align: center" colspan="2">{{ $lineupIndex + 1 }}</td>
							<td>{{ $lineup['salary_total'] }}</td>
							<td style="color: green;"><strong>{{ numFormat($lineup['fppg_minus1_total'], 2) }}</strong></td>
						</tr>				
					</tbody>
				</table>
			@endforeach
		</div>
	</div>

<script>
	$(function () {
	    $('#player-percentages-container').highcharts({
	        chart: {
	            type: 'bar'
	        },
	        title: {
	        	text: null
	        },
	        xAxis: {
	            categories: <?php echo json_encode($playersInTopLineups); ?>,
	            labels: {
	            	step: 1
	            }
	        },
	        yAxis: {
	            min: 0,
	            title: {
	                text: 'Percentage'
	            },
	            max: 100
	        },
	        tooltip: {
	            valueSuffix: '%'
	        },
	        plotOptions: {
	            bar: {
	                dataLabels: {
	                    enabled: true
	                },
	                pointWidth: 20,
	                pointPadding: 0
	            }
	        },
	        credits: {
	            enabled: false
	        },
	        series: [{
	        	name: 'Percentage',
	            data: <?php echo json_encode($percentagesInTopLineups); ?>
	        }],
	        legend: {
	        	enabled: false
	        }
	    });
	});	


</script>

@stop