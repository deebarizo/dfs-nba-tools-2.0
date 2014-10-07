@extends('master')

@section('content')
	<h2>Scrapers - Season</h2>

	{!!	Form::open(['url' => 'scrapers/season_scraper']) !!}

		<div class="form-group">
			{!! Form::text('end_year', null, ['class' => 'form-control']) !!}
		</div>

		<div class="form-group">
			{!! Form::submit('Scrape Season Data', ['class' => 'btn btn-primary']) !!}
		</div>
	
	{!!	Form::close() !!}
@stop