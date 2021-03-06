@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily - FD NBA</h2>
		</div>
	</div>
	
	<div class="row">
		<?php $errors = Session::get('errors') ? : $errors; ?>

		@if(Session::has('message'))
		    <div class="col-lg-12">
				<div class="alert alert-{{ Session::get('alert') }} fade in" role="alert" style="width: 50%">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
					{{ Session::get('message') }}
				</div>
		    </div>
		@endif

		<div class="col-lg-12">
			<h3>{{ $date }} {{ $timePeriod }} | <a target="_blank" href="/solver_top_plays/fd/nba/all-day/{{ $date }}">Solver</a> | <a target="_blank" href="/lineup_builder/{{ $date }}/create/">Lineup Builder</a></h3>

			<form class="form-inline" style="margin: 0 0 10px 0">

				<label>Times</label>
				<select class="form-control time-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	@foreach ($gameTimes as $gameTime)
					  	<option value="{{ $gameTime }}">{{ $gameTime }}</option>
				  	@endforeach
				</select>

				<label>Teams</label>
				<select class="form-control team-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	@foreach ($teamsToday['abbr'] as $team)
					  	<option value="{{ $team }}">{{ $team }}</option>
				  	@endforeach
				</select>			

				<label>Positions</label>
				<select class="form-control position-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	<option value="PG">PG</option>
				  	<option value="SG">SG</option>
				  	<option value="SF">SF</option>
				  	<option value="PF">PF</option>
				  	<option value="C">C</option>
				</select>

				<label>Salary</label>
				<input class="salary-input form-control" type="number" value="0" style="width: 10%">
				<input class="form-control" type="radio" name="salary-toggle" id="greater-than" value="greater-than" checked="checked">>=
				<input class="form-control" type="radio" name="salary-toggle" id="less-than" value="less-than"><=				
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
				<input class="default-target-percentage form-control" type="number" value="5" style="width: 10%">
			</form>
		</div>

		<div class="col-lg-12" style="margin: 2px 0 3px 0">
			<p>
				<span style="margin-right: 20px"><strong>PG: </strong> <span class="total-target-percentage-PG"></span>%</span>
				<span style="margin-right: 20px"><strong>SG: </strong> <span class="total-target-percentage-SG"></span>%</span>
				<span style="margin-right: 20px"><strong>SF: </strong> <span class="total-target-percentage-SF"></span>%</span>
				<span style="margin-right: 20px"><strong>PF: </strong> <span class="total-target-percentage-PF"></span>%</span>
				<span style="margin-right: 40px"><strong>C: </strong> <span class="total-target-percentage-C"></span>%</span>
				<span style="margin-right: 20px"><strong>+6500: </strong> <span class="total-target-percentage-plus"></span>%</span>
				<span style="margin-right: 40px"><strong>-6500: </strong> <span class="total-target-percentage-minus"></span>%</span>
				<span style="margin-right: 60px"><strong>Total: </strong> <span class="total-target-percentage"></span>%</span>

				<span><strong>Weighted Salary: </strong> <span class="total-weighted-salary"></span></span>
			</p>
		</div>

		<div class="col-lg-12">
			<table style="font-size: 85%" id="daily" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Mods</th>
						<th>T%</th>
						<th>Time</th>
						<th>Team</th>
						<th>Opp</th>
						<th>Line</th>
						<th>Pos</th>
						<th>Filter</th>
						<th>MP</th>
						<th>FPPM</th>
						<th>FP</th>
						<th>Sal</th>
						<th>VR</th>
						<th>aFP</th>
						<th>aVR</th>
					</tr>
				</thead>
				<tbody>
					@foreach ($players as $player)
						<?php 
							$noFilterQtip = 'class="player-filter"';
							$spanFilterLink = ''; 

							if (!isset($player->filter->playing)) { 
								$noFilterQtip = '';
								$spanFilterLink = 'style="color: red"'; 
							} 

							if (isset($player->vegas_score_team)) {
								$line = $player->vegas_score_opp_team - $player->vegas_score_team;
							} else {
								$line = 'None';
							}

							$isPlayerLocked = $player->top_play_index;

							if ($isPlayerLocked == 1) {
								$playerLockedClass = 'daily-lock-active';
							} else {
								$playerLockedClass = '';
							}

							if (!isset($player->is_player_on_home_team)) {
								$player->is_player_on_home_team = '';
							}

							if (!isset($player->is_player_on_road_team)) {
								$player->is_player_on_road_team = '';
							}
						?>

					    <tr data-player-fd-index="{{ $player->player_fd_index }}" 
					    	data-player-position="{{ $player->position }}"
					    	data-player-team="{{ $player->team_abbr }}"
					    	class="player-row">
					    	<td><a target="_blank" href="/players/nba/{{ $player->player_id }}">{{ $player->name }}</a>
			    			</td>
			    			<td>
					    		<a {!! $noFilterQtip !!} target="_blank" href="/daily_fd_filters/{{ $player->player_id }}/create">
					    			<span {!! $spanFilterLink !!} class="glyphicon glyphicon-filter" aria-hidden="true"></span>
				    			</a> 
				    			@if (isset($player->filter))
				    			<div class="player-filter-tooltip">
									<table class="player-filter-tooltip-table">
									  	<tr>
										    <th>Filter</th>
										    <th>FPPG</th>
										    <th>FPPM</th>
										    <th>CV</th>
										    <th>Notes</th>
									  	</tr>
									  	<tr>
										    <td>{{ $player->filter->filter }}</td>
										    <td>{{ $player->filter->fppg_source }}</td>
										    <td>{{ $player->filter->fppm_source }}</td>
										    <td>{{ $player->filter->cv_source }}</td>
										    <td>{{ $player->filter->notes }}</td>
									  	</tr>
									</table>
								</div>
								@endif
				    			<a href="#"><span class="glyphicon glyphicon-lock daily-lock {{ $playerLockedClass }}" aria-hidden="true"></span></a>
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
			    			<td class="target-percentage-amount">{{ $player->target_percentage }}</td>
			    			<td class="time">
			    				@if (isset($player->game_time))
				    				{{ $player->game_time }}
				    			@else
				    				NLY
				    			@endif
			    			</td>
					    	<td><a target="_blank" href="/teams/{{ $player->team_abbr }}">{{ $player->team_abbr }}{!! $player->is_player_on_home_team !!}</a> (<a target="_blank" href="http://www.basketball-reference.com/teams/{{ $player->team_abbr }}/2016.html#all_advanced">br</a>)</td>
					    	<td><a target="_blank" href="/teams/{{ $player->opp_team_abbr }}">{{ $player->opp_team_abbr }}{!! $player->is_player_on_road_team !!}</a> (<a target="_blank" href="http://www.basketball-reference.com/teams/{{ $player->opp_team_abbr }}/2016.html#all_advanced">br</a>)</td>
					    	<td>{{ $line }}</td>
					    	<td>{{ $player->position }}</td>
					    	<td>{{ $player->fppmTotalFilter }}</td>
					    	<td>{{ numFormat($player->mp_mod) }}</td>
					    	<td>{{ $player->fppmWithAllFilters }}</td>
					    	<td>{{ $player->fppgWithAllFilters }}</td>
					    	<td class="salary">{{ $player->salary }}</td>
					    	<td>{{ $player->vr }}</td>
							@if (isset($player->box_score_line))
								@if ($player->box_score_line == 'DNP')
									<td>0.00</td>
								@else
									<td><a target="_blank" href="/games/nba/{{ $player->box_score_line->id }}">{{ numFormat($player->box_score_line->fdpts, 2) }}</a></td>
								@endif
							@else
								<td>NA</td>
							@endif
							@if (isset($player->box_score_line))
								@if ($player->box_score_line == 'DNP')
									<td>0.00</td>
								@else
									<td><a target="_blank" href="/games/nba/{{ $player->box_score_line->id }}">{{ numFormat($player->box_score_line->fdpts / ($player->salary / 1000), 2) }}</a></td>
								@endif
							@else
								<td>NA</td>
							@endif
					    </tr>

					    <?php unset($line); ?>
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

	<script src="/js/daily/fd/nba.js"></script>
@stop