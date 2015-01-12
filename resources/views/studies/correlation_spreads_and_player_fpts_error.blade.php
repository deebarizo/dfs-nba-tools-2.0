@extends('master')

@section('content')
	<div class="row">
		<div class=".col-lg-12">
			<h2>Correlation - {{ $data['xVar'] }} and {{ $data['yVar'] }}</h2>

			<p><strong>Correlation:</strong> {{ $data['correlation'] }}</p>
			<p><strong>{{ $data['subhead1'] }}</strong> {{ $data['subhead2'] }}</p>

			<div id="container" style="width:100%; height:800px;"></div>

			<script>
				$(function () {
				    $('#container').highcharts({
				        title: {
				            text: '<?php echo $data["xVar"]; ?> and <?php echo $data["yVar"]; ?>'
				        },
				        xAxis: {
				            title: {
				                enabled: true,
				                text: '<?php echo $data["xVar"]; ?>'
				            },
				            startOnTick: true,
				            endOnTick: true,
				            showLastLabel: true
				        },
				        yAxis: {
				            title: {
				                text: '<?php echo $data["yVar"]; ?>'
				            }
				        },
				        plotOptions: {
				            scatter: {
				                marker: {
				                    radius: 3,
				                    states: {
				                        hover: {
				                            enabled: true,
				                            lineColor: 'rgb(100,100,100)'
				                        }
				                    }
				                },
				                states: {
				                    hover: {
				                        marker: {
				                            enabled: false
				                        }
				                    }
				                },
				                tooltip: {
				                    pointFormat: '{point.x} <?php echo $data["xVar"]; ?>, {point.y} <?php echo $data["yVar"]; ?>'
				                }
				            }
				        },
				        series: [{
				        	type: 'scatter',
				        	name: 'Actual Results',
				            data: <?php echo json_encode($data['dataSetsJSON']); ?>
				        }, {
				            type: 'scatter',
				            name: 'Line of Best Fit',
				            data: <?php echo json_encode($data['lineOfBestFitJSON']); ?>,
				        }]
				    });
				});
			</script>
		</div>
	</div>
@stop