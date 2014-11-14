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
			<h3>{{ $date }} {{ $timePeriod }}</h3>

			<table id="daily" class="table table-striped table-bordered table-hover table-condensed">
				<thead>
					<tr>
						<th>Name</th>
						<th>Team</th>
						<th>Opp</th>
						<th>Pos</th>
						<th>Salary</th>
						<th>VR</th>
						<th>VR-1</th>
						<th>CV</th>
						<th>FPPM</th>
						<th>CVPM</th>
						<th>FPPM-1</th>
						<th>FPPG</th>
						<th>FPPG-1</th>
					<!--<th>ST</th>
						<th>VST</th>
						<th>VSOT</th> -->
					</tr>
				</thead>
				<tbody>
					@foreach ($players as $player)
					    <tr>
					    	<td><a target="_blank" href="/players/{{ $player->player_id }}">{{ $player->name }}</a> <a target="_blank" href="/daily_fd_filters/{{ $player->player_id }}/create"><span class="glyphicon glyphicon-filter" aria-hidden="true"></span></a></td>
					    	<td>{{ $player->team_abbr }}</td>
					    	<td>{{ $player->opp_team_abbr }}</td>
					    	<td>{{ $player->position }}</td>
					    	<td>{{ $player->salary }}</td>
					    	<td>{{ $player->vr }}</td>
					    	<td>{{ $player->vr_minus_1sd }}</td>
					    	<td>{{ $player->cv }}</td>
					    	<td>{{ $player->fppmPerGame }}</td>
					    	<td>{{ $player->cvFppm }}</td>
					    	<td>{{ $player->fppm_minus_1sd }}</td>
					    	<td>{{ $player->fppg }}</td>
					    	<td>{{ $player->fppg_minus_1sd }}</td>
					    <!--<td>100</td>
					    	<td>{{ $player->vegas_score_team }}</td>
					    	<td>{{ $player->vegas_score_opp_team }}</td> -->
					    </tr>
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
    			"order": [[6, "desc"]]
    		});
		});
	</script>
@stop