@extends('master')

@section('content')
	<h2>Correlation - Scores and Vegas Scores</h2>

	Correlation: {{ $data['correlation'] }}

	<div id="container" style="width:100%; height:800px;"></div>

	<script>
		$(function () {
		    $('#container').highcharts({
		        chart: {
		            type: 'scatter',
		            zoomType: 'xy'
		        },
		        title: {
		            text: 'Scores vs Vegas Scores'
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
		                text: 'Vegas Scores'
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
		                    pointFormat: '{point.x} Score, {point.y} Vegas Score'
		                }
		            }
		        },
		        series: [{
		        	name: 'Actual Results',
		            data: <?php echo json_encode($data['dataSetsJSON']); ?>
		        }, {
		        	name: 'Perfect Correlation',
		        	data: <?php echo json_encode($data['perfectLineJSON']); ?>
		        }]
		    });
		});
	</script>
@stop