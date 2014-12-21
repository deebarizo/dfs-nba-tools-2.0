@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD NBA (Solver With Top Plays) | {{ $date }} {{ $timePeriod }}</h2>
		</div>

		<div class="col-lg-4">
			<p>
				<strong>Buy In: </strong> 
				$<span class="buy-in-amount">{{ $buyIn }}</span>
				[<a href="#" class="edit-buy-in-link">Edit</a>]

				<span style="margin-left: 20px">
					<strong>Unspent Buy In: </strong>
					$<span class="unspent-buy-in-amount">{{ $unspentBuyIn }}</span>
				</span>
			</p>

			<div class="input-group edit-buy-in form-hidden" style="width: 65%; margin-bottom: 10px">
				<div class="input-group-addon">$</div>
			   	<input type="text" class="form-control edit-buy-in-input" value="{{ $buyIn }}">
			   	<span class="input-group-btn">
			    	<button class="btn btn-default edit-buy-in-button" type="button">Submit</button>
			   	</span>
			</div>
		</div>

		<div class="col-lg-4">
			<p>
				<strong>Default Lineup Buy In: </strong> 
				$<span class="default-lineup-buy-in-amount">{{ $defaultLineupBuyIn }}</span> 
				(<span class="default-lineup-buy-in-percentage">{{ numFormat($defaultLineupBuyIn / $buyIn * 100, 2) }}</span>%)
				[<a href="#" class="edit-default-lineup-buy-in-link">Edit</a>]
			</p>

			<div class="input-group edit-default-lineup-buy-in form-hidden" style="width: 65%; margin-bottom:10px">
				<div class="input-group-addon">$</div>
			   	<input type="text" class="form-control edit-default-lineup-buy-in-input" value="{{ $defaultLineupBuyIn }}">
			   	<span class="input-group-btn">
			    	<button class="btn btn-default edit-default-lineup-buy-in-button" type="button">Submit</button>
			   	</span>
			</div>
		</div>

		<div class="col-lg-12">
			<form class="form-inline" style="margin: 5px 20px 10px 0">
				<label>Show Lineups</label>
				<select class="form-control lineup-type-filter">
				  	<option value="All">All</option>
				  	<option value="Only Non Active">Only Non Active</option>
				  	<option value="Only Active">Only Active</option>
				</select>
			</form>
		</div>
	</div>

	<hr>

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
					   class="table 
					   		  table-striped 
					   		  table-bordered 
					   		  table-hover 
					   		  table-condensed 
					   		  {{ $lineup['css_class_blue_border'] }} 
					   		  {{ $lineup['css_class_money_lineup'] }}">
					<thead>
						<tr>
							<th style="width: 15%">Pos</th>
							<th style="width: 55%">Name</th>
							<th style="width: 30%">Sal</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($lineup['roster_spots'] as $rosterSpot)
							<tr class="roster-spot" data-player-id="{{ $rosterSpot->player_id }}">
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
									<a href="#" class="play-or-unplay-lineup-link"><span class="play-or-unplay-lineup-anchor-text">{{ $lineup['play_or_unplay_anchor_text'] }}</span></a> | 
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
			
		/********************************************
		GLOBAL VARIABLES
		********************************************/

		var playerPoolId = <?php echo json_encode($playerPoolId); ?>;
		var buyIn = $("span.buy-in-amount").text();
		var defaultLineupBuyIn = $("span.default-lineup-buy-in-amount").text();
		var areThereActiveLineups = <?php echo $areThereActiveLineups; ?>;
		var baseUrl = '<?php echo url(); ?>';

	</script>

	<script src="/js/solver_top_plays.js"></script>
@stop