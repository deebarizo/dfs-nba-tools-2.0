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
			<h3>{{ $date }} {{ $timePeriod }} | <a target="_blank" href="/solver_with_top_plays_fd_nba/{{ $date }}">Solver (With Top Plays)</a> <!-- |  <a target="_blank" href="/solver_fd_nba/{{ $date }}">Solver</a> --></h3>

			<form class="form-inline" style="margin: 15px 0 10px 0">

				<label>Positions</label>
				<select class="form-control position-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	<option value="PG">PG</option>
				  	<option value="SG">SG</option>
				  	<option value="SF">SF</option>
				  	<option value="PF">PF</option>
				  	<option value="C">C</option>
				</select>

				<label>Teams</label>
				<select class="form-control team-filter" style="width: 10%; margin-right: 20px">
				  	<option value="All">All</option>
				  	@foreach ($teamsToday as $team)
					  	<option value="{{ $team }}">{{ $team }}</option>
				  	@endforeach
				</select>

				<label>Show Only Top Plays</label>
				<select class="form-control top-plays-filter" style="width: 10%; margin-right: 20px">
				  	<option value="0">No</option>
				  	<option value="1">Yes</option>
				</select>
			
			</form>
		</div>

		<div class="col-lg-12" style="margin-bottom: 7px">
			<p><strong>Total Target Percentage: </strong> <span class="total-target-percentage">N/A</span></p>
		</div>

		<div class="col-lg-12">
			<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Mods</th>
						<th>Target %</th>
						<th>Team</th>
						<th>Opp</th>
						<th>Line</th>
						<th>Pos</th>
						<th>Salary</th>
						<th>VR</th>
						<th>VR-1</th>
						<th>FPPG</th>
						<th>FPPG-1</th>
						<th>FPPM</th>
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
								$playerLockedClass = ' daily-lock-active';

								$targetPercentage = $player->target_percentage;
								$targetPercentageGroup = '';
							} else {
								$playerLockedClass = '';

								$targetPercentage = '---';
								$targetPercentageGroup = 'hide-target-percentage-group';
							}
						?>

					    <tr data-player-fd-index="{{ $player->player_fd_index }}" 
					    	data-player-position="{{ $player->position }}"
					    	data-player-team="{{ $player->team_abbr }}"
					    	class="player-row">
					    	<td><a target="_blank" href="/players/{{ $player->player_id }}">{{ $player->name }}</a>
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
				    			<a target="_blank" href="/daily_fd_filters/{{ $player->player_id }}/edit"><span class="glyphicon glyphicon-edit" aria-hidden="true"></span></a> 
				    			<a href="#"><span class="glyphicon glyphicon-lock daily-lock {{ $playerLockedClass }}" aria-hidden="true"></span></a>
			    			</td>
			    			<td style="text-align: center">
			    				<span class="target-percentage-amount">{{ $targetPercentage }}</span><span class="target-percentage-group {{ $targetPercentageGroup }}">% 
				    				<a class="target-percentage-qtip edit-target-percentage-link" href="#">
				    					<span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
				    				</a>
								</span>
								
								<div class="edit-target-percentage-tooltip">
									<input type="text" class="edit-target-percentage-input" value="{{ $player->target_percentage }}">
							    	<button class="edit-target-percentage-button" type="button">Submit</button>
								</div>
			    			</td>
					    	<td>{{ $player->team_abbr }}</td>
					    	<td>{{ $player->opp_team_abbr }}</td>
					    	<td>{{ $line }}</td>
					    	<td>{{ $player->position }}</td>
					    	<td>{{ $player->salary }}</td>
					    	<td>{{ $player->vr }}</td>
					    	<td>{{ $player->vr_minus_1sd }}</td>
					    	<td>{{ $player->fppgWithVegasFilter }}</td>
					    	<td>{{ $player->fppgMinus1WithVegasFilter }}</td>
					    	<td>{{ $player->fppmPerGameWithVegasFilter }}</td>
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

	<script src="/js/daily_fd_nba.js"></script>
@stop