@extends('master')

@section('content')
	<div class="row">
		<div class=".col-lg-12">
			<h2>Correlation - Scores and FD Scores</h2>

			<p><strong>Correlation:</strong> {{ $data['correlation'] }}</p>
			<p><strong>Calculate Predicted FD Score:</strong> {{ $data['calculatePredictedFDScore']  }}</p>

			<div id="container" style="width:100%; height:800px;"></div>

			<script>
				$(function () {
				    $('#container').highcharts({
				        title: {
				            text: 'Scores and FD Scores'
				        },
				        xAxis: {
				            title: {
				                enabled: true,
				                text: 'Scores'
				            },
				            startOnTick: true,
				            endOnTick: true,
				            showLastLabel: true
				        },
				        yAxis: {
				            title: {
				                text: 'FD Scores'
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
				                    pointFormat: '{point.x} Score, {point.y} FD Score'
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
				        }, {
				        	type: 'scatter',
				        	name: 'Perfect Correlation',
				        	data: <?php echo json_encode($data['perfectLineJSON']); ?>
				        }]
				    });
				});
			</script>
		</div>
	</div>
@stop