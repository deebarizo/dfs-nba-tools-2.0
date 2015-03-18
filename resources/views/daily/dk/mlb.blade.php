@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily - DK MLB</h2>
		</div>
	</div>
	
	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $date }} {{ $timePeriod }} | <a target="_blank" href="/solver_top_plays/dk/mlb/{{ $timePeriodInUrl }}/{{ $date }}">Solver</a></h3>

			<form class="form-inline" style="margin: 0 0 10px 0">

				<label>Teams</label>
				<select class="form-control team-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	@foreach ($teams as $team)
					  	<option value="{{ $team->abbr_dk }}">{{ $team->abbr_dk }}</option>
				  	@endforeach
				</select>	

				<label>Positions</label>
				<select class="form-control position-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	<option value="SP">SP</option>
				  	<option value="C">C</option>
				  	<option value="1B">1B</option>
				  	<option value="2B">2B</option>
				  	<option value="3B">3B</option>
				  	<option value="SS">SS</option>
				  	<option value="OF">OF</option>
				</select>

				<label>Salary</label>
				<input class="salary-input form-control" type="number" value="0" style="width: 10%">
				<input class="form-control" type="radio" name="salary-toggle" id="greater-than" value="greater-than" checked="checked">>=
				<input class="form-control" type="radio" name="salary-toggle" id="less-than" value="less-than"><				
				<input style="width: 10%; margin-right: 20px; outline: none; margin-left: 5px" class="salary-reset btn btn-default" name="salary-reset" value="Salary Reset">

			</form>
		</div>

		<div class="col-lg-12">
			<form class="form-inline" style="margin: 0 0 10px 0">
				<label>Show Only Top Plays</label>
				<select class="form-control top-plays-filter" style="width: 10%; margin-right: 20px">
				  	<option value="0">No</option>
				  	<option value="1">Yes</option>
				</select>

				<label>Default Target %</label>
				<input class="default-target-percentage form-control" type="number" value="10" style="width: 10%">
			</form>
		</div>

		<div class="col-lg-12" style="margin: 2px 0 3px 0">
			<p>
				<span style="margin-right: 20px"><strong>SP: </strong> <span class="total-target-percentage-with-percentage-sign-SP"><span class="total-target-percentage-SP"></span>%</span></span>
				<span style="margin-right: 20px"><strong>C: </strong> <span class="total-target-percentage-with-percentage-sign-C"><span class="total-target-percentage-C"></span>%</span></span>
				<span style="margin-right: 20px"><strong>1B: </strong> <span class="total-target-percentage-with-percentage-sign-1B"><span class="total-target-percentage-1B"></span>%</span></span>
				<span style="margin-right: 20px"><strong>2B: </strong> <span class="total-target-percentage-with-percentage-sign-2B"><span class="total-target-percentage-2B"></span>%</span></span>
				<span style="margin-right: 20px"><strong>3B: </strong> <span class="total-target-percentage-with-percentage-sign-3B"><span class="total-target-percentage-3B"></span>%</span></span>
				<span style="margin-right: 20px"><strong>SS: </strong> <span class="total-target-percentage-with-percentage-sign-SS"><span class="total-target-percentage-SS"></span>%</span></span>
				<span style="margin-right: 40px"><strong>OF: </strong> <span class="total-target-percentage-with-percentage-sign-OF"><span class="total-target-percentage-OF"></span>%</span></span>
				
				<span style="margin-right: 60px"><strong>Total: </strong> <span class="total-target-percentage-with-percentage-sign"><span class="total-target-percentage"></span>%</span></span>

				<span><strong>Weighted Salary: </strong> <span class="total-weighted-salary-with-percentage-sign"><span class="total-weighted-salary"></span></span>
			</p>
		</div>

		<div class="col-lg-12">
			<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Mods</th>
						<th>Target %</th>
						<th>Team</th>
						<th>Pos</th>
						<th>Sal</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($players as $player)
					    <tr data-dk-mlb-players-id="{{ $player->dk_mlb_players_id }}" 
					    	data-date="{{ $player->date }}"
					    	data-buy-in="{{ $player->buy_in }}"
					    	data-player-pool-id="{{ $player->player_pool_id }}"
					    	data-mlb-player-id="{{ $player->mlb_player_id }}"
					    	data-mlb-team-id="{{ $player->mlb_team_id }}"
					    	data-position="{{ $player->position }}"
					    	data-salary="{{ $player->salary }}"
					    	data-name="{{ $player->name }}"
					    	data-abbr-dk="{{ $player->abbr_dk }}"
					    	class="player-row">
					    	<td>{{ $player->name }}</td>
			    			<td class="mods">
				    			<a href="#"><span class="glyphicon glyphicon-lock daily-lock {{ $player->css_lock_class }}" aria-hidden="true"></span></a>
				    			<span class="target-percentage-group">
				    				<a class="target-percentage-qtip edit-target-percentage-link" href="#">
				    					<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
				    				</a>
								</span>
								<div class="edit-target-percentage-tooltip">
									<input type="text" class="edit-target-percentage-input" value="{{ $player->target_percentage }}">
							    	<button class="edit-target-percentage-button" type="button">Submit</button>
								</div>
			    			</td>
			    			<td class="target-percentage-amount"><span class="target-percentage-amount">{{ $player->target_percentage }}</span></td>
					    	<td>{{ $player->abbr_dk }}</td>
					    	<td>{{ $player->position }}</td>
					    	<td>{{ $player->salary }}</td>
					    </tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>

	<script type="text/javascript">

		/****************************************************************************************
		GLOBAL VARIABLES
		****************************************************************************************/

		var baseUrl = '<?php echo url(); ?>';

	</script>

	<script src="/js/daily/dk/mlb.js"></script>
@stop