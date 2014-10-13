@extends('master')

@section('content')
	<h2>Histogram - Scores</h2>

	<div id="container" style="width:100%; height:800px;"></div>

	<script>
		$(function () {
		    $('#container').highcharts({
		        title: {
		            text: 'Scores'
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
		                text: 'Quantity'
		            },
		            min: 0
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
		                    pointFormat: '{point.x} Score, {point.y} Quantity'
		                }
		            }
		        },
		        series: [{
		        	type: 'line',
		        	name: 'Score',
		            data: <?php echo json_encode($histogram); ?>
		        }]
		    });
		});
	</script>
@stop