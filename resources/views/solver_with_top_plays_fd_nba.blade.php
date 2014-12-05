@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD NBA (Solver With Top Plays) | {{ $date }} {{ $timePeriod }}</h2>

			<p>
				<strong>Buy In: </strong> 
				$<span class="buy-in-amount">{{ $buyIn }}</span>
				(<a href="#" class="edit-buy-in-link">Edit</a>) 
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

			<div id="player-percentages-container" style="width:100%; height:700px; padding-right: 70px"></div>
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
							<tr>
								<td>{{ $rosterSpot->position }}</td>
								<td>{{ $rosterSpot->name }}</td>
								<td>{{ $rosterSpot->salary }}</td>
							</tr>
						@endforeach

						<tr>
							<td style="text-align: center" colspan="2">
								<span class="edit-lineup-buy-in {{ $lineup['css_class_edit_info'] }}">
									$<span class="edit-lineup-buy-in-amount">24</span> 
									(<span class="edit-lineup-buy-in-percentage">20</span>%) | 
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
			@endforeach	
	</div>

	<script>
		$(document).ready(function() {
			var playerPoolId = <?php echo $playerPoolId; ?>;

			$(".edit-buy-in-link").click(function(e) {
				e.preventDefault();

				$(".edit-buy-in").toggleClass("form-hidden");
			});

			$(".edit-buy-in-button").click(function(e) {
				e.preventDefault();

				var buyIn = $("span.buy-in-amount").text();

		    	$.ajax({
		            url: '<?php echo url(); ?>/solver_top_plays/update_buy_in/'+playerPoolId+'/'+buyIn,
		            type: 'POST',
		            success: function() {
		            	$(".edit-buy-in").addClass("form-hidden");

		            	$("span.buy-in-amount").text(buyIn).fadeIn();
		            }
		        }); 				
			});

			$(".add-or-remove-lineup-link").click(function(e) {
				e.preventDefault();

				var buyIn = $("span.buy-in-amount").text();

				if (buyIn == 0) {
					alert("Please enter a buy in.");

					return false;
				}

				var lineupBuyIn = Math.round(buyIn * 0.20);

				var addOrRemove = $(this).children(".add-or-remove-lineup-anchor-text").text();

				switch(addOrRemove) {
				    case "Add":
						var lineups = <?php echo json_encode($lineups); ?>;
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

		    	$.ajax({
		            url: '<?php echo url(); ?>/solver_top_plays/add_or_remove_lineup/'+playerPoolId+'/'+hash+'/'+totalSalary+'/'+lineupBuyIn+'/'+addOrRemove,
		            type: 'POST',
		            data: {lineups: lineups},
		            success: function() {
						$this.parent().parent().parent().parent().toggleClass("active-lineup");	
						$this.prev().toggleClass("edit-lineup-buy-in-hidden");	
						$this.next(".add-or-remove-lineup-link-loading-gif").hide();

						switch(addOrRemove) {
						    case "Add":
								$this.children(".add-or-remove-lineup-anchor-text").text("Remove");
						        break;
						    case "Remove":
						        $this.children(".add-or-remove-lineup-anchor-text").text("Add");
						        break;
						}
		            }
		        }); 
			});
		});
	</script>
@stop