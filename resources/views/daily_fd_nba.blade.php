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
			<h3>{{ $date }} {{ $timePeriod }} | <a target="_blank" href="/solver_fd_nba/{{ $date }}">Solver</a></h3>

			<p><a class="show-toggle-dtd-players" href="#">DTD Players</a></p>

			<table id="daily-dtd" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
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
					@foreach ($dtdPlayers as $player)
						<?php 
							$noFilterQtip = 'class="player-filter"';
							$spanFilterLink = ''; 

							if (isset($player->vegas_score_team)) {
								$line = $player->vegas_score_opp_team - $player->vegas_score_team;
							} else {
								$line = 'None';
							}
						?>

					    <tr>
					    	<td>
					    		<a target="_blank" href="/players/{{ $player->player_id }}">{{ $player->name }}</a> 
					    		<a {!! $noFilterQtip !!} target="_blank" href="/daily_fd_filters/{{ $player->player_id }}/create">
					    			<span {!! $spanFilterLink !!} class="glyphicon glyphicon-filter" aria-hidden="true"></span>
				    			</a> 

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
				    			<a target="_blank" href="/daily_fd_filters/{{ $player->player_id }}/edit">E</a>
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

		<div class="col-lg-12">
			<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
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
						?>

					    <tr>
					    	<td>
					    		<a target="_blank" href="/players/{{ $player->player_id }}">{{ $player->name }}</a> 
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
				    			<a target="_blank" href="/daily_fd_filters/{{ $player->player_id }}/edit">E</a>
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
		$(document).ready(function() {
    		$('#daily').dataTable({
    			"scrollY": "600px",
    			"paging": false,
    			"order": [[5, "desc"]]
    		});

		    $('.player-filter').each(function() {
		        $(this).qtip({
		            content: {
		                text: $(this).next('.player-filter-tooltip')
		            }
		        });
		    });   

			$(".show-toggle-dtd-players").click(function(){
			  $("#daily-dtd").toggle();
			}); 		
		});
	</script>
@stop