@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Add Player - NBA</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			{!!	Form::open(['url' => 'admin/nba/add_player']) !!}

				<label>Name</label>
				<input class="form-control" name="name" id="name" type="text" style="width: 30%; margin-bottom: 10px">		

				<label>Team</label>
				<select class="form-control" id="team_id" name="team_id" style="width: 15%; margin-bottom: 10px">
				  	@foreach ($teams as $team)
					  	<option value="{{ $team->id }}">{{ $team->name_br }}</option>
				  	@endforeach
				</select>

				<label>Start Date for Team</label>
				<input class="form-control" id="start_date" name="start_date" type="date" style="width: 15%; margin-bottom: 30px" value="{{ $startDate }}">	

				<input style="width: 10%; outline: none" type="submit" class="btn btn-primary submit-info" value="Submit">
			
			{!!	Form::close() !!}
		</div>
	</div>
@stop