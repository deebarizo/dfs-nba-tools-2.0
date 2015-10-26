@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Update Player</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $playerTeams[0]->name }}</h3>
		</div>
	</div>

	<div class="row">
		@if(Session::has('message'))
		    <div class="col-lg-12">
				<div class="alert alert-{{ Session::get('alert') }} fade in" role="alert" style="width: 50%">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
					{{ Session::get('message') }}
				</div>
		    </div>
		@endif

		{!!	Form::open(['url' => 'admin/nba/update_player/'.$playerTeams[0]->player_id, 'class' => 'form-inline']) !!}

			@foreach ($playerTeams as $playerTeam)

				<div class="col-lg-12" style="margin-bottom: 15px">
					<label>Team</label>
					<select class="form-control" id="team_id_{{ $playerTeam->id }}" name="team_id_{{ $playerTeam->id }}" style="width: 15%; margin-right: 30px">
					  	@foreach ($teams as $team)
					  		<?php 
					  			if ($team->id == $playerTeam->team_id) {
									$isSelected = 'selected';
					  			} else {
					  				$isSelected = '';
					  			}
					  		?>
						  	<option value="{{ $team->id }}" {{ $isSelected }}>{{ $team->abbr_br }}</option>
					  	@endforeach
					</select>

					<label>Start Date</label>
					<input class="form-control" id="start_date_{{ $playerTeam->id }}" name="start_date_{{ $playerTeam->id }}" type="text" style="width: 15%; margin-right: 30px" value="{{ $playerTeam->start_date }}">	

					<label>End Date</label>
					<input class="form-control" id="end_date_{{ $playerTeam->id }}" name="end_date_{{ $playerTeam->id }}" type="text" style="width: 15%; margin-right: 30px" value="{{ $playerTeam->end_date }}">
				</div>

			@endforeach

			<div class="col-lg-12" style="margin-bottom: 15px">
				<input style="width: 10%; outline: none" type="submit" class="btn btn-primary submit-info" value="Submit">
			</div>

		{!!	Form::close() !!}

		<div class="col-lg-12" style="margin-bottom: 15px">
			<a href="/admin/nba/update_player">Update another player</a>
		</div>
	</div>

	<script type="text/javascript">
		$(document).off('.datepicker.data-api');
	</script>
@stop