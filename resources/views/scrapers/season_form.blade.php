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

		<div class="col-lg-2"> 
			<div class="form-group">
				{!! Form::label('game_groups', 'Game Groups:') !!}
				{!! Form::select(
						  'game_groups', 
						  array(
						  	'1' => '1-100', 
						  	'101' => '101-200',
						  	'201' => '201-300',
						  	'301' => '301-400',
						  	'401' => '401-500',
						  	'501' => '501-600',
						  	'601' => '601-700',
						  	'701' => '701-800',
						  	'801' => '801-900',
						  	'901' => '901-1000',
						  	'1001' => '1001-1100',
						  	'1101' => '1101-1200',
						  	'1201' => '1201-1300',
						  	'1301' => '1301-1400',
						  ), 
						  null, 
						  ['class' => 'form-control']) 
				!!}
			</div>
		</div>

		<div class="col-lg-12"> 
			{!! Form::submit('Scrape Season Data', ['class' => 'btn btn-primary']) !!}
		</div>
		
		{!!	Form::close() !!}
	</div>
@stop