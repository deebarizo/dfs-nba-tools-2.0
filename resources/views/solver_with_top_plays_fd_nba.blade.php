@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD NBA (Solver With Top Plays) | {{ $date }} {{ $timePeriod }}</h2>

			<p>
				<strong>Buy In: </strong> 
				$<span class="buy-in-amount">{{ $buyIn }}</span>
				(<a href="#" class="edit-buy-in-link">Edit</a>) 

				<span style="margin-left: 20px">
					<strong>Unspent Buy In: </strong>
					$<span class="unspent-buy-in-amount">{{ $unspentBuyIn }}</span>
				</span>
			</p>

			<div class="input-group edit-buy-in form-hidden" style="width: 20%; margin-bottom: 10px">
				<div class="input-group-addon">$</div>
			   	<input type="text" class="form-control edit-buy-in-input" value="{{ $buyIn }}">
			   	<span class="input-group-btn">
			    	<button class="btn btn-default edit-buy-in-button" type="button">Submit</button>
			   	</span>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6">
			<h4>Player Percentages</h4>

			<div id="player-percentages-container" style="width:100%; height:700px; padding-right:30px"></div>
		</div>

		<div class="col-lg-6" style="overflow-y: scroll; height: 800px">
			<h4>Lineups</h4>

			@foreach ($lineups as $lineup)
				<table data-player-pool-id="{{ $playerPoolId }}" 
					   data-hash="{{ $lineup['hash'] }}" 
					   data-total-salary="{{ $lineup['total_salary'] }}" 
					   class="table table-striped table-bordered table-hover table-condensed {{ $lineup['css_class_blue_border'] }}">
					<thead>
						<tr>
							<th style="width: 15%">Pos</th>
							<th style="width: 55%">Name</th>
							<th style="width: 30%">Sal</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($lineup['roster_spots'] as $rosterSpot)
							<tr class="roster-spot">
								<td>{{ $rosterSpot->position }}</td>
								<td class="roster-spot-name">{{ $rosterSpot->name }}</td>
								<td>{{ $rosterSpot->salary }}</td>
							</tr>
						@endforeach

						<tr class="update-lineup-row">
							<td class="update-lineup-td" style="text-align: center" colspan="2">
								<span class="edit-lineup-buy-in {{ $lineup['css_class_edit_info'] }}">
									$<span class="lineup-buy-in-amount">{{ $lineup['buy_in'] }}</span> 
									(<span class="lineup-buy-in-percentage">{{ $lineup['buy_in_percentage'] }}</span>%) | 
									<a href="#" class="edit-lineup-buy-in-link">Edit</a> | 
								</span>
								<a href="#" class="add-or-remove-lineup-link"><span class="add-or-remove-lineup-anchor-text">{{ $lineup['anchor_text'] }}</span></a>
								<span class="add-or-remove-lineup-link-loading-gif">
									<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />
								</span>
							</td>
							<td style="color: green"><strong>{{ $lineup['total_salary'] }}</strong></td>
						</tr>
					</tbody>
				</table>

				<div class="input-group edit-lineup-buy-in-amount edit-lineup-buy-in-amount-hidden" style="width: 45%; margin: -12px auto 20px auto">
					<div class="input-group-addon">$</div>
				   	<input type="text" class="form-control edit-lineup-buy-in-input" value="{{ $lineup['buy_in'] }}">
				   	<span class="input-group-btn">
				    	<button class="btn btn-default edit-lineup-buy-in-button" type="button">Submit</button>
				   	</span>
				</div>
			@endforeach	
	</div>

	<script>
		$(document).ready(function() {

			var playerPoolId = <?php echo $playerPoolId; ?>;
			var buyIn = $("span.buy-in-amount").text();


			/********************************************
			EDIT BUY IN
			********************************************/

			$(".edit-buy-in-link").click(function(e) {
				e.preventDefault();

				$(".edit-buy-in").toggleClass("form-hidden");
			});

			$(".edit-buy-in-button").click(function(e) {
				e.preventDefault();

				buyIn = $(this).parent().prev('input.edit-buy-in-input').val();

				$.ajax({
		            url: '<?php echo url(); ?>/solver_top_plays/update_buy_in/'+playerPoolId+'/'+buyIn,
		            type: 'POST',
		            success: function() {
		            	$(".edit-buy-in").addClass("form-hidden");

		            	$("span.buy-in-amount").text(buyIn).fadeIn();
		            }
		        }); 				
			});


			/********************************************
			EDIT LINEUP BUY IN
			********************************************/

			$(".edit-lineup-buy-in-link").click(function(e) {
				e.preventDefault();

				console.log('test');

				$(this).parent().parent().parent().parent().parent().next().toggleClass("edit-lineup-buy-in-amount-hidden");
			});

			$(".edit-lineup-buy-in-button").click(function(e) {
				e.preventDefault();

				var lineupBuyIn = $(this).parent().prev().val();
				var lineupBuyInPercentage = lineupBuyIn / buyIn * 100;
				lineupBuyInPercentage = lineupBuyInPercentage.toFixed(2);

				var hash = $(this).parent().parent().prev().data('hash');

				// console.log(test); return false;

				var $this = $(this);

		    	$.ajax({
		            url: '<?php echo url(); ?>/solver_top_plays/update_lineup_buy_in/'+playerPoolId+'/'+hash+'/'+lineupBuyIn,
		            type: 'POST',
		            success: function() {
		            	$this.parent().parent().addClass("edit-lineup-buy-in-amount-hidden");

		            	$this.parent().parent().prev().children('tbody').children('tr.update-lineup-row').children('td.update-lineup-td').children('span.edit-lineup-buy-in').children('span.lineup-buy-in-amount').text(lineupBuyIn);
		            	$this.parent().parent().prev().children('tbody').children('tr.update-lineup-row').children('td.update-lineup-td').children('span.edit-lineup-buy-in').children('span.lineup-buy-in-percentage').text(lineupBuyInPercentage);

		            	updateUnspentBuyIn();
		            	drawBarChart();
		            }
		        }); 				
			});


			/********************************************
			ADD OR REMOVE LINEUP
			********************************************/

			$(".add-or-remove-lineup-link").click(function(e) {
				e.preventDefault();

				var buyIn = $("span.buy-in-amount").text();

				if (buyIn == 0) {
					alert("Please enter a buy in.");

					return false;
				}

				var lineupBuyIn = Math.round(buyIn * 0.20);
				var lineupBuyInPercentage = lineupBuyIn / buyIn * 100;
				lineupBuyInPercentage = lineupBuyInPercentage.toFixed(2);

				var addOrRemove = $(this).children(".add-or-remove-lineup-anchor-text").text();

				switch(addOrRemove) {
				    case "Add":
						var lineups = [];
						lineups = <?php echo json_encode($lineups); ?>;
				        break;
				    case "Remove":
						var lineups = [];
				        break;
				}

				$(this).children(".add-or-remove-lineup-anchor-text").text('');
				$(this).next(".add-or-remove-lineup-link-loading-gif").show();

				var hash = $(this).parent().parent().parent().parent().data('hash');
				var totalSalary = $(this).parent().parent().parent().parent().data('total-salary');
				var $this = $(this);

				var Rand = Math.floor((Math.random() * 100000) + 1);

		    	$.ajax({
		            url: '<?php echo url(); ?>/solver_top_plays/add_or_remove_lineup/'+playerPoolId+'/'+hash+'/'+totalSalary+'/'+lineupBuyIn+'/'+addOrRemove+'/'+Rand,
		           	data: { 'lineups': lineups },
		           	type: 'post',
		            success: function(output) {
						$this.parent().parent().parent().parent().toggleClass("active-lineup");	
						$this.prev().toggleClass("edit-lineup-buy-in-hidden");	
						$this.next(".add-or-remove-lineup-link-loading-gif").hide();

						switch(addOrRemove) {
						    case "Add":
						    	$this.prev().children('.lineup-buy-in-amount').text(lineupBuyIn);
						    	$this.prev().children('.lineup-buy-in-percentage').text(lineupBuyInPercentage);
								$this.parent().parent().parent().parent().next().children(".edit-lineup-buy-in-input").val(lineupBuyIn);								
								
								$this.children(".add-or-remove-lineup-anchor-text").text("Remove");

						        break;
						    
						    case "Remove":
						    	$this.parent().parent().parent().parent().next().addClass("edit-lineup-buy-in-amount-hidden");

						        $this.children(".add-or-remove-lineup-anchor-text").text("Add");

						        break;
						}

						updateUnspentBuyIn();
						drawBarChart();
		            },
		            error: function(request, status, error) {
		            	alert(request+' '+status+' '+error);
		            }
		        }); 
			});


			/********************************************
			UPDATE UNSPENT BUY IN
			********************************************/

			function updateUnspentBuyIn() {
				var buyIn = $("span.buy-in-amount").text();
				buyIn = parseInt(buyIn);

				var spentBuyIn = 0;
				spentBuyIn = parseInt(spentBuyIn);

				$(".active-lineup").each(function() {
					var lineupBuyIn = $(this).find("span.lineup-buy-in-amount").text();	

					spentBuyIn += parseInt(lineupBuyIn);
				});

				var unspentBuyIn = buyIn - spentBuyIn;

				$("span.unspent-buy-in-amount").text(unspentBuyIn);
			}


			/********************************************
			DRAW BAR CHART
			********************************************/

			var areThereActiveLineups = <?php echo $areThereActiveLineups; ?>;

			if (areThereActiveLineups == 1) {
				drawBarChart();
			}

			if (areThereActiveLineups == 0) {
				$('#player-percentages-container').text("There are no active lineups.");
			}

			function drawBarChart() {
				var numActiveLineups = $(".active-lineup").length; 

				if (numActiveLineups == 0) {
					$('#player-percentages-container').text("There are no active lineups.");

					return true;
				}

				var rosterSpotsInActiveLineups = [];
				var playersInActiveLineups = [];

				$(".active-lineup").each(function() {
					var lineupBuyIn = $(this).find("span.lineup-buy-in-amount").text();

					$(this).children("tbody").children("tr.roster-spot").find("td.roster-spot-name").each(function() {
						var name = $(this).text();

						var rosterSpot = { 
							name: name, 
							lineupBuyIn: lineupBuyIn 
						};

						rosterSpotsInActiveLineups.push(rosterSpot);

						playersInActiveLineups.push(name);
					});
				});

				function arrayUnique(arr) {
				    var a = [];
				    for (var i=0, l=arr.length; i<l; i++)
				        if (a.indexOf(arr[i]) === -1 && arr[i] !== '')
				            a.push(arr[i]);
				    return a;
				}

				playersInActiveLineups = arrayUnique(playersInActiveLineups);

				var players = [];

				for (var i = 0; i < playersInActiveLineups.length; i++) {
					players[i] = {};

					players[i]['name'] = playersInActiveLineups[i];
					var totalBuyInOfPlayer = 0;

					for (var n = 0; n < rosterSpotsInActiveLineups.length; n++) {
						if (players[i]['name'] == rosterSpotsInActiveLineups[n]['name']) {
							totalBuyInOfPlayer += parseInt(rosterSpotsInActiveLineups[n]['lineupBuyIn']);
						} 
					};

					var percentage = totalBuyInOfPlayer / buyIn * 100;
					percentage = parseInt(percentage);

					players[i]['percentage'] = percentage;
				};

				players.sort(function(a,b) {
				    return b.percentage - a.percentage;
				});

				var playerNames = [];
				var percentages = [];

				for (var i = 0; i < players.length; i++) {
					playerNames.push(players[i]['name']);
					percentages.push(players[i]['percentage']);
				};

			    $('#player-percentages-container').highcharts({
			        chart: {
			            type: 'bar'
			        },
			        title: {
			        	text: null
			        },
			        xAxis: {
			            categories: playerNames,
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
			            data: percentages
			        }],
			        legend: {
			        	enabled: false
			        }
			    });				
			}				

		});
	</script>
@stop