@extends('master')

@section('content')
	<div class="row">
		<div class="col-lg-12">
			<h2>Scrapers - Season</h2>
		</div>
	</div>
	<div class="row">
		@if(Session::has('message'))
		    <div class="col-lg-12">
				<div class="alert alert-info fade in" role="alert" style="width: 50%">
					<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">×</span><span class="sr-only">Close</span></button>
					{{ Session::get('message') }}
				</div>
		    </div>
		@endif

		{!!	Form::open(['url' => 'scrapers/season_scraper']) !!}
		<div class="col-lg-2"> 
			<div class="form-group">
				{!! Form::label('end_year', 'End Year of Season:') !!}
				{!! Form::text('end_year', null, ['class' => 'form-control']) !!}
			</div>
		</div>

		<div class="col-lg-2"> 
			<div class="form-group">
				{!! Form::label('type', 'Type:') !!}
				{!! Form::select('type', array('regular' => 'Regular Season', 'playoffs' => 'Playoffs'), null, ['class' => 'form-control']) !!}
			</div>
		</div>

		<div class="col-lg-12"> 
			{!! Form::submit('Scrape Season Data', ['class' => 'btn btn-primary']) !!}
		</div>
		
		{!!	Form::close() !!}
	</div>
@stop