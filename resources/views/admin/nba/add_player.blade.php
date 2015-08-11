@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Add Player - NBA</h2>
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

		<div class="col-lg-12">
			{!!	Form::open(['url' => 'admin/nba/add_player']) !!}

				<label>Name</label>
				<input class="form-control" name="name" id="name" type="text" style="width: 30%; margin-bottom: 10px">		

				<label>Team</label>
				<select class="form-control" id="team_id" name="team_id" style="width: 22%; margin-bottom: 10px">
				  	@foreach ($teams as $team)
				  		<?php 
				  			if ($team->id == 18) {
								$isSelected = 'selected';
				  			} else {
				  				$isSelected = '';
				  			}
				  		?>
					  	<option value="{{ $team->id }}" {{ $isSelected }}>{{ $team->name_br }}</option>
				  	@endforeach
				</select>

				<label>Start Date for Team</label>
				<input class="form-control" id="start_date" name="start_date" type="date" style="width: 15%; margin-bottom: 10px" value="{{ $startDate }}">	

				<label>Rookie?</label>
				<select class="form-control" id="is_rookie" name="is_rookie" style="width: 15%; margin-bottom: 30px">
				  	<option value="0">No</option>
				  	<option value="1">Yes</option>
				</select>

				<input style="width: 10%; outline: none" type="submit" class="btn btn-primary submit-info" value="Submit">
			
			{!!	Form::close() !!}
		</div>
	</div>
@stop