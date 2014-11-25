@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD Filters - Create</h2>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-12">
			<h3>{{ $player[0]->name }}</h3>
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

		{!!	Form::open(['route' => 'daily_fd_filters.store']) !!}

			{!! Form::hidden('player_id', $player[0]->id); !!}

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('filter', 'Filter:') !!}
					{!! Form::select('filter', array(0 => 'No', 1 => 'Yes'), 1, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('playing', 'Playing:') !!}
					{!! Form::select('playing', array(0 => 'No', 1 => 'Yes'), 1, ['class' => 'form-control']) !!}
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
					{!! Form::text('mp_ot_filter', $mpOtFilter, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('dnp_games', 'DNP Games:') !!}
					{!! Form::text('dnp_games', 0, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-2">
				<div class="form-group">
					{!! Form::label('notes', 'Notes:') !!}
					{!! Form::textarea('notes', null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-12"> 
				{!! Form::submit('Create Filter', ['class' => 'btn btn-primary']) !!}
			</div>

		{!!	Form::close() !!}
	</div>
@stop