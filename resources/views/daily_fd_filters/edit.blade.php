@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD Filters - Edit</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $player[0]->name }}</h3>
		</div>
	</div>

	<div class="row">
		{!!	Form::model($dailyFdFilter) !!}
			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('filter', 'Filter:') !!}
					{!! Form::select('filter', array(0 => 'No', 1 => 'Yes'), null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('playing', 'Playing:') !!}
					{!! Form::select('playing', array(0 => 'No', 1 => 'Yes'), null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('fppg_source', 'FPPG Source:') !!}
					{!! Form::text('fppg_source', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('fppm_source', 'FPPM Source:') !!}
					{!! Form::text('fppm_source', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('cv_source', 'CV Source:') !!}
					{!! Form::text('cv_source', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('mp_ot_filter', 'MP OT Filter:') !!}
					{!! Form::text('mp_ot_filter', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('dnp_games', 'DNP Games:') !!}
					{!! Form::text('dnp_games', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('notes', 'Notes:') !!}
					{!! Form::textarea('notes', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-12"> 
				{!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
			</div>
		{!!	Form::close() !!}
	</div>
@stop