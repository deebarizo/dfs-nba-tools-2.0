@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>{{ $lineup['h2_tag'] }}</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $lineup['sub_heading'] }}</h3>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6">
			<h4>Players in Player Pool</h4>

			<table style="font-size: 85%" id="available-players" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Pos</th>					
						<th>Name</th>
						<th>Team</th>
						<th>Opp</th>
						<th>Salary</th>
						<th>bFPTS</th>
						<th>Update</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($players as $player)
						<tr class="available-player-row {{ $player->strikethrough_css_class }}" 
							data-player-pool-id="{{ $player->player_pool_id }}" 
							data-player-id="{{ $player->mlb_player_id }}" 
							data-team="{{ $player->abbr_dk }}" 
							data-opp="{{ $player->opp_abbr_dk }}"
							data-bat-fpts="{{ $player->bat_fpts }}">
							<td class="available-player-position">{{ $player->position }}</td>
							<td class="available-player-name">{{ $player->name }}</td>
							<td class="available-player-team">{{ $player->abbr_dk }}</td>
							<td class="available-player-opp">{{ $player->opp_abbr_dk }}</td>
							<td class="available-player-salary">{{ $player->salary }}</td>
							<td>{{ $player->bat_fpts }}</td>
							<td class="available-player-update" style="width: 10%"><a class="update-available-player-link" href="">{!! $player->update_icon !!}</a></td>
						</tr>		
					@endforeach		
				</tbody>
			</table>
		</div>

		<div class="col-lg-6">
			<h4 class="lineup">Lineup</h4>

			<table style="font-size: 85%" id="lineup" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Pos</th>					
						<th>Name</th>
						<th>Team</th>
						<th>Opp</th>
						<th>Salary</th>
						<th>bFPTS</th>
						<th>Remove</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($lineup['players'] as $player)
						<tr class="lineup-row lineup-player-row" 
							data-player-pool-id="{{ $player->player_pool_id }}" 
							data-player-id="{{ $player->mlb_player_id }}" 
							data-position="{{ $player->position }}"
							data-team="{{ $player->abbr_dk }}" 
							data-opp="{{ $player->opp_abbr_dk }}"
							data-bat-fpts="{{ $player->bat_fpts }}">
							<td style="width: 5%" class="lineup-player-position">{{ $player->position }}</td>
							<td style="width: 25%" class="lineup-player-name">{{ $player->name }}</td>
							<td style="width: 10%" class="lineup-player-team">{{ $player->abbr_dk }}</td>
							<td style="width: 10%" class="lineup-player-opp">{{ $player->opp_abbr_dk }}</td>
							<td style="width: 15%" class="lineup-player-salary">{{ $player->salary }}</td>
							<td style="width: 15%" class="lineup-player-bat-fpts">{{ $player->bat_fpts }}</td>
							<td style="width: 10%"><a href="" class="remove-lineup-player-link">{!! $player->remove_player_icon !!}</a></td>
						</tr>
					@endforeach
					<tr class="lineup-row">
						<td colspan="4">
							<div class="input-group inline" style="margin: 0 auto">
						  		<span class="input-group-addon">$</span>
						  		<input style="width: 75px; margin-right: 30px" type="text" class="form-control lineup-buy-in-amount" value="{{ $lineup['metadata']->lineup_buy_in }}"> 

						  		<div style="display: inline-block; margin-top: 8px"><strong>Avg/Player: </strong> $<span class="avg-salary-per-player-left"></span></div>
							</div>
						</td>
						<td><span class="lineup-salary-total">{{ $lineup['metadata']->total_salary }}</span></td>
						<td><span class="lineup-bat-fpts-total">{{ $lineup['metadata']->total_bat_fpts }}</span></td>
						<td></td>
					</tr>	
				</tbody>
			</table>

			<button style="width: 128px" class="btn btn-primary pull-right submit-lineup" type="submit">Submit Lineup</button>
		</div>
	</div>

	<script type="text/javascript">

		/****************************************************************************************
		GLOBAL VARIABLES
		****************************************************************************************/

		var baseUrl = '<?php echo url(); ?>';

	</script>

	<script src="/js/lineup_builder/dk/mlb.js"></script>
@stop