@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - FD NBA Salaries</h2>
		</div>
	</div>
	<div class="row">

		{!!	Form::open(['url' => 'scrapers/fd_nba_salaries_scraper']) !!}
		<div class="col-lg-6"> 
			<div class="form-group">
				{!! Form::label('url', 'URL:') !!}
				{!! Form::text('url', null, ['class' => 'form-control']) !!}
			</div>
		</div>

		<div class="col-lg-2"> 
			<div class="form-group">
				{!! Form::label('time_period', 'Time Period:') !!}
				{!! Form::select('time_period', array('All Day' => 'All Day', 'Early' => 'Early', 'Late' => 'Late'), null, ['class' => 'form-control']) !!}
			</div>
		</div>

		<div class="col-lg-12"> 
			{!! Form::submit('Submit', ['class' => 'btn btn-primary']) !!}
		</div>
		
		{!!	Form::close() !!}
	</div>
@stop