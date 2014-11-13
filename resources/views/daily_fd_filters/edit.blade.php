@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Daily FD Filters - Edit</h2>


		</div>
	</div>
	<div class="row">
		{!!	Form::model($dailyFdFilter) !!}
			<div class="col-lg-2">
				<h3>{{ $player[0]->name }}</h3>

				<div class="form-group">
					{!! Form::label('filter', 'Filter:') !!}
					{!! Form::select('filter', array(0 => 'No', 1 => 'Yes'), null, ['class' => 'form-control']) !!}
				</div>
			</div>

			<div class="col-lg-12"> 
				{!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
			</div>
		{!!	Form::close() !!}
	</div>
@stop