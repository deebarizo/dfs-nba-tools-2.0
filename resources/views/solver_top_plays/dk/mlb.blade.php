@extends('master')

@section('content')
	<?php 
		$playerTypeFilter['all'] = '';
		$playerTypeFilter['pitchers'] = 'selected';
		$playerTypeFilter['hitters'] = '';
	?>

	<div class="row" style="font-size: 90%">
		<div class="col-lg-12">
			<h2 id="heading">DK MLB | Solver | {{ $date }} {{ $timePeriod }} | <a target="_blank" href="/daily/dk/mlb/{{ $timePeriodInUrl }}/{{ $date }}/nc">Daily</a> | <a target="_blank" href="/lineup_builder/dk/mlb/{{ $timePeriodInUrl }}/{{ $date }}">Lineup Builder</a></h2>
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

			<div class="input-group edit-buy-in form-hidden" style="width: 80%; margin-bottom: 10px">
				<div class="input-group-addon">$</div>
			   	<input type="text" class="form-control edit-buy-in-input" value="{{ $buyIn }}">
			   	<span class="input-group-btn">
			    	<button class="btn btn-default edit-buy-in-button" type="button">Submit</button>
			   	</span>
			</div>
		</div>

		<div class="col-lg-3">
			<p>
				<strong>Default Lineup Buy In: </strong> 
				$<span class="default-lineup-buy-in-amount">{{ $defaultLineupBuyIn }}</span> 
				(<span class="default-lineup-buy-in-percentage">@if ($buyIn != 0){{ numFormat($defaultLineupBuyIn / $buyIn * 100, 2) }}@else{{0.00}}@endif</span>%)
				[<a href="#" class="edit-default-lineup-buy-in-link">Edit</a>]
			</p>

			<div class="input-group edit-default-lineup-buy-in form-hidden" style="width: 80%; margin-bottom:10px">
				<div class="input-group-addon">$</div>
			   	<input type="text" class="form-control edit-default-lineup-buy-in-input" value="{{ $defaultLineupBuyIn }}">
			   	<span class="input-group-btn">
			    	<button class="btn btn-default edit-default-lineup-buy-in-button" type="button">Submit</button>
			   	</span>
			</div>
		</div>

		<div class="col-lg-4">
			<p>
				<strong>Navbar:</strong> <a style="margin-right: 50px" class="toggle-navbar-link" href="">Toggle</a> <strong>Players:</strong> <a class="clear-selected-players-link" href="">Clear All</a> | <a class="hide-spent-players-link" href="">Hide Spent</a>
			</p>
		</div>
	</div>

	<div class="row" style="font-size: 90%">
		<div style="width: 525px" class="col-lg-4">
			<form class="form-inline" style="margin: 5px 0 10px 0; display: inline-block">
				<div style="font-size: 130%; font-weight: bold; display: inline-block; width: 60px">Show:</div>

				<label>Lineups</label>
				<select class="form-control lineup-type-filter" style="margin-right: 20px">
				  	<option value="All">All</option>
				  	<option value="Only Non Active">Only Non Active</option>
				  	<option value="Only Active">Only Active</option>
				</select>

				<label>Players</label>
				<select class="form-control player-filter show-player-filter" style="width: 150px">
				  	<option value="Default" selected>-</option>
				  	@foreach ($players as $player)
				  		<option value="{{ $player->mlb_player_id }}">{{ $player->name }}</option>
				  	@endforeach
				</select>
			</form>
		</div>

		<div style="padding-left: 0" class="col-lg-6">
			<span style="display: inline-block; margin-top: 12px" class="selected-players selected-players-to-show"></span>
		</div>
	</div>

	<div class="row" style="font-size: 90%">
		<div style="width: 525px" class="col-lg-4">
			<form class="form-inline" style="margin: 5px 0 10px 0; display: inline-block">
				<div style="font-size: 130%; font-weight: bold; display: inline-block; width: 60px">Hide:</div>

				<div style="display: inline-block; width: 226px"></div>

				<label>Players</label>
				<select class="form-control player-filter hide-player-filter" style="width: 150px">
				  	<option value="Default" selected>-</option>
				  	@foreach ($players as $player)
				  		<option value="{{ $player->mlb_player_id }}">{{ $player->name }}</option>
				  	@endforeach
				</select>
			</form>
		</div>

		<div style="padding-left: 0" class="col-lg-6">
			<span style="display: inline-block; margin-top: 12px" class="selected-players selected-players-to-hide"></span>
		</div>
	</div>

	<hr>

	<div class="row">
		<div class="col-lg-7">
			<h4>Player Percentages</h4>

			<form class="form-inline" style="font-size: 90%; margin: 15px 0 10px 0">
				<label>Sort</label>
				<select class="form-control player-percentages-filter" style="margin-right: 20px">
					<option value="Unspent Target Percentage (Desc)">Unspent Target Percentage (Desc)</option>
					<option value="Unspent Target Percentage (Asc)">Unspent Target Percentage (Asc)</option>
				  	<option value="Target Percentage">Target Percentage</option>
				  	<option value="Actual Percentage" selected>Actual Percentage</option>
				  	<option value="First Name">First Name</option>
				  	<option value="Position">Position</option>
				  	<option value="Team">Team</option>
				  	<option value="Salary">Salary</option>
				</select>
			</form>

			<form class="form-inline" style="font-size: 90%; margin: 15px 0 10px 0">
				<label>Types</label>
				<select class="form-control player-types-show-filter" style="margin-right: 20px">
					<option value="All" {{ $playerTypeFilter['all'] }}>All</option>
				  	<option value="Only Pitchers" {{ $playerTypeFilter['pitchers'] }}>Pitchers</option>
				  	<option value="Only Hitters" {{ $playerTypeFilter['hitters'] }}>Hitters</option>
				</select>

				<label>Percentages</label>
				<select class="form-control player-percentages-show-filter">
					<option value="All" selected>All</option>
				  	<option value="Only Actual Percentage">Actual Percentage</option>
				</select>
			</form>

			<div id="player-percentages-container" style="width:100%; padding-right:30px"></div>
		</div>

		<div class="col-lg-5" style="overflow-y: scroll; height: 2000px">
			<h4>Lineups {{ $sorter }}</h4>

			@foreach ($lineups as $lineup)
				<table style="font-size: 85%"
					   data-player-pool-id="{{ $playerPoolId }}" 
					   data-hash="{{ $lineup['hash'] }}" 
					   data-salary="{{ $lineup['salary'] }}" 
					   class="table 
					   		  table-striped 
					   		  table-bordered 
					   		  table-hover 
					   		  table-condensed 
					   		  lineup 
					   		  {{ $lineup['css_class_active_lineup'] }} 
					   		  {{ $lineup['css_class_money_lineup'] }}">
					<thead>
						<tr>
							<th style="width: 10%">Pos</th>
							<th style="width: 15%">Team</th>
							<th style="width: 45%">Name</th>
							<th style="width: 15%">Sal</th>
							<th style="width: 15%">bFPTS</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($lineup['players'] as $key => $player)
							<tr class="roster-spot" 
								data-id="{{ $player->mlb_player_id }}"
								data-target-percentage="{{ $player->target_percentage }}" 
								data-team-abbr="{{ $player->abbr_dk }}"
								data-position="{{ $player->position }}"
								data-name="{{ $player->name }}"
								data-salary="{{ $player->salary }}"
								data-bat-fpts="{{ $player->bat_fpts }}">
								<td>{{ $player->position }}</td>	
								<td>{{ $player->abbr_dk }}</td>
								<td>{{ $player->name }}</td>
								<td>{{ $player->salary }}</td>
								<td>{{ $player->bat_fpts }}</td>
							</tr>
						@endforeach
						<tr class="update-lineup-row">
							<td class="update-lineup-td" style="text-align: center" colspan="3">
								<span class="edit-lineup-buy-in {{ $lineup['css_class_edit_info'] }}">
									$<span class="lineup-buy-in-amount">{{ $lineup['buy_in'] }}</span> 
									(<span class="lineup-buy-in-percentage">{{ $lineup['buy_in_percentage'] }}</span>%) | 
									<a href="#" class="edit-lineup-buy-in-link">Edit</a> | 
									<a href="#" class="play-or-unplay-lineup-link"><span class="play-or-unplay-lineup-anchor-text">{{ $lineup['play_or_unplay_anchor_text'] }}</span></a> | <a href="{{ url() }}/lineup_builder/dk/mlb/{{ $timePeriodInUrl }}/{{ $date }}/{{ $lineup['hash'] }}" target="_blank"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> | 
								</span>
								<a href="#" class="add-or-remove-lineup-link"><span class="add-or-remove-lineup-anchor-text">{{ $lineup['add_or_remove_anchor_text'] }}</span></a>
								<span class="add-or-remove-lineup-link-loading-gif">
									<img src="/files/spiffygif_16x16.gif" alt="Please wait..." />
								</span>
							</td>
							<td style="color: green"><strong>{{ $lineup['salary'] }}</strong></td>
							<td><strong>{{ $lineup['total_bat_fpts'] }}</strong></td>
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

		/****************************************************************************************
		HIDE NAVBAR
		****************************************************************************************/

		// $(".navbar").hide();
		// $("h2").css({"margin-top":"10px"});

			
		/****************************************************************************************
		GLOBAL VARIABLES
		****************************************************************************************/

		var baseUrl = '<?php echo url(); ?>';
		var playerPoolId = <?php echo json_encode($playerPoolId); ?>;
		var buyIn = $("span.buy-in-amount").text();
		var defaultLineupBuyIn = $("span.default-lineup-buy-in-amount").text();
		var topPlays = <?php echo json_encode($players); ?>;

	</script>

	<script src="/js/solver_top_plays/dk/mlb.js"></script>
@stop